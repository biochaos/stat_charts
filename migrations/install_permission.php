<?php
/**
 * Stat Charts extension for phpBB.
 *
 * @copyright (c) 2026, biochaos
 * @license GNU General Public License, version 2 (GPL-2.0)
 */

namespace biochaos\statcharts\migrations;

/**
 * u_statcharts_view, granted to registered users so the page works as soon as
 * the extension is enabled (SPEC section 10). The ACP learns of it through
 * the core.permissions listener (event/listener.php), and labels it from
 * language/<lang>/permissions_statcharts.php.
 */
class install_permission extends \phpbb\db\migration\migration
{
	public static function depends_on()
	{
		return ['\biochaos\statcharts\migrations\install_base'];
	}

	public function update_data()
	{
		return [
			['permission.add', ['u_statcharts_view']],
			['permission.permission_set', ['REGISTERED', 'u_statcharts_view', 'group']],
		];
	}
}
