<?php
/**
 * Stat Charts extension for phpBB.
 *
 * @copyright (c) 2026, biochaos
 * @license GNU General Public License, version 2 (GPL-2.0)
 */

namespace biochaos\statcharts\service;

use phpbb\cache\driver\driver_interface as cache_interface;
use phpbb\config\config;
use phpbb\db\driver\driver_interface;
use phpbb\language\language;
use phpbb\textformatter\s9e\utils;

/**
 * Every read the stats pages make. Reads only the stats tables - never the
 * forum's own, except post_text for the handful of previews shown - so what
 * the rebuild excluded cannot appear here (ETL rule 2).
 *
 * Per viewer, phpBB's own rules apply (SPEC section 10): numbers stay
 * board-wide, but a forum the viewer cannot list (f_list) is never named - its
 * share goes to one "hidden" entity, id 0 - and topics, titles and previews
 * only come from forums the viewer can read (f_read). set_viewer() must be
 * called first; until it is, nothing is visible.
 *
 * Days are integers (days since 1970-01-01, board timezone). Names and titles
 * are returned as plain text, entities decoded; the page escapes them.
 * Results are cached until the next rebuild: every cache key carries the
 * build time, the presentation settings and the viewer's forum visibility.
 */
class reader
{
	const CACHE_TTL = 7200;

	const LIMIT         = 10;
	const LIMIT_LIKED   = 25;
	const MAX_LIMIT     = 50;
	const PREVIEW_SHORT = 200;
	const PREVIEW_LONG  = 500;

	/** Widgets of top(), with the scopes each accepts. */
	const WIDGETS = [
		'viewed'        => ['all', 'forum'],
		'active'        => ['all', 'forum', 'user'],
		'liked'         => ['all', 'forum', 'user', 'topic'],
		'posters'       => ['topic', 'forum'],
		'liked_members' => ['forum'],
	];

	/** @var driver_interface */
	private $db;

	/** @var config */
	private $config;

	/** @var cache_interface */
	private $cache;

	/** @var language */
	private $language;

	/** @var utils */
	private $text_utils;

	/** @var grouping */
	private $grouping;

	/** @var colours */
	private $colours;

	/** @var string */
	private $table_prefix;

	/** @var string */
	private $posts_table;

	/** @var array|null|false Memoised meta; false until read */
	private $meta = false;

	/** @var array|null Memoised grouping result, as this viewer sees it */
	private $groups;

	/** @var array Forum ids the viewer may see listed, as a set */
	private $listable = [];

	/** @var array Forum ids the viewer may read, as a set */
	private $readable = [];

	/** @var tables */
	private $tables;

	public function __construct(
		driver_interface $db,
		config $config,
		cache_interface $cache,
		language $language,
		utils $text_utils,
		grouping $grouping,
		$table_prefix,
		$posts_table,
		colours $colours,
		tables $tables
	)
	{
		$this->colours      = $colours;
		$this->tables       = $tables;
		$this->db           = $db;
		$this->config       = $config;
		$this->cache        = $cache;
		$this->language     = $language;
		$this->text_utils   = $text_utils;
		$this->grouping     = $grouping;
		$this->table_prefix = $table_prefix;
		$this->posts_table  = $posts_table;
	}

	/**
	 * @param int[] $listable Forum ids with f_list for the viewer
	 * @param int[] $readable Forum ids with f_read for the viewer
	 */
	public function set_viewer(array $listable, array $readable)
	{
		$this->listable = array_fill_keys(array_map('intval', $listable), true);
		$this->readable = array_fill_keys(array_map('intval', $readable), true);
		ksort($this->listable);
		ksort($this->readable);
		$this->groups = null;
	}

	/**
	 * @return array|null Build metadata, or null when the tables were never built
	 */
	public function meta()
	{
		if ($this->meta !== false)
		{
			return $this->meta;
		}

		$result = $this->db->sql_query('SELECT meta_key, meta_value FROM ' . $this->table('meta'));
		$raw = [];
		while ($row = $this->db->sql_fetchrow($result))
		{
			$raw[$row['meta_key']] = $row['meta_value'];
		}
		$this->db->sql_freeresult($result);

		if (!isset($raw['built_at']))
		{
			return $this->meta = null;
		}

		$first_day = (int) $raw['first_day'];
		return $this->meta = [
			'first_day'  => $first_day,
			'last_day'   => (int) $raw['last_day'],
			'as_of_day'  => (int) $raw['as_of_day'],
			'start_day'  => max($first_day, $this->configured_start_day()),
			'built_at'   => (int) $raw['built_at'],
			'timezone'   => $raw['timezone'],
			'has_likes'  => (bool) $raw['has_likes'],
		];
	}

	/**
	 * What the last build was made with, for the ACP only (never sent to the
	 * page: it names the excluded forums).
	 *
	 * @return array|null excluded (int[], cascaded), count_anonymous (bool), timezone, built_at; null if never built
	 */
	public function build_params()
	{
		$result = $this->db->sql_query('SELECT meta_key, meta_value FROM ' . $this->table('meta'));
		$raw = [];
		while ($row = $this->db->sql_fetchrow($result))
		{
			$raw[$row['meta_key']] = $row['meta_value'];
		}
		$this->db->sql_freeresult($result);
		if (!isset($raw['built_at']))
		{
			return null;
		}
		$excluded = array_values(array_filter(array_map('intval', explode(',', (string) $raw['excluded']))));
		sort($excluded);
		return [
			'excluded'        => $excluded,
			'count_anonymous' => !empty($raw['count_anonymous']),
			'timezone'        => $raw['timezone'],
			'built_at'        => (int) $raw['built_at'],
		];
	}

	/**
	 * The general and forum tabs' data in one payload (SPEC section 8): per
	 * entity daily posts, topics, views and likes, per entity totals, and the
	 * users series. Pies, time series, range selectors and the chart toggle all
	 * compute from this client-side.
	 */
	public function overview()
	{
		return $this->cached('overview', function () {
			$groups = $this->groups();
			$map = $groups['forum_entity'];

			$totals = [];
			foreach ($groups['entities'] as $entity)
			{
				$totals[$entity['id']] = ['posts' => 0, 'topics' => 0, 'likes' => 0, 'views' => 0];
			}
			$result = $this->db->sql_query('SELECT forum_id, posts, topics, likes, views FROM ' . $this->table('totals_forum'));
			while ($row = $this->db->sql_fetchrow($result))
			{
				$forum_id = (int) $row['forum_id'];
				if (isset($map[$forum_id]) && isset($totals[$map[$forum_id]]))
				{
					foreach (['posts', 'topics', 'likes', 'views'] as $key)
					{
						$totals[$map[(int) $row['forum_id']]][$key] += (int) $row[$key];
					}
				}
			}
			$this->db->sql_freeresult($result);

			return [
				'meta'     => $this->meta(),
				'grouping' => $this->grouping_mode(),
				'entities' => $groups['entities'],
				'totals'   => $totals,
				'posts'    => $this->entity_series('SELECT day, forum_id, posts AS n FROM ' . $this->table('posts_daily_forum')),
				'topics'   => $this->entity_series('SELECT day, forum_id, topics AS n FROM ' . $this->table('topics_daily_forum')),
				'views'    => $this->entity_series('SELECT day, forum_id, views AS n FROM ' . $this->table('topics_daily_forum')),
				'likes'    => $this->entity_series('SELECT day, forum_id, likes AS n FROM ' . $this->table('likes_daily_forum')),
				'users'    => $this->users_series(),
			];
		});
	}

	/**
	 * A top-N table (SPEC section 8; widget inventory in section 15, 6).
	 *
	 * @param string   $widget viewed | active | liked | posters | liked_members
	 * @param string   $scope  all | forum (id = entity) | user | topic
	 * @param int      $id     Scope id; ignored for "all"
	 * @param int|null $from   First day, inclusive; null = the effective start
	 * @param int|null $to     Last day, inclusive; null = the as-of day
	 * @param int      $limit  0 = the widget's default
	 * @return array|null Rows, or null for an unknown widget, scope or id
	 */
	public function top($widget, $scope, $id, $from, $to, $limit)
	{
		if (!isset(self::WIDGETS[$widget]) || !in_array($scope, self::WIDGETS[$widget], true))
		{
			return null;
		}

		$meta = $this->meta();
		$from = $from === null ? $meta['start_day'] : max($meta['start_day'], (int) $from);
		$to = $to === null ? $meta['as_of_day'] : min($meta['as_of_day'], (int) $to);

		$default = ($widget === 'liked' && $scope === 'all') ? self::LIMIT_LIKED : self::LIMIT;
		$limit = $limit > 0 ? min((int) $limit, self::MAX_LIMIT) : $default;

		if ($scope === 'forum' && $this->entity_forums((int) $id) === null)
		{
			return null;
		}
		if ($scope === 'topic' && !$this->topic_readable((int) $id))
		{
			return null;
		}

		$key = implode('|', ['top', $widget, $scope, (int) $id, $from, $to, $limit]);
		return $this->cached($key, function () use ($widget, $scope, $id, $from, $to, $limit) {
			$where = [];
			if ($scope === 'forum')
			{
				$where[] = $this->db->sql_in_set('forum_id', $this->entity_forums((int) $id), false, true);
			}
			else if ($scope === 'user')
			{
				$where[] = ($widget === 'liked' ? 'poster_id' : 'user_id') . ' = ' . (int) $id;
			}
			else if ($scope === 'topic')
			{
				$where[] = 'topic_id = ' . (int) $id;
			}

			// Only what the viewer may read. A like's post and its topic both carry
			// a forum, and they can disagree (SPEC section 5), so both must pass.
			if ($widget === 'viewed' || ($widget === 'active' && $scope !== 'user'))
			{
				$where[] = $this->readable_sql('forum_id');
			}
			else if ($widget === 'active')
			{
				$where[] = $this->readable_topics_sql();
			}
			else if ($widget === 'liked')
			{
				$where[] = $this->readable_sql('forum_id');
				$where[] = $this->readable_topics_sql();
			}

			switch ($widget)
			{
				case 'viewed':
					return $this->top_viewed($where, $from, $to, $limit);
				case 'active':
					return $this->top_active($where, $scope, $from, $to, $limit);
				case 'liked':
					$length = ($scope === 'all') ? self::PREVIEW_LONG : self::PREVIEW_SHORT;
					return $this->top_liked($where, $from, $to, $limit, $length);
				case 'posters':
					return $scope === 'topic'
						? $this->top_posters((int) $id, $from, $to, $limit)
						: $this->top_members($this->entity_forums((int) $id), 'posts', $from, $to, $limit);
				case 'liked_members':
					return $this->top_members($this->entity_forums((int) $id), 'likes', $from, $to, $limit);
			}
		});
	}

	/**
	 * The user tab's header and series (U1-U3; its tables, U4-U5, come from
	 * top() with a range). Totals are board-wide, like the post count on a
	 * phpBB profile; the per-forum series names only forums the viewer can list.
	 *
	 * @return array|null Null when the user is unknown
	 */
	public function user($user_id)
	{
		$user_id = (int) $user_id;
		return $this->cached('user|' . $user_id, function () use ($user_id) {
			$result = $this->db->sql_query($this->db->sql_build_query('SELECT', [
				'SELECT'    => 'u.user_id, u.username, u.user_colour, t.posts, t.likes',
				'FROM'      => [$this->table('dim_user') => 'u'],
				'LEFT_JOIN' => [[
					'FROM' => [$this->table('totals_user') => 't'],
					'ON'   => 't.user_id = u.user_id',
				]],
				'WHERE'     => 'u.user_id = ' . (int) $user_id,
			]));
			$row = $this->db->sql_fetchrow($result);
			$this->db->sql_freeresult($result);
			if (!$row)
			{
				return null;
			}

			return [
				'user'       => [
					'id'     => $user_id,
					'name'   => $this->plain($row['username']),
					'colour' => $row['user_colour'],
				],
				'totals'     => ['posts' => (int) $row['posts'], 'likes' => (int) $row['likes']],
				'posts'      => $this->entity_series('SELECT day, forum_id, posts AS n
					FROM ' . $this->table('posts_daily_user_forum') . '
					WHERE user_id = ' . (int) $user_id),
			];
		});
	}

	/**
	 * A user tab with nothing on it yet, shaped like user(): someone who has
	 * not posted since the last rebuild is not in the stats tables.
	 */
	public function empty_user($user_id, $username, $colour)
	{
		return [
			'user'       => ['id' => (int) $user_id, 'name' => $this->plain($username), 'colour' => $colour],
			'totals'     => ['posts' => 0, 'likes' => 0],
			'posts'      => [],
		];
	}

	/**
	 * The topic tab's header and series (T1-T3; its tables, T4-T5, come from
	 * top() with a range).
	 *
	 * @param int $topic_id 0 = the newest topic the viewer can read
	 * @return array|null Null when the topic is unknown, excluded, or not readable by the viewer
	 */
	public function topic($topic_id)
	{
		$topic_id = (int) $topic_id;
		if (!$topic_id)
		{
			$result = $this->db->sql_query('SELECT MAX(topic_id) AS id FROM ' . $this->table('dim_topic') . '
				WHERE ' . $this->readable_sql('forum_id'));
			$topic_id = (int) $this->db->sql_fetchfield('id');
			$this->db->sql_freeresult($result);
		}

		if (!$this->topic_readable($topic_id))
		{
			return null;
		}

		return $this->cached('topic|' . $topic_id, function () use ($topic_id) {
			$result = $this->db->sql_query($this->db->sql_build_query('SELECT', [
				'SELECT' => 't.topic_id, t.forum_id, t.topic_title, t.topic_poster, t.poster_name,
					t.topic_day, t.views, t.replies, f.forum_name',
				'FROM'   => [$this->table('dim_topic') => 't', $this->table('dim_forum') => 'f'],
				'WHERE'  => 't.topic_id = ' . (int) $topic_id . '
					AND f.forum_id = t.forum_id',
			]));
			$row = $this->db->sql_fetchrow($result);
			$this->db->sql_freeresult($result);
			if (!$row)
			{
				return null;
			}

			$days = $counts = [];
			$result = $this->db->sql_query('SELECT day, posts FROM ' . $this->table('posts_daily_topic') . '
				WHERE topic_id = ' . (int) $topic_id . '
				ORDER BY day');
			while ($day = $this->db->sql_fetchrow($result))
			{
				$days[] = (int) $day['day'];
				$counts[] = (int) $day['posts'];
			}
			$this->db->sql_freeresult($result);

			$forum_entity = $this->groups()['forum_entity'];
			return [
				'topic'       => [
					'id'        => $topic_id,
					'title'     => $this->plain($row['topic_title']),
					'forum_id'  => (int) $row['forum_id'],
					'forum'     => $this->plain($row['forum_name']),
					'entity'    => isset($forum_entity[(int) $row['forum_id']]) ? $forum_entity[(int) $row['forum_id']] : null,
					'poster_id' => (int) $row['topic_poster'],
					'poster'    => $this->plain($row['poster_name']),
					'day'       => (int) $row['topic_day'],
					'views'     => (int) $row['views'],
					'replies'   => (int) $row['replies'],
				],
				'posts'       => ['d' => $days, 'v' => $counts],
			];
		});
	}

	/**
	 * The topic search list (T0), columnar. Sorted by title or by reply count
	 * client-side (SPEC section 15, 6b).
	 */
	public function topics()
	{
		return $this->cached('topics', function () {
			$out = ['id' => [], 'title' => [], 'forum_id' => [], 'replies' => []];
			$result = $this->db->sql_query('SELECT topic_id, topic_title, forum_id, replies FROM ' . $this->table('dim_topic') . '
				WHERE ' . $this->readable_sql('forum_id') . '
				ORDER BY topic_id');
			while ($row = $this->db->sql_fetchrow($result))
			{
				$out['id'][] = (int) $row['topic_id'];
				$out['title'][] = $this->plain($row['topic_title']);
				$out['forum_id'][] = (int) $row['forum_id'];
				$out['replies'][] = (int) $row['replies'];
			}
			$this->db->sql_freeresult($result);
			return $out;
		});
	}

	/**
	 * The user search list (U0), columnar, by name. Bots and the anonymous
	 * account are not people to look up.
	 */
	public function users()
	{
		return $this->cached('users', function () {
			$out = ['id' => [], 'name' => []];
			$result = $this->db->sql_query('SELECT user_id, username FROM ' . $this->table('dim_user') . '
				WHERE user_type <> ' . USER_IGNORE . '
				ORDER BY username_clean');
			while ($row = $this->db->sql_fetchrow($result))
			{
				$out['id'][] = (int) $row['user_id'];
				$out['name'][] = $this->plain($row['username']);
			}
			$this->db->sql_freeresult($result);
			return $out;
		});
	}

	/* ------------------------------------------------------------------ */
	/* Top-N queries                                                       */
	/* ------------------------------------------------------------------ */

	/**
	 * Views are a lifetime counter and cannot be windowed, so a range selects
	 * topics *started* in it (reframing C, SPEC section 8).
	 */
	private function top_viewed(array $where, $from, $to, $limit)
	{
		$where[] = 'topic_day BETWEEN ' . (int) $from . ' AND ' . (int) $to;
		$result = $this->db->sql_query_limit('SELECT topic_id, views AS n
			FROM ' . $this->table('dim_topic') . '
			WHERE ' . implode(' AND ', $where) . '
			ORDER BY views DESC, topic_id DESC', $limit);
		return $this->with_topics($this->fetch_ranked($result, 'topic_id'), 'views');
	}

	/**
	 * Posts per topic in the range - for a user, their own posts per topic.
	 */
	private function top_active(array $where, $scope, $from, $to, $limit)
	{
		$where[] = 'day BETWEEN ' . (int) $from . ' AND ' . (int) $to;
		$table = $scope === 'user' ? 'posts_daily_topic_user' : 'posts_daily_topic';
		$sql = 'SELECT topic_id, SUM(posts) AS n
			FROM ' . $this->table($table) . '
			WHERE ' . implode(' AND ', $where) . '
			GROUP BY topic_id
			ORDER BY n DESC, topic_id DESC';
		$result = $this->db->sql_query_limit($sql, $limit);
		return $this->with_topics($this->fetch_ranked($result, 'topic_id'), 'posts');
	}

	/** Likes received in the range, counted on the day they were given. */
	private function top_liked(array $where, $from, $to, $limit, $preview_length)
	{
		$where[] = 'day BETWEEN ' . (int) $from . ' AND ' . (int) $to;
		$result = $this->db->sql_query_limit('SELECT post_id, topic_id, poster_id, SUM(likes) AS n
			FROM ' . $this->table('likes_daily_post') . '
			WHERE ' . implode(' AND ', $where) . '
			GROUP BY post_id, topic_id, poster_id
			ORDER BY n DESC, post_id DESC', $limit);
		$rows = [];
		while ($row = $this->db->sql_fetchrow($result))
		{
			$rows[] = [
				'post_id'   => (int) $row['post_id'],
				'topic_id'  => (int) $row['topic_id'],
				'poster_id' => (int) $row['poster_id'],
				'likes'     => (int) $row['n'],
			];
		}
		$this->db->sql_freeresult($result);

		$rows = $this->with_topics($rows, null);
		$posts = $this->posts_for_preview(array_column($rows, 'post_id'));
		$names = $this->user_names(array_column($rows, 'poster_id'));

		foreach ($rows as &$row)
		{
			$post = isset($posts[$row['post_id']]) ? $posts[$row['post_id']] : null;
			$row['preview'] = $post ? $this->preview($post['post_text'], $preview_length) : null;

			// A deleted member's kept posts belong to ANONYMOUS; the name is on the post.
			if ($row['poster_id'] == ANONYMOUS && $post)
			{
				$row['poster'] = $this->plain($post['post_username']);
			}
			else
			{
				$row['poster'] = isset($names[$row['poster_id']]) ? $names[$row['poster_id']] : null;
			}
		}
		unset($row);
		return $rows;
	}

	/**
	 * A topic's most active users in the range, with the likes their posts
	 * there received in it (counted when given, like every likes figure).
	 */
	private function top_posters($topic_id, $from, $to, $limit)
	{
		$result = $this->db->sql_query_limit('SELECT user_id, SUM(posts) AS n
			FROM ' . $this->table('posts_daily_topic_user') . '
			WHERE topic_id = ' . (int) $topic_id . '
				AND day BETWEEN ' . (int) $from . ' AND ' . (int) $to . '
			GROUP BY user_id
			ORDER BY n DESC, user_id ASC', $limit);
		$rows = [];
		while ($row = $this->db->sql_fetchrow($result))
		{
			$rows[(int) $row['user_id']] = ['user_id' => (int) $row['user_id'], 'posts' => (int) $row['n'], 'likes' => 0];
		}
		$this->db->sql_freeresult($result);
		if (!$rows)
		{
			return [];
		}

		$result = $this->db->sql_query('SELECT poster_id, SUM(likes) AS n
			FROM ' . $this->table('likes_daily_post') . '
			WHERE topic_id = ' . (int) $topic_id . '
				AND day BETWEEN ' . (int) $from . ' AND ' . (int) $to . '
				AND ' . $this->db->sql_in_set('poster_id', array_keys($rows)) . '
			GROUP BY poster_id');
		while ($row = $this->db->sql_fetchrow($result))
		{
			$rows[(int) $row['poster_id']]['likes'] = (int) $row['n'];
		}
		$this->db->sql_freeresult($result);

		$names = $this->user_names(array_keys($rows));
		foreach ($rows as &$row)
		{
			$row['name'] = isset($names[$row['user_id']]) ? $names[$row['user_id']] : null;
		}
		unset($row);
		return array_values($rows);
	}

	/**
	 * A forum's members ranked by posts or by likes received on their posts
	 * there (F5, F6), each row carrying both. $forum_ids are the entity's
	 * forums the viewer can read; a like also needs its topic readable, as
	 * everywhere (SPEC section 5).
	 *
	 * @param int[]  $forum_ids
	 * @param string $by posts | likes
	 */
	private function top_members(array $forum_ids, $by, $from, $to, $limit)
	{
		if (!$forum_ids)
		{
			return [];
		}
		$days = 'day BETWEEN ' . (int) $from . ' AND ' . (int) $to;
		// [SELECT ... WHERE, the member column, the summed column]
		$posts = ['SELECT user_id, SUM(posts) AS n
			FROM ' . $this->table('posts_daily_user_forum') . '
			WHERE ' . $this->db->sql_in_set('forum_id', $forum_ids) . '
				AND ' . $days, 'user_id', 'posts'];
		$likes = ['SELECT poster_id AS user_id, SUM(likes) AS n
			FROM ' . $this->table('likes_daily_post') . '
			WHERE ' . $this->db->sql_in_set('forum_id', $forum_ids) . '
				AND ' . $this->readable_topics_sql() . '
				AND ' . $days, 'poster_id', 'likes'];
		list($rank, $other) = $by === 'likes' ? [$likes, $posts] : [$posts, $likes];
		$rank_column = $rank[2];
		$other_column = $other[2];

		$result = $this->db->sql_query_limit($rank[0] . '
			GROUP BY ' . $rank[1] . '
			HAVING SUM(' . $rank[2] . ') > 0
			ORDER BY n DESC, user_id ASC', $limit);
		$rows = [];
		while ($row = $this->db->sql_fetchrow($result))
		{
			$rows[(int) $row['user_id']] = ['user_id' => (int) $row['user_id'], $rank_column => (int) $row['n'], $other_column => 0];
		}
		$this->db->sql_freeresult($result);
		if (!$rows)
		{
			return [];
		}

		$result = $this->db->sql_query($other[0] . '
				AND ' . $this->db->sql_in_set($other[1], array_keys($rows)) . '
			GROUP BY ' . $other[1]);
		while ($row = $this->db->sql_fetchrow($result))
		{
			$rows[(int) $row['user_id']][$other_column] = (int) $row['n'];
		}
		$this->db->sql_freeresult($result);

		$names = $this->user_names(array_keys($rows));
		foreach ($rows as &$row)
		{
			$row['name'] = isset($names[$row['user_id']]) ? $names[$row['user_id']] : null;
		}
		unset($row);
		return array_values($rows);
	}

	private function fetch_ranked($result, $id_column)
	{
		$rows = [];
		while ($row = $this->db->sql_fetchrow($result))
		{
			$rows[] = [$id_column => (int) $row[$id_column], 'n' => (int) $row['n']];
		}
		$this->db->sql_freeresult($result);
		return $rows;
	}

	/**
	 * Adds title, forum and entity to rows carrying topic_id; renames the
	 * ranking column "n" to $value_name when given.
	 */
	private function with_topics(array $rows, $value_name)
	{
		if (!$rows)
		{
			return [];
		}

		$result = $this->db->sql_query($this->db->sql_build_query('SELECT', [
			'SELECT' => 't.topic_id, t.topic_title, t.forum_id, f.forum_name',
			'FROM'   => [$this->table('dim_topic') => 't', $this->table('dim_forum') => 'f'],
			'WHERE'  => $this->db->sql_in_set('t.topic_id', array_column($rows, 'topic_id')) . '
				AND f.forum_id = t.forum_id',
		]));
		$topics = [];
		while ($row = $this->db->sql_fetchrow($result))
		{
			$topics[(int) $row['topic_id']] = $row;
		}
		$this->db->sql_freeresult($result);

		$forum_entity = $this->groups()['forum_entity'];
		foreach ($rows as &$row)
		{
			$topic = isset($topics[$row['topic_id']]) ? $topics[$row['topic_id']] : null;
			$row['title'] = $topic ? $this->plain($topic['topic_title']) : null;
			$row['forum_id'] = $topic ? (int) $topic['forum_id'] : null;
			$row['forum'] = $topic ? $this->plain($topic['forum_name']) : null;
			$row['entity'] = ($topic && isset($forum_entity[(int) $topic['forum_id']])) ? $forum_entity[(int) $topic['forum_id']] : null;
			if ($value_name !== null)
			{
				$row[$value_name] = $row['n'];
				unset($row['n']);
			}
		}
		unset($row);
		return $rows;
	}

	private function user_names(array $user_ids)
	{
		if (!$user_ids)
		{
			return [];
		}
		$result = $this->db->sql_query('SELECT user_id, username FROM ' . $this->table('dim_user') . '
			WHERE ' . $this->db->sql_in_set('user_id', array_unique($user_ids)));
		$names = [];
		while ($row = $this->db->sql_fetchrow($result))
		{
			$names[(int) $row['user_id']] = $this->plain($row['username']);
		}
		$this->db->sql_freeresult($result);
		return $names;
	}

	/* ------------------------------------------------------------------ */
	/* Previews                                                            */
	/* ------------------------------------------------------------------ */

	/**
	 * post_text for the posts shown, in one query (the old app ran one per
	 * post). Only posts still in a forum the stats cover and the viewer can
	 * read: a post moved since the last build into an excluded or unreadable
	 * forum shows no text, and neither does one soft-deleted or unapproved
	 * since (a soft-deleted topic takes its posts with it).
	 */
	private function posts_for_preview(array $post_ids)
	{
		if (!$post_ids)
		{
			return [];
		}
		$where = [
			$this->db->sql_in_set('p.post_id', $post_ids),
			$this->readable_sql('p.forum_id'),
			'f.forum_id = p.forum_id',
			'p.post_visibility = ' . ITEM_APPROVED,
		];
		$result = $this->db->sql_query($this->db->sql_build_query('SELECT', [
			'SELECT' => 'p.post_id, p.post_text, p.post_username',
			'FROM'   => [$this->posts_table => 'p', $this->table('dim_forum') => 'f'],
			'WHERE'  => implode(' AND ', $where),
		]));
		$posts = [];
		while ($row = $this->db->sql_fetchrow($result))
		{
			$posts[(int) $row['post_id']] = $row;
		}
		$this->db->sql_freeresult($result);
		return $posts;
	}

	/**
	 * Plain-text excerpt: quotes removed ("..." in front when the post opened
	 * with one), formatting stripped, links elided, cut to $length characters.
	 */
	private function preview($xml, $length)
	{
		$full = $this->squash($this->text_utils->clean_formatting($xml));
		$body = $this->squash($this->text_utils->clean_formatting($this->text_utils->remove_bbcode($xml, 'quote')));

		$prefix = (utf8_substr($full, 0, 10) !== utf8_substr($body, 0, 10)) ? '...' : '';

		$body = $this->squash(preg_replace('#https?://\S+#u', ' ' . $this->language->lang('STATCHARTS_LINK_ELIDED') . ' ', $body));
		if (utf8_strlen($body) > $length)
		{
			$body = utf8_substr($body, 0, $length) . '...';
		}
		return $prefix . $body;
	}

	private function squash($text)
	{
		return trim(preg_replace('/\s+/u', ' ', $text));
	}

	/* ------------------------------------------------------------------ */
	/* Series, grouping, cache                                             */
	/* ------------------------------------------------------------------ */

	/**
	 * Runs a query yielding (day, forum_id, n) and sums it per entity.
	 *
	 * @return array [entity id => ['d' => [days ascending], 'v' => [values]]]
	 */
	private function entity_series($sql)
	{
		$map = $this->groups()['forum_entity'];
		$sums = [];
		$result = $this->db->sql_query($sql);
		while ($row = $this->db->sql_fetchrow($result))
		{
			$forum_id = (int) $row['forum_id'];
			if (!isset($map[$forum_id]))
			{
				continue;
			}
			$day = (int) $row['day'];
			$entity = $map[$forum_id];
			$sums[$entity][$day] = (isset($sums[$entity][$day]) ? $sums[$entity][$day] : 0) + (int) $row['n'];
		}
		$this->db->sql_freeresult($result);

		$out = [];
		foreach ($sums as $entity => $days)
		{
			ksort($days);
			$out[$entity] = ['d' => array_keys($days), 'v' => array_values($days)];
		}
		return $out;
	}

	private function users_series()
	{
		$out = ['d' => [], 'registered' => [], 'registered_active' => [], 'active_7d' => []];
		$result = $this->db->sql_query('SELECT day, registered, registered_active, active_7d
			FROM ' . $this->table('users_daily') . '
			ORDER BY day');
		while ($row = $this->db->sql_fetchrow($result))
		{
			$out['d'][] = (int) $row['day'];
			$out['registered'][] = (int) $row['registered'];
			$out['registered_active'][] = (int) $row['registered_active'];
			$out['active_7d'][] = (int) $row['active_7d'];
		}
		$this->db->sql_freeresult($result);
		return $out;
	}

	/**
	 * The grouping as this viewer sees it. An entity is shown only if the
	 * viewer can list the forum it is named after and at least one postable
	 * forum in it; its members are narrowed to forums they can list. Every
	 * other forum maps to the hidden entity 0, which carries no name and no
	 * member ids, so the numbers still add up to the board total.
	 */
	private function groups()
	{
		if ($this->groups !== null)
		{
			return $this->groups;
		}

		// The build's forum snapshot, cached per build. Not phpBB's SQL cache: its
		// key is the query, and the query names the same table again two builds on.
		$meta = $this->meta();
		$key = '_statcharts_dim_forum_' . ($meta ? $meta['built_at'] : 0);
		$forums = $this->cache->get($key);
		if ($forums === false)
		{
			$result = $this->db->sql_query('SELECT forum_id, parent_id, forum_type, left_id, forum_name FROM ' . $this->table('dim_forum'));
			$forums = $this->db->sql_fetchrowset($result);
			$this->db->sql_freeresult($result);
			$this->cache->put($key, $forums, self::CACHE_TTL);
		}
		$all = $this->grouping->resolve($forums, $this->grouping_mode(), $this->colours->order());

		$postable = [];
		foreach ($forums as $forum)
		{
			$postable[(int) $forum['forum_id']] = (int) $forum['forum_type'] === FORUM_POST;
		}

		$entities = [];
		foreach ($all['entities'] as $entity)
		{
			$members = array_values(array_filter($entity['forums'], function ($forum_id) {
				return isset($this->listable[$forum_id]);
			}));
			$has_postable = (bool) array_filter($members, function ($forum_id) use ($postable) {
				return $postable[$forum_id];
			});
			if (isset($this->listable[$entity['id']]) && $has_postable)
			{
				$entity['forums'] = $members;
				$entities[$entity['id']] = $entity;
			}
		}

		$map = [];
		$hidden = false;
		foreach ($all['forum_entity'] as $forum_id => $entity_id)
		{
			if (isset($entities[$entity_id]) && isset($this->listable[$forum_id]))
			{
				$map[$forum_id] = $entity_id;
			}
			else
			{
				$map[$forum_id] = 0;
				$hidden = $hidden || $postable[$forum_id];
			}
		}

		$entities = array_values($entities);
		if ($hidden)
		{
			$entities[] = ['id' => 0, 'name' => null, 'type' => 'hidden', 'forums' => []];
		}

		return $this->groups = ['entities' => $entities, 'forum_entity' => $map];
	}

	/**
	 * @return int[]|null Forum ids of an entity the viewer can read, or null if
	 *                    the entity does not exist for them (including hidden, 0)
	 */
	private function entity_forums($entity_id)
	{
		foreach ($this->groups()['entities'] as $entity)
		{
			if ($entity['id'] === $entity_id && $entity_id !== 0)
			{
				return array_values(array_filter($entity['forums'], function ($forum_id) {
					return isset($this->readable[$forum_id]);
				}));
			}
		}
		return null;
	}

	private function readable_sql($column)
	{
		return $this->db->sql_in_set($column, array_keys($this->readable), false, true);
	}

	/** For tables that carry a topic but no forum. */
	private function readable_topics_sql()
	{
		return 'topic_id IN (SELECT topic_id FROM ' . $this->table('dim_topic') . ' WHERE ' . $this->readable_sql('forum_id') . ')';
	}

	private function topic_readable($topic_id)
	{
		$result = $this->db->sql_query('SELECT forum_id FROM ' . $this->table('dim_topic') . ' WHERE topic_id = ' . (int) $topic_id);
		$forum_id = $this->db->sql_fetchfield('forum_id');
		$this->db->sql_freeresult($result);
		return $forum_id !== false && isset($this->readable[(int) $forum_id]);
	}

	private function grouping_mode()
	{
		$mode = (string) $this->config['statcharts_grouping'];
		return in_array($mode, grouping::MODES, true) ? $mode : grouping::TOP_LEVEL;
	}

	/** The configured start date as a day number, or 0 when unset or invalid. */
	private function configured_start_day()
	{
		$date = \DateTime::createFromFormat('!Y-m-d', (string) $this->config['statcharts_start_date'], new \DateTimeZone('UTC'));
		return $date ? (int) ($date->getTimestamp() / 86400) : 0;
	}

	/**
	 * Cached until the next rebuild. The key carries the build time, every
	 * setting that shapes a result, and the viewer's forum visibility - so
	 * each permission set (guests, members, moderators...) has its own entries,
	 * and a rebuild or a settings change simply stops matching old ones.
	 */
	private function cached($key, callable $compute)
	{
		$meta = $this->meta();
		$full_key = '_statcharts_' . hash('sha256', implode('|', [
			$meta['built_at'],
			$this->grouping_mode(),
			implode(',', $this->colours->order()),
			$meta['start_day'],
			$this->language->get_used_language(),
			implode(',', array_keys($this->listable)),
			implode(',', array_keys($this->readable)),
			$key,
		]));

		$value = $this->cache->get($full_key);
		if ($value === false)
		{
			$value = $compute();
			$this->cache->put($full_key, $value, self::CACHE_TTL);
		}
		return $value;
	}

	/** A table of the live set (service/tables.php). */
	private function table($name)
	{
		return $this->tables->live($name);
	}

	private function plain($text)
	{
		return html_entity_decode((string) $text, ENT_QUOTES, 'UTF-8');
	}
}
