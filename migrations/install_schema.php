<?php
/**
 * Stat Charts extension for phpBB.
 *
 * @copyright (c) 2026, biochaos
 * @license GNU General Public License, version 2 (GPL-2.0)
 */

namespace biochaos\statcharts\migrations;

use biochaos\statcharts\service\tables;

/**
 * The stats tables, set "a" (service/tables.php holds the definitions and
 * explains the sets). The migration owns the schema; the rebuild never defines
 * columns of its own.
 */
class install_schema extends \phpbb\db\migration\migration
{
	public function effectively_installed()
	{
		return $this->db_tools->sql_table_exists($this->table_prefix . 'statcharts_meta');
	}

	public static function depends_on()
	{
		return ['\biochaos\statcharts\migrations\install_base'];
	}

	public function update_schema()
	{
		return [
			'add_tables' => tables::set_schema($this->table_prefix, 'a'),
		];
	}

	public function revert_schema()
	{
		return [
			'drop_tables' => array_keys(tables::set_schema($this->table_prefix, 'a')),
		];
	}

	public function revert_data()
	{
		return [
			['custom', [[$this, 'drop_build_leftovers']]],
		];
	}

	/**
	 * Before 0.9.2 a rebuild built into "_new" copies and swapped them in by
	 * renaming the old set to "_old"; an interrupted one left either behind.
	 * The migration system does not know about them, so purge drops them here.
	 */
	public function drop_build_leftovers()
	{
		foreach (tables::names() as $name)
		{
			foreach (['_new', '_old'] as $suffix)
			{
				$table = $this->table_prefix . 'statcharts_' . $name . $suffix;
				if ($this->db_tools->sql_table_exists($table))
				{
					$this->db_tools->sql_table_drop($table);
				}
			}
		}
	}
}
