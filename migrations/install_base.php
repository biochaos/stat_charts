<?php
/**
 * Stat Charts extension for phpBB.
 *
 * @copyright (c) 2026, biochaos
 * @license GNU General Public License, version 2 (GPL-2.0)
 */

namespace biochaos\statcharts\migrations;

/**
 * Root of the migration tree. Deliberately empty: it pins the phpBB baseline
 * so later migrations (schema, config, permission, ACP module) can depend on it.
 */
class install_base extends \phpbb\db\migration\migration
{
	public static function depends_on()
	{
		return ['\phpbb\db\migration\data\v330\v330'];
	}

	public function update_data()
	{
		return [];
	}
}
