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
 * The portable rebuild (SPEC section 5): a second set of the stats tables to
 * build into while the first is live, the staging tables the chunked steps
 * merge through, and the pointer to the live set. Set "a" stays live, so an
 * existing install needs no rebuild. Replaces the "_new" copies and RENAME of
 * earlier versions, whose leftovers it drops.
 */
class install_second_set extends \phpbb\db\migration\migration
{
	public function effectively_installed()
	{
		return $this->db_tools->sql_table_exists(tables::name($this->table_prefix, 'meta', 'b'));
	}

	public static function depends_on()
	{
		return [
			'\biochaos\statcharts\migrations\install_schema',
			'\biochaos\statcharts\migrations\install_config',
		];
	}

	public function update_schema()
	{
		return [
			'add_tables' => array_merge(
				tables::set_schema($this->table_prefix, 'b'),
				tables::stage_schema($this->table_prefix)
			),
		];
	}

	public function revert_schema()
	{
		return [
			'drop_tables' => array_merge(
				array_keys(tables::set_schema($this->table_prefix, 'b')),
				array_keys(tables::stage_schema($this->table_prefix))
			),
		];
	}

	public function update_data()
	{
		return [
			['config.add', ['statcharts_live_set', 'a']],
			['custom', [[$this, 'drop_build_leftovers']]],
		];
	}

	/** "_new" and "_old" copies from the rename-based rebuild, if one was interrupted. */
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
