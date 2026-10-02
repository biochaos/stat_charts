<?php
/**
 * Stat Charts extension for phpBB.
 *
 * @copyright (c) 2026, biochaos
 * @license GNU General Public License, version 2 (GPL-2.0)
 */

namespace biochaos\statcharts\service;

use phpbb\config\config;

/**
 * The stats tables: their definitions, their names, and which set is live.
 *
 * There are two sets of the same tables (SPEC section 5, portable rebuild): set
 * "a" under the plain names, set "b" with a "_b" suffix. Config
 * statcharts_live_set names the live one; the rebuild fills the other and then
 * flips the pointer, so readers switch to a complete set in one atomic step,
 * with no DDL at all. Chunked steps collect partial groups in staging tables
 * ("stage_" prefix) and sum them into the set at the end of the step.
 *
 * All data in these tables is derived and rebuilt from the forum. Days are
 * integers: days since 1970-01-01 in the board's timezone (SPEC section 5,
 * rule 4); phpBB's schema tools have no DATE type.
 */
class tables
{
	const SETS = ['a', 'b'];

	/**
	 * Short codes for index names. SQLite (and Oracle) index names are global to
	 * the database and phpBB cuts them to 30 characters, falling back to the bare
	 * key name - so "day" on two tables collides. Every index is therefore named
	 * "sc" + set ("a", "b", or "s" for staging) + this code + "_" + key.
	 */
	const CODES = [
		'dim_forum'              => 'df',
		'dim_user'               => 'du',
		'dim_topic'              => 'dt',
		'topics_daily_forum'     => 'tdf',
		'posts_daily_forum'      => 'pdf',
		'likes_daily_forum'      => 'ldf',
		'posts_daily_user_forum' => 'pduf',
		'posts_daily_topic'      => 'pdt',
		'posts_daily_topic_user' => 'pdtu',
		'likes_daily_post'       => 'ldp',
		'users_daily'            => 'ud',
		'totals_forum'           => 'tf',
		'totals_user'            => 'tu',
		'meta'                   => 'm',
	];

	/** Tables whose rows the chunked steps merge from partial groups. */
	const STAGED = ['posts_daily_forum', 'posts_daily_user_forum', 'posts_daily_topic', 'posts_daily_topic_user', 'likes_daily_post'];

	/** @var config */
	private $config;

	/** @var string */
	private $table_prefix;

	public function __construct(config $config, $table_prefix)
	{
		$this->config = $config;
		$this->table_prefix = $table_prefix;
	}

	/** The live set's table. Config is loaded once per request, so a request never mixes sets. */
	public function live($name)
	{
		return $this->in_set($name, $this->live_set());
	}

	public function live_set()
	{
		return $this->config['statcharts_live_set'] === 'b' ? 'b' : 'a';
	}

	public static function other_set($set)
	{
		return $set === 'a' ? 'b' : 'a';
	}

	public function in_set($name, $set)
	{
		return self::name($this->table_prefix, $name, $set);
	}

	public function stage($name)
	{
		return self::stage_name($this->table_prefix, $name);
	}

	public static function name($table_prefix, $name, $set)
	{
		return $table_prefix . 'statcharts_' . $name . ($set === 'b' ? '_b' : '');
	}

	public static function stage_name($table_prefix, $name)
	{
		return $table_prefix . 'statcharts_stage_' . $name;
	}

	/**
	 * Every table of one set, as phpBB's schema tools want them: [name => definition].
	 */
	public static function set_schema($table_prefix, $set)
	{
		$out = [];
		foreach (self::definitions() as $name => $definition)
		{
			if (isset($definition['KEYS']))
			{
				$definition['KEYS'] = self::index_names($definition['KEYS'], $set, $name);
			}
			$out[self::name($table_prefix, $name, $set)] = $definition;
		}
		return $out;
	}

	/**
	 * The staging tables: the merged tables' columns, no unique key - partial
	 * groups repeat - and an index on day, as they are summed a day range at a time.
	 */
	public static function stage_schema($table_prefix)
	{
		$definitions = self::definitions();
		$out = [];
		foreach (self::STAGED as $name)
		{
			$out[self::stage_name($table_prefix, $name)] = [
				'COLUMNS' => $definitions[$name]['COLUMNS'],
				'KEYS'    => self::index_names(['day' => ['INDEX', 'day']], 's', $name),
			];
		}
		return $out;
	}

	/** Keys renamed to database-wide unique names (see CODES). */
	private static function index_names(array $keys, $set, $name)
	{
		$out = [];
		foreach ($keys as $key => $definition)
		{
			$out['sc' . $set . self::CODES[$name] . '_' . $key] = $definition;
		}
		return $out;
	}

	/** @return string[] Base names, in the order the schema defines them */
	public static function names()
	{
		return array_keys(self::definitions());
	}

	public static function definitions()
	{
		return [
			'dim_forum' => [
				'COLUMNS' => [
					'forum_id'   => ['UINT', 0],
					'parent_id'  => ['UINT', 0],
					'forum_type' => ['TINT:4', 0],
					'left_id'    => ['UINT', 0],
					'right_id'   => ['UINT', 0],
					'forum_name' => ['STEXT_UNI', ''],
				],
				'PRIMARY_KEY' => 'forum_id',
			],
			'dim_user' => [
				'COLUMNS' => [
					'user_id'        => ['UINT', 0],
					'user_type'      => ['TINT:2', 0],
					'username'       => ['VCHAR_CI', ''],
					'username_clean' => ['VCHAR_CI', ''],
					'user_colour'    => ['VCHAR:6', ''],
					'reg_day'        => ['UINT', 0],
				],
				'PRIMARY_KEY' => 'user_id',
				'KEYS' => [
					'username_clean' => ['INDEX', 'username_clean'],
				],
			],
			'dim_topic' => [
				'COLUMNS' => [
					'topic_id'     => ['UINT', 0],
					'forum_id'     => ['UINT', 0],
					'topic_title'  => ['STEXT_UNI', ''],
					'topic_poster' => ['UINT', 0],
					'poster_name'  => ['VCHAR_UNI', ''],
					'topic_day'    => ['UINT', 0],
					'views'        => ['UINT:10', 0],
					'replies'      => ['UINT:10', 0],
					'visibility'   => ['TINT:3', 0],
				],
				'PRIMARY_KEY' => 'topic_id',
				'KEYS' => [
					'day_views' => ['INDEX', ['topic_day', 'views']],
					'forum_day' => ['INDEX', ['forum_id', 'topic_day']],
				],
			],
			'topics_daily_forum' => [
				'COLUMNS' => [
					'day'      => ['UINT', 0],
					'forum_id' => ['UINT', 0],
					'topics'   => ['UINT:10', 0],
					'views'    => ['BINT', 0],
				],
				'PRIMARY_KEY' => ['day', 'forum_id'],
			],
			'posts_daily_forum' => [
				'COLUMNS' => [
					'day'      => ['UINT', 0],
					'forum_id' => ['UINT', 0],
					'posts'    => ['UINT:10', 0],
				],
				'PRIMARY_KEY' => ['day', 'forum_id'],
			],
			'likes_daily_forum' => [
				'COLUMNS' => [
					'day'      => ['UINT', 0],
					'forum_id' => ['UINT', 0],
					'likes'    => ['UINT:10', 0],
				],
				'PRIMARY_KEY' => ['day', 'forum_id'],
			],
			'posts_daily_user_forum' => [
				'COLUMNS' => [
					'day'      => ['UINT', 0],
					'user_id'  => ['UINT', 0],
					'forum_id' => ['UINT', 0],
					'posts'    => ['UINT:10', 0],
				],
				'PRIMARY_KEY' => ['user_id', 'day', 'forum_id'],
			],
			'posts_daily_topic' => [
				'COLUMNS' => [
					'day'      => ['UINT', 0],
					'topic_id' => ['UINT', 0],
					'forum_id' => ['UINT', 0],
					'posts'    => ['UINT:10', 0],
				],
				'PRIMARY_KEY' => ['topic_id', 'day'],
				'KEYS' => [
					'day'       => ['INDEX', 'day'],
					'forum_day' => ['INDEX', ['forum_id', 'day']],
				],
			],
			// A topic's members and a member's topics, over any range.
			'posts_daily_topic_user' => [
				'COLUMNS' => [
					'day'      => ['UINT', 0],
					'topic_id' => ['UINT', 0],
					'user_id'  => ['UINT', 0],
					'posts'    => ['UINT:10', 0],
				],
				'PRIMARY_KEY' => ['topic_id', 'user_id', 'day'],
				'KEYS' => [
					'user_day' => ['INDEX', ['user_id', 'day']],
				],
			],
			'likes_daily_post' => [
				'COLUMNS' => [
					'day'       => ['UINT', 0],
					'post_id'   => ['UINT', 0],
					'topic_id'  => ['UINT', 0],
					'forum_id'  => ['UINT', 0],
					'poster_id' => ['UINT', 0],
					'likes'     => ['UINT:10', 0],
				],
				'PRIMARY_KEY' => ['post_id', 'day'],
				'KEYS' => [
					'day'        => ['INDEX', 'day'],
					'topic_day'  => ['INDEX', ['topic_id', 'day']],
					'forum_day'  => ['INDEX', ['forum_id', 'day']],
					'poster_day' => ['INDEX', ['poster_id', 'day']],
				],
			],
			'users_daily' => [
				'COLUMNS' => [
					'day'               => ['UINT', 0],
					'registered'        => ['UINT:10', 0],
					'registered_active' => ['UINT:10', 0],
					'active_7d'         => ['UINT:10', 0],
				],
				'PRIMARY_KEY' => 'day',
			],
			'totals_forum' => [
				'COLUMNS' => [
					'forum_id' => ['UINT', 0],
					'posts'    => ['UINT:10', 0],
					'topics'   => ['UINT:10', 0],
					'likes'    => ['UINT:10', 0],
					'views'    => ['BINT', 0],
				],
				'PRIMARY_KEY' => 'forum_id',
			],
			'totals_user' => [
				'COLUMNS' => [
					'user_id' => ['UINT', 0],
					'posts'   => ['UINT:10', 0],
					'likes'   => ['UINT:10', 0],
				],
				'PRIMARY_KEY' => 'user_id',
			],
			'meta' => [
				'COLUMNS' => [
					'meta_key'   => ['VCHAR:50', ''],
					'meta_value' => ['TEXT_UNI', ''],
				],
				'PRIMARY_KEY' => 'meta_key',
			],
		];
	}
}
