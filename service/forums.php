<?php
/**
 * Stat Charts extension for phpBB.
 *
 * @copyright (c) 2026, biochaos
 * @license GNU General Public License, version 2 (GPL-2.0)
 */

namespace biochaos\statcharts\service;

use phpbb\config\config;
use phpbb\db\driver\driver_interface;

/**
 * The board's forums as the stats see them, read live from phpbb_forums:
 * what may enter the stats, and what is excluded (ETL rule 2). Shared by the
 * rebuild, which fixes the set for a build, and the ACP, which shows the
 * forums as they are now.
 */
class forums
{
	/** @var driver_interface */
	private $db;

	/** @var config */
	private $config;

	/** @var string */
	private $forums_table;

	public function __construct(driver_interface $db, config $config, $forums_table)
	{
		$this->db           = $db;
		$this->config       = $config;
		$this->forums_table = $forums_table;
	}

	/**
	 * @return array [forum_id => row (forum_id, parent_id, forum_type, left_id, right_id, forum_name)], by left_id
	 */
	public function all()
	{
		$result = $this->db->sql_query('SELECT forum_id, parent_id, forum_type, left_id, right_id, forum_name
			FROM ' . $this->forums_table . '
			ORDER BY left_id');
		$forums = [];
		while ($row = $this->db->sql_fetchrow($result))
		{
			$forums[(int) $row['forum_id']] = $row;
		}
		$this->db->sql_freeresult($result);
		return $forums;
	}

	/** @return int[] The excluded forums as configured (their descendants not included) */
	public function configured_exclusions()
	{
		return array_values(array_filter(array_map('intval', explode(',', (string) $this->config['statcharts_excluded_forums']))));
	}

	/**
	 * Forums whose content may enter the stats, and the excluded set after
	 * cascading to descendants (ETL rule 2). Links carry no content.
	 *
	 * @param array|null $forums Rows from all(), to save reading them twice
	 * @return array [allowed ids, excluded ids], each sorted ascending
	 */
	public function sets(array $forums = null)
	{
		$forums = $forums === null ? $this->all() : $forums;

		$excluded = [];
		foreach ($this->configured_exclusions() as $root_id)
		{
			if (!isset($forums[$root_id]))
			{
				continue;
			}
			$root = $forums[$root_id];
			foreach ($forums as $id => $forum)
			{
				if ($forum['left_id'] >= $root['left_id'] && $forum['left_id'] <= $root['right_id'])
				{
					$excluded[$id] = $id;
				}
			}
		}

		$allowed = [];
		foreach ($forums as $id => $forum)
		{
			if (!isset($excluded[$id]) && (int) $forum['forum_type'] !== FORUM_LINK)
			{
				$allowed[] = $id;
			}
		}

		$excluded = array_values($excluded);
		sort($allowed);
		sort($excluded);
		return [$allowed, $excluded];
	}
}
