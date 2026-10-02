<?php
/**
 * Stat Charts extension for phpBB.
 *
 * @copyright (c) 2026, biochaos
 * @license GNU General Public License, version 2 (GPL-2.0)
 */

namespace biochaos\statcharts\service;

use phpbb\config\config;
use phpbb\config\db_text;
use phpbb\db\driver\driver_interface;
use phpbb\db\tools\tools_interface;

/**
 * Full rebuild of the stats tables from the forum (SPEC section 5).
 *
 * Builds into the idle one of two sets of tables and then makes it live by
 * flipping one config value (service/tables.php), so readers never see a
 * half-built set; portable SQL throughout. Work is split into units (one step,
 * or one post_id chunk of a chunked step); run() does units until its time
 * budget is spent, persists where it got to, and returns. Each unit commits
 * together with the state that records it, so a run killed at any point
 * resumes exactly where the last committed unit ended.
 */
class rebuild
{
	const STEP_CREATE      = 0;
	const STEP_DIMS        = 1;
	const STEP_TOPICS      = 2;
	const STEP_POSTS       = 3;
	const STEP_POSTS_MERGE = 4;
	const STEP_LIKES       = 5;
	const STEP_LIKES_MERGE = 6;
	const STEP_USERS       = 7;
	const STEP_TOTALS      = 8;
	const STEP_SWAP        = 9;

	const STEP_NAMES = [
		self::STEP_CREATE      => 'create',
		self::STEP_DIMS        => 'dimensions',
		self::STEP_TOPICS      => 'topics',
		self::STEP_POSTS       => 'posts',
		self::STEP_POSTS_MERGE => 'merge_posts',
		self::STEP_LIKES       => 'likes',
		self::STEP_LIKES_MERGE => 'merge_likes',
		self::STEP_USERS       => 'users',
		self::STEP_TOTALS      => 'totals',
		self::STEP_SWAP        => 'swap',
	];

	/** post_id range per chunk. Ranges, not row counts: id gaps cost nothing. */
	const CHUNK = 20000;

	/**
	 * Days per merge unit: staged partial groups are summed a day range at a
	 * time, so no unit grows with the size of the board.
	 */
	const MERGE_DAYS = 120;

	/** A lock whose heartbeat is older than this belongs to a dead run. */
	const LOCK_STALE = 600;

	const RESULT_DONE    = 'done';
	const RESULT_PARTIAL = 'partial';
	const RESULT_LOCKED  = 'locked';

	/** @var driver_interface */
	private $db;

	/** @var config */
	private $config;

	/** @var db_text */
	private $config_text;

	/** @var tools_interface */
	private $db_tools;

	/** @var string */
	private $table_prefix;

	/** @var string */
	private $forums_table;

	/** @var string */
	private $posts_table;

	/** @var string */
	private $topics_table;

	/** @var string */
	private $users_table;

	/** @var string */
	private $thanks_table;

	/** @var forums */
	private $forums;

	/** @var colours */
	private $colours;

	/** @var tables */
	private $tables;

	/** @var string|null Value we wrote into the lock, while we hold it */
	private $lock_value;

	/** @var array State of the current build, as read from the database */
	private $state;

	/** @var array Parameters frozen at build start */
	private $params;

	public function __construct(
		driver_interface $db,
		config $config,
		db_text $config_text,
		tools_interface $db_tools,
		$table_prefix,
		$forums_table,
		$posts_table,
		$topics_table,
		$users_table,
		forums $forums,
		colours $colours,
		tables $tables
	)
	{
		$this->forums       = $forums;
		$this->colours      = $colours;
		$this->tables       = $tables;
		$this->db           = $db;
		$this->config       = $config;
		$this->config_text  = $config_text;
		$this->db_tools     = $db_tools;
		$this->table_prefix = $table_prefix;
		$this->forums_table = $forums_table;
		$this->posts_table  = $posts_table;
		$this->topics_table = $topics_table;
		$this->users_table  = $users_table;
		$this->thanks_table = $table_prefix . 'thanks';
	}

	/**
	 * Advance the rebuild, starting a new one if none is in progress.
	 *
	 * @param float         $budget   Seconds to spend; 0 runs to completion
	 * @param callable|null $progress Called after each unit with (step, step name, cursor, max post id)
	 * @param bool          $restart  Discard a build in progress and start over
	 * @return string One of the RESULT_* constants
	 */
	public function run($budget, callable $progress = null, $restart = false)
	{
		if (!$this->acquire_lock())
		{
			return self::RESULT_LOCKED;
		}

		try
		{
			$deadline = $budget > 0 ? microtime(true) + $budget : INF;

			$this->load_state();
			if ($restart || $this->state['state'] !== 'building' || !$this->params_still_valid())
			{
				$this->start();
			}

			do
			{
				$finished = $this->run_unit();
				$this->heartbeat();

				if ($progress)
				{
					list($done, $total, $unit) = $this->position();
					$progress($this->state['step'], self::STEP_NAMES[$this->state['step']], $done, $total, $unit);
				}
			}
			while (!$finished && microtime(true) < $deadline);

			return $finished ? self::RESULT_DONE : self::RESULT_PARTIAL;
		}
		finally
		{
			$this->release_lock();
		}
	}

	/**
	 * Where the rebuild stands, read fresh from the database (another process
	 * may be running it): for the ACP status panel and its progress bar.
	 *
	 * @return array state, step, step_name, steps, fraction (of the current step), started, last, running
	 */
	public function status()
	{
		$this->load_state();
		$lock = (string) $this->read_config_value('statcharts_rebuild_lock');
		$heartbeat = (int) explode(' ', $lock)[0];
		list($done, $total) = $this->params ? $this->position() : [0, 0];
		return [
			'state'       => $this->state['state'],
			'step'        => $this->state['step'],
			'step_name'   => self::STEP_NAMES[$this->state['step']],
			'steps'       => count(self::STEP_NAMES),
			'fraction'    => $total ? min(1, max(0, $done / $total)) : 0,
			'started'     => (int) $this->read_config_value('statcharts_rebuild_started'),
			'last'        => (int) $this->read_config_value('statcharts_rebuild_last'),
			'running'     => $lock !== '0' && $heartbeat + self::LOCK_STALE >= time(),
		];
	}

	/**
	 * How far the current step is: posts for the chunked steps, days for the
	 * merge steps.
	 *
	 * @return array [done, total, unit "post" | "day"]
	 */
	private function position()
	{
		$step = $this->state['step'];
		if ($step === self::STEP_POSTS_MERGE || $step === self::STEP_LIKES_MERGE)
		{
			$from = isset($this->params['merge_from']) ? (int) $this->params['merge_from'] : 0;
			return [$this->state['cursor'] - $from, $this->params['as_of_day'] + 1 - $from, 'day'];
		}
		if ($step === self::STEP_POSTS || $step === self::STEP_LIKES)
		{
			return [$this->state['cursor'], (int) $this->params['max_post_id'], 'post'];
		}
		return [0, 0, 'post'];
	}

	/**
	 * @return bool Whether a build is in progress (started, not yet swapped in)
	 */
	public function is_building()
	{
		return $this->config['statcharts_rebuild_state'] === 'building';
	}

	/* ------------------------------------------------------------------ */
	/* Locking                                                             */
	/* ------------------------------------------------------------------ */

	/**
	 * Our own lock rather than phpbb\lock\db: that one only expires after an
	 * hour and cannot be refreshed, while ours carries a heartbeat updated after
	 * every unit, so a dead run is taken over after LOCK_STALE seconds and a
	 * live one, however long, never is. Read from the database, not from
	 * $this->config, which was loaded before the other process last wrote it.
	 */
	private function acquire_lock()
	{
		$current = $this->read_config_value('statcharts_rebuild_lock');
		if ($current !== '0' && $current !== null)
		{
			$heartbeat = (int) explode(' ', $current)[0];
			if ($heartbeat + self::LOCK_STALE >= time())
			{
				return false;
			}
		}

		$value = time() . ' ' . unique_id();
		if (!$this->config->set_atomic('statcharts_rebuild_lock', $current, $value, false))
		{
			return false;
		}

		$this->lock_value = $value;
		return true;
	}

	private function heartbeat()
	{
		$value = time() . ' ' . explode(' ', $this->lock_value)[1];
		if ($value === $this->lock_value)
		{
			return;
		}

		if (!$this->config->set_atomic('statcharts_rebuild_lock', $this->lock_value, $value, false))
		{
			$this->lock_value = null;
			throw new \RuntimeException('statcharts: rebuild lock was taken over by another process');
		}
		$this->lock_value = $value;
	}

	private function release_lock()
	{
		if ($this->lock_value !== null)
		{
			$this->config->set_atomic('statcharts_rebuild_lock', $this->lock_value, '0', false);
			$this->lock_value = null;
		}
	}

	/* ------------------------------------------------------------------ */
	/* State                                                               */
	/* ------------------------------------------------------------------ */

	private function load_state()
	{
		$this->state = [
			'state'  => (string) $this->read_config_value('statcharts_rebuild_state'),
			'step'   => (int) $this->read_config_value('statcharts_rebuild_step'),
			'cursor' => (int) $this->read_config_value('statcharts_rebuild_cursor'),
		];

		$params = json_decode((string) $this->config_text->get('statcharts_rebuild_params'), true);
		$this->params = is_array($params) ? $params : null;
	}

	private function set_state($step, $cursor)
	{
		$this->config->set('statcharts_rebuild_step', $step, false);
		$this->config->set('statcharts_rebuild_cursor', $cursor, false);
		$this->state['step'] = $step;
		$this->state['cursor'] = $cursor;
	}

	/**
	 * A build must finish with the settings it started with. If the forum set,
	 * the timezone or an ETL setting changed underneath it, what is already
	 * built is wrong.
	 */
	private function params_still_valid()
	{
		if ($this->params === null)
		{
			return false;
		}

		list($allowed, $excluded) = $this->forums->sets();
		return isset($this->params['set']) && $this->params['set'] !== $this->tables->live_set()
			&& $this->params['timezone'] === $this->board_timezone()
			&& $this->params['allowed'] === $allowed
			&& $this->params['excluded'] === $excluded
			&& $this->params['count_anonymous'] === $this->count_anonymous();
	}

	private function start()
	{
		$now = time();
		$timezone = $this->board_timezone();
		list($allowed, $excluded) = $this->forums->sets();

		$sql = 'SELECT MAX(post_id) AS max_id FROM ' . $this->posts_table;
		$result = $this->db->sql_query($sql);
		$max_post_id = (int) $this->db->sql_fetchfield('max_id');
		$this->db->sql_freeresult($result);

		$tz = new \DateTimeZone($timezone);
		$offset_now = (new \DateTime('@' . $now))->setTimezone($tz)->getOffset();

		$this->params = [
			'started'     => $now,
			'timezone'    => $timezone,
			'periods'     => $this->utc_offset_periods($tz, $now + 2 * 86400),
			'as_of_day'   => (int) floor(($now + $offset_now) / 86400),
			'allowed'     => $allowed,
			'excluded'    => $excluded,
			'max_post_id' => $max_post_id,
			'has_thanks'  => $this->db_tools->sql_table_exists($this->thanks_table),
			// The idle set, built into and then made live.
			'set'         => tables::other_set($this->tables->live_set()),

			'count_anonymous' => $this->count_anonymous(),
		];

		$this->config_text->set('statcharts_rebuild_params', json_encode($this->params));
		$this->config->set('statcharts_rebuild_started', $now, false);
		$this->config->set('statcharts_rebuild_state', 'building', false);
		$this->set_state(self::STEP_CREATE, 0);
		$this->state['state'] = 'building';
	}


	/**
	 * Whether posts without an account (guests, and deleted members whose posts
	 * were kept - phpBB stores both the same way) count as active users.
	 */
	private function count_anonymous()
	{
		return (bool) $this->config['statcharts_count_anonymous'];
	}

	private function board_timezone()
	{
		$timezone = (string) $this->config['board_timezone'];
		return in_array($timezone, \DateTimeZone::listIdentifiers(), true) ? $timezone : 'UTC';
	}

	/**
	 * The zone's UTC offset as a list of [from timestamp, offset seconds]
	 * periods, ascending, adjacent equal offsets merged. Asks PHP's own
	 * timezone database, which is always present - never MySQL's (rule 4).
	 */
	private function utc_offset_periods(\DateTimeZone $tz, $until)
	{
		$periods = [];
		foreach ($tz->getTransitions(0, $until) as $transition)
		{
			$offset = (int) $transition['offset'];
			if ($periods && $periods[count($periods) - 1][1] === $offset)
			{
				continue;
			}
			$periods[] = [(int) $transition['ts'], $offset];
		}
		return $periods;
	}

	/* ------------------------------------------------------------------ */
	/* Steps                                                               */
	/* ------------------------------------------------------------------ */

	/**
	 * @return bool True when the whole build has finished and been swapped in
	 */
	private function run_unit()
	{
		switch ($this->state['step'])
		{
			case self::STEP_CREATE:
				$this->step_create();
			break;

			case self::STEP_DIMS:
				$this->step_dims();
			break;

			case self::STEP_TOPICS:
				$this->step_topics();
			break;

			case self::STEP_POSTS:
				$this->step_posts();
			break;

			case self::STEP_POSTS_MERGE:
				$this->step_posts_merge();
			break;

			case self::STEP_LIKES:
				$this->step_likes();
			break;

			case self::STEP_LIKES_MERGE:
				$this->step_likes_merge();
			break;

			case self::STEP_USERS:
				$this->step_users();
			break;

			case self::STEP_TOTALS:
				$this->step_totals();
			break;

			case self::STEP_SWAP:
				$this->step_swap();
				return true;

			default:
				throw new \RuntimeException('statcharts: unknown rebuild step ' . $this->state['step']);
		}

		return false;
	}

	/**
	 * Empties the idle set - the previous build, kept until now so that a page
	 * mid-way through reading it when it was replaced could finish (SPEC
	 * section 5) - and the staging tables. TRUNCATE commits implicitly on MySQL,
	 * so this step is idempotent rather than transactional.
	 */
	private function step_create()
	{
		foreach (tables::names() as $name)
		{
			$this->empty_table($this->build($name));
		}
		$this->empty_staging();

		$this->set_state(self::STEP_DIMS, 0);
	}

	private function step_dims()
	{
		$this->transaction(function () {
			$this->clear(['dim_forum', 'dim_user']);

			$this->db->sql_query('INSERT INTO ' . $this->build('dim_forum') . '
				(forum_id, parent_id, forum_type, left_id, right_id, forum_name)
				SELECT forum_id, parent_id, forum_type, left_id, right_id, forum_name
				FROM ' . $this->forums_table . '
				WHERE ' . $this->allowed_sql('forum_id'));

			$this->db->sql_query('INSERT INTO ' . $this->build('dim_user') . '
				(user_id, user_type, username, username_clean, user_colour, reg_day)
				SELECT user_id, user_type, username, username_clean, user_colour, ' . $this->day_sql('user_regdate') . '
				FROM ' . $this->users_table);

			$this->set_state(self::STEP_TOPICS, 0);
		});
	}

	/**
	 * Shadow topics (move placeholders) are not topics: SPEC section 4. Only
	 * approved topics count: soft-deleted and unapproved ones are left out
	 * (SPEC section 15, post visibility).
	 */
	private function step_topics()
	{
		$this->transaction(function () {
			$this->clear(['dim_topic', 'topics_daily_forum']);

			$this->db->sql_query('INSERT INTO ' . $this->build('dim_topic') . '
				(topic_id, forum_id, topic_title, topic_poster, poster_name, topic_day, views, replies, visibility)
				SELECT topic_id, forum_id, topic_title, topic_poster, topic_first_poster_name, ' . $this->day_sql('topic_time') . ',
					topic_views, topic_posts_approved, topic_visibility
				FROM ' . $this->topics_table . '
				WHERE ' . $this->allowed_sql('forum_id') . '
					AND topic_moved_id = 0
					AND topic_visibility = ' . (int) ITEM_APPROVED);

			$this->db->sql_query('INSERT INTO ' . $this->build('topics_daily_forum') . '
				(day, forum_id, topics, views)
				SELECT topic_day, forum_id, COUNT(*), SUM(views)
				FROM ' . $this->build('dim_topic') . '
				GROUP BY topic_day, forum_id');

			$this->set_state(self::STEP_POSTS, 0);
		});
	}

	/**
	 * Posts matching $condition that may enter the stats. Both the post's forum
	 * and its topic's forum must be allowed: phpbb_posts.forum_id can disagree
	 * with the topic's (measured: 3 posts here), and exclusion must not leak
	 * through whichever of the two is stale. Only approved posts in approved
	 * topics count (SPEC section 15, post visibility).
	 */
	private function posts_from($condition)
	{
		return "{$this->posts_table} p
			JOIN {$this->topics_table} tp ON tp.topic_id = p.topic_id
			WHERE $condition
				AND " . $this->visible_sql() . '
				AND ' . $this->allowed_sql('p.forum_id') . '
				AND ' . $this->allowed_sql('tp.forum_id');
	}

	/** Post p and its topic tp are approved: not unapproved, soft-deleted or awaiting reapproval. */
	private function visible_sql()
	{
		return 'p.post_visibility = ' . (int) ITEM_APPROVED . ' AND tp.topic_visibility = ' . (int) ITEM_APPROVED;
	}

	/**
	 * All four post rollups in one pass per chunk, so each post_id range is
	 * read once. Chunks produce partial groups, merged by summing on the key.
	 * Forum rollups use the post's forum_id, as the oracle does; the topic
	 * rollup carries the topic's current forum (SPEC section 4).
	 */
	private function step_posts()
	{
		$lo = $this->state['cursor'];
		if ($lo >= $this->params['max_post_id'])
		{
			$this->start_merge(self::STEP_POSTS_MERGE, ['posts_daily_forum']);
			return;
		}
		$hi = min($lo + self::CHUNK, $this->params['max_post_id']);

		$this->transaction(function () use ($lo, $hi) {
			$from = $this->posts_from('p.post_id > ' . (int) $lo . ' AND p.post_id <= ' . (int) $hi);
			$day = $this->day_sql('p.post_time');

			$this->stage('posts_daily_forum', ['day', 'forum_id', 'posts'],
				"SELECT $day, p.forum_id, COUNT(*)
				FROM $from
				GROUP BY $day, p.forum_id");

			$this->stage('posts_daily_user_forum', ['day', 'user_id', 'forum_id', 'posts'],
				"SELECT $day, p.poster_id, p.forum_id, COUNT(*)
				FROM $from
				GROUP BY $day, p.poster_id, p.forum_id");

			$this->stage('posts_daily_topic', ['day', 'topic_id', 'forum_id', 'posts'],
				"SELECT $day, p.topic_id, tp.forum_id, COUNT(*)
				FROM $from
				GROUP BY $day, p.topic_id, tp.forum_id");

			$this->stage('posts_daily_topic_user', ['day', 'topic_id', 'user_id', 'posts'],
				"SELECT $day, p.topic_id, p.poster_id, COUNT(*)
				FROM $from
				GROUP BY $day, p.topic_id, p.poster_id");

			$this->set_state(self::STEP_POSTS, $hi);
		});
	}

	/** Sums the posts' staged partial groups into the build set, a day range per unit. */
	private function step_posts_merge()
	{
		$lo = $this->state['cursor'];
		if ($lo > $this->params['as_of_day'])
		{
			$this->set_state(self::STEP_LIKES, 0);
			return;
		}
		$hi = $lo + self::MERGE_DAYS;

		$this->transaction(function () use ($lo, $hi) {
			$this->consolidate('posts_daily_forum', ['day', 'forum_id'], 'posts', $lo, $hi);
			$this->consolidate('posts_daily_user_forum', ['day', 'user_id', 'forum_id'], 'posts', $lo, $hi);
			$this->consolidate('posts_daily_topic', ['day', 'topic_id', 'forum_id'], 'posts', $lo, $hi);
			$this->consolidate('posts_daily_topic_user', ['day', 'topic_id', 'user_id'], 'posts', $lo, $hi);
			$this->set_state(self::STEP_POSTS_MERGE, $hi);
		});
	}

	/**
	 * Likes take forum, topic and poster from the post, never from the copies
	 * phpbb_thanks keeps, which go stale on topic moves (ETL rule 1). Bucketed
	 * by when the like was given. A like counts only if its post does.
	 */
	private function step_likes()
	{
		if (!$this->params['has_thanks'])
		{
			$this->set_state(self::STEP_USERS, 0);
			return;
		}

		$lo = $this->state['cursor'];
		if ($lo >= $this->params['max_post_id'])
		{
			$this->start_merge(self::STEP_LIKES_MERGE, ['likes_daily_post']);
			return;
		}
		$hi = min($lo + self::CHUNK, $this->params['max_post_id']);

		$this->transaction(function () use ($lo, $hi) {
			$from = "{$this->thanks_table} t
				JOIN {$this->posts_table} p ON p.post_id = t.post_id
				JOIN {$this->topics_table} tp ON tp.topic_id = p.topic_id
				WHERE " . $this->visible_sql() . '
					AND ' . $this->allowed_sql('p.forum_id') . '
					AND ' . $this->allowed_sql('tp.forum_id') . '
					AND t.post_id > ' . (int) $lo . ' AND t.post_id <= ' . (int) $hi;

			$day = $this->day_sql('t.thanks_time');
			$this->stage('likes_daily_post', ['day', 'post_id', 'topic_id', 'forum_id', 'poster_id', 'likes'],
				"SELECT $day, p.post_id, p.topic_id, p.forum_id, p.poster_id, COUNT(*)
				FROM $from
				GROUP BY $day, p.post_id, p.topic_id, p.forum_id, p.poster_id");

			$this->set_state(self::STEP_LIKES, $hi);
		});
	}

	/**
	 * Sums the likes' staged partial groups into the build set, a day range per
	 * unit, then the per-forum likes from the finished per-post ones.
	 */
	private function step_likes_merge()
	{
		$lo = $this->state['cursor'];
		if ($lo > $this->params['as_of_day'])
		{
			$this->transaction(function () {
				$this->clear(['likes_daily_forum']);
				$this->db->sql_query('INSERT INTO ' . $this->build('likes_daily_forum') . '
					(day, forum_id, likes)
					SELECT day, forum_id, SUM(likes)
					FROM ' . $this->build('likes_daily_post') . '
					GROUP BY day, forum_id');
				$this->set_state(self::STEP_USERS, 0);
			});
			return;
		}
		$hi = $lo + self::MERGE_DAYS;

		$this->transaction(function () use ($lo, $hi) {
			$this->consolidate('likes_daily_post', ['day', 'post_id', 'topic_id', 'forum_id', 'poster_id'], 'likes', $lo, $hi);
			$this->set_state(self::STEP_LIKES_MERGE, $hi);
		});
	}

	/**
	 * Enters a merge step at the first staged day: no empty ranges before it,
	 * and "merge_from" gives the progress bar its start.
	 *
	 * @param string[] $staged Staging tables that hold every staged day of the step
	 */
	private function start_merge($step, array $staged)
	{
		$first = null;
		foreach ($staged as $name)
		{
			$result = $this->db->sql_query('SELECT MIN(day) AS first_day FROM ' . $this->tables->stage($name));
			$day = $this->db->sql_fetchfield('first_day');
			$this->db->sql_freeresult($result);
			if ($day !== null && $day !== false && ($first === null || (int) $day < $first))
			{
				$first = (int) $day;
			}
		}
		// Nothing staged: straight past the end.
		$first = $first === null ? $this->params['as_of_day'] + 1 : $first;

		$this->params['merge_from'] = $first;
		$this->config_text->set('statcharts_rebuild_params', json_encode($this->params));
		$this->set_state($step, $first);
	}

	/**
	 * Registrations per day, plus the rolling 7-day active count: for each day
	 * D, distinct people who posted in [D-6, D]. A rolling distinct count cannot
	 * be summed from daily counts, so it is computed here, from the finished
	 * user rollup (SPEC section 15, 6a).
	 *
	 * A person is a user id, or - for posts without an account, when counted -
	 * a (ANONYMOUS, post_username) pair: the anonymous account itself is never
	 * one person. Those names come from phpbb_posts, because the user rollup
	 * has already folded them all into ANONYMOUS.
	 *
	 * Bots and the anonymous account (USER_IGNORE) are not members, so they are
	 * not registrations either (SPEC section 15).
	 */
	private function step_users()
	{
		$this->transaction(function () {
			$this->clear(['users_daily']);

			$reg_day = $this->day_sql('user_regdate');
			$members = 'user_type <> ' . (int) USER_IGNORE;
			$registrations = 'SELECT ' . $reg_day . ' AS day, COUNT(*) AS registered,
					SUM(CASE WHEN user_posts > 0 THEN 1 ELSE 0 END) AS registered_active, 0 AS active_7d
				FROM ' . $this->users_table . '
				WHERE ' . $members . '
				GROUP BY ' . $reg_day;

			$days = 'SELECT 0 AS n UNION ALL SELECT 1 UNION ALL SELECT 2 UNION ALL SELECT 3
				UNION ALL SELECT 4 UNION ALL SELECT 5 UNION ALL SELECT 6';

			$people = "SELECT DISTINCT user_id, '' AS name, day
				FROM " . $this->build('posts_daily_user_forum') . '
				WHERE user_id <> ' . ANONYMOUS;

			if ($this->params['count_anonymous'])
			{
				$people .= '
				UNION
				SELECT DISTINCT p.poster_id, p.post_username, ' . $this->day_sql('p.post_time') . '
				FROM ' . $this->posts_from('p.poster_id = ' . ANONYMOUS . ' AND p.post_id <= ' . (int) $this->params['max_post_id']);
			}

			// For each day, each person once: distinct (day, person) pairs, then counted.
			$active = 'SELECT a.day, 0 AS registered, 0 AS registered_active, COUNT(*) AS active_7d
				FROM (
					SELECT DISTINCT d.day + k.n AS day, d.user_id, d.name
					FROM (' . $people . ') d
					CROSS JOIN (' . $days . ') k
					WHERE d.day + k.n <= ' . (int) $this->params['as_of_day'] . '
				) a
				GROUP BY a.day';

			$this->db->sql_query('INSERT INTO ' . $this->build('users_daily') . '
				(day, registered, registered_active, active_7d)
				SELECT u.day, SUM(u.registered), SUM(u.registered_active), SUM(u.active_7d)
				FROM (' . $registrations . ' UNION ALL ' . $active . ') u
				GROUP BY u.day');

			$this->set_state(self::STEP_TOTALS, 0);
		});
	}

	private function step_totals()
	{
		$this->transaction(function () {
			$this->clear(['totals_forum', 'totals_user', 'meta']);

			// A topic's post count is the posts counted, not phpBB's topic_posts_approved:
			// that counter can drift (measured: topic 6 here says 506 of its 509), and
			// all-time lists would then disagree with ranged ones, which count posts.
			$topics = $this->build('dim_topic');
			$this->db->sql_query('UPDATE ' . $topics . '
				SET replies = COALESCE((
					SELECT SUM(p.posts) FROM ' . $this->build('posts_daily_topic') . ' p WHERE ' . $topics . '.topic_id = p.topic_id
				), 0)');

			$this->db->sql_query('INSERT INTO ' . $this->build('totals_forum') . '
				(forum_id, posts, topics, likes, views)
				SELECT f.forum_id, COALESCE(p.n, 0), COALESCE(t.n, 0), COALESCE(l.n, 0), COALESCE(t.v, 0)
				FROM ' . $this->build('dim_forum') . ' f
				LEFT JOIN (
					SELECT forum_id, SUM(posts) AS n FROM ' . $this->build('posts_daily_forum') . ' GROUP BY forum_id
				) p ON p.forum_id = f.forum_id
				LEFT JOIN (
					SELECT forum_id, COUNT(*) AS n, SUM(views) AS v FROM ' . $this->build('dim_topic') . ' GROUP BY forum_id
				) t ON t.forum_id = f.forum_id
				LEFT JOIN (
					SELECT forum_id, SUM(likes) AS n FROM ' . $this->build('likes_daily_forum') . ' GROUP BY forum_id
				) l ON l.forum_id = f.forum_id');

			$this->db->sql_query('INSERT INTO ' . $this->build('totals_user') . '
				(user_id, posts, likes)
				SELECT u.user_id, COALESCE(p.n, 0), COALESCE(l.n, 0)
				FROM ' . $this->build('dim_user') . ' u
				LEFT JOIN (
					SELECT user_id, SUM(posts) AS n FROM ' . $this->build('posts_daily_user_forum') . ' GROUP BY user_id
				) p ON p.user_id = u.user_id
				LEFT JOIN (
					SELECT poster_id, SUM(likes) AS n FROM ' . $this->build('likes_daily_post') . ' GROUP BY poster_id
				) l ON l.poster_id = u.user_id');

			$result = $this->db->sql_query('SELECT MIN(day) AS first_day, MAX(day) AS last_day FROM ' . $this->build('posts_daily_forum'));
			$range = $this->db->sql_fetchrow($result);
			$this->db->sql_freeresult($result);

			$meta = [
				'first_day'   => (int) $range['first_day'],
				'last_day'    => (int) $range['last_day'],
				'as_of_day'   => $this->params['as_of_day'],
				'started_at'  => $this->params['started'],
				'built_at'    => time(),
				'timezone'    => $this->params['timezone'],
				'excluded'    => implode(',', $this->params['excluded']),
				'has_likes'   => $this->params['has_thanks'] ? 1 : 0,
				'count_anonymous' => $this->params['count_anonymous'] ? 1 : 0,
				'max_post_id' => $this->params['max_post_id'],
			];
			$rows = [];
			foreach ($meta as $key => $value)
			{
				$rows[] = ['meta_key' => $key, 'meta_value' => (string) $value];
			}
			$this->db->sql_multi_insert($this->build('meta'), $rows);

			$this->set_state(self::STEP_SWAP, 0);
		});
	}

	/**
	 * Makes the built set live: one config value, so one atomic step. Safe to
	 * re-run. The set it replaces stays as it is until the next build empties
	 * it, so a page reading it at this moment still finishes on complete data.
	 */
	private function step_swap()
	{
		$this->config->set('statcharts_live_set', $this->params['set']);
		$this->config->set('statcharts_rebuild_last', time(), false);
		$this->empty_staging();
		$this->config->set('statcharts_rebuild_state', 'idle', false);
		$this->set_state(self::STEP_CREATE, 0);
		$this->state['state'] = 'idle';

		// New forums take a colour on first sight; deleted ones give theirs back
		// (SPEC section 7). Presentation data, so after the swap, outside the build.
		$this->colours->sync();
	}

	/* ------------------------------------------------------------------ */
	/* SQL helpers                                                         */
	/* ------------------------------------------------------------------ */

	/** A table of the set being built. */
	private function build($name)
	{
		return $this->tables->in_set($name, $this->params['set']);
	}

	private function transaction(callable $work)
	{
		$this->db->sql_transaction('begin');
		$work();
		$this->db->sql_transaction('commit');
	}

	/** Makes a transactional step safe to repeat even on a non-transactional engine. */
	private function clear(array $names)
	{
		foreach ($names as $name)
		{
			$this->db->sql_query('DELETE FROM ' . $this->build($name));
		}
	}

	/**
	 * One chunk's partial groups, into the staging table. Chunks overlap on
	 * their keys (the same day and forum in several post_id ranges), so the
	 * sums are made once at the end of the step (consolidate()).
	 *
	 * @param string[] $columns The staging table's columns, in $select's order
	 */
	private function stage($name, array $columns, $select)
	{
		$this->db->sql_query('INSERT INTO ' . $this->tables->stage($name) . ' (' . implode(', ', $columns) . ') ' . $select);
	}

	/**
	 * Sums a staging table's partial groups for days [$lo, $hi) into the build
	 * table. Every group has a day, so each range sums on its own. Runs in the
	 * unit's transaction with the cursor that records it, so a range is summed
	 * once or not at all; the staging tables are emptied after the swap, by a
	 * quick TRUNCATE outside any transaction.
	 */
	private function consolidate($name, array $keys, $sum_column, $lo, $hi)
	{
		$key_list = implode(', ', $keys);
		$in_range = 'day >= ' . (int) $lo . ' AND day < ' . (int) $hi;
		$this->db->sql_query('INSERT INTO ' . $this->build($name) . ' (' . $key_list . ', ' . $sum_column . ')
			SELECT ' . $key_list . ', SUM(' . $sum_column . ')
			FROM ' . $this->tables->stage($name) . '
			WHERE ' . $in_range . '
			GROUP BY ' . $key_list);
	}

	private function empty_staging()
	{
		foreach (tables::STAGED as $name)
		{
			$this->empty_table($this->tables->stage($name));
		}
	}

	/**
	 * TRUNCATE where there is one (it commits implicitly on MySQL, so never
	 * inside a transaction); SQLite has none, and optimises a bare DELETE the same way.
	 */
	private function empty_table($table)
	{
		$this->db->sql_query(($this->db->get_sql_layer() === 'sqlite3' ? 'DELETE FROM ' : 'TRUNCATE TABLE ') . $table);
	}

	private function allowed_sql($column)
	{
		return $this->db->sql_in_set($column, $this->params['allowed'], false, true);
	}

	/**
	 * Days since 1970-01-01 in the board's timezone, by integer arithmetic on
	 * the Unix timestamp (ETL rule 4). Never FROM_UNIXTIME, which silently uses
	 * the MySQL session's zone, nor CONVERT_TZ, which returns NULL where MySQL's
	 * timezone tables were never loaded. Periods are tested newest first,
	 * since most rows are recent.
	 *
	 * The one per-database expression. MySQL's "/" returns a decimal, hence
	 * FLOOR, and its unsigned columns would underflow with a negative offset,
	 * hence the cast; this form is kept exactly, as the oracle checks were made
	 * against it. PostgreSQL, SQLite and MS SQL divide integers by truncating,
	 * which equals FLOOR for these non-negative sums, and has no unsigned types.
	 */
	private function day_sql($column)
	{
		$periods = $this->params['periods'];

		if (count($periods) === 1)
		{
			$offset = (string) $periods[0][1];
		}
		else
		{
			$offset = 'CASE';
			for ($i = count($periods) - 1; $i >= 1; $i--)
			{
				$offset .= ' WHEN ' . $column . ' >= ' . $periods[$i][0] . ' THEN ' . $periods[$i][1];
			}
			$offset .= ' ELSE ' . $periods[0][1] . ' END';
		}

		if (strpos($this->db->get_sql_layer(), 'mysql') === 0)
		{
			return 'GREATEST(0, FLOOR((CAST(' . $column . ' AS SIGNED) + ' . $offset . ') / 86400))';
		}
		return '((' . $column . ' + ' . $offset . ') / 86400)';
	}

	private function read_config_value($name)
	{
		$sql = 'SELECT config_value FROM ' . CONFIG_TABLE . "
			WHERE config_name = '" . $this->db->sql_escape($name) . "'";
		$result = $this->db->sql_query($sql);
		$value = $this->db->sql_fetchfield('config_value');
		$this->db->sql_freeresult($result);
		return $value === false ? null : $value;
	}
}
