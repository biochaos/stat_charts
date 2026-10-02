<?php
/**
 * Stat Charts extension for phpBB.
 *
 * @copyright (c) 2026, biochaos
 * @license GNU General Public License, version 2 (GPL-2.0)
 */

namespace biochaos\statcharts;

class ext extends \phpbb\extension\base
{
	/** Database layers the rebuild is tested on (SPEC section 5, portable rebuild). */
	const TESTED_LAYERS = ['mysqli', 'postgres', 'sqlite3'];

	/**
	 * phpBB 3.3, on a database the rebuild has been tested on. MS SQL and
	 * Oracle have not been, so they are refused with the reason rather than
	 * risk wrong statistics.
	 *
	 * @return bool|array True, or the reasons it cannot be enabled
	 */
	public function is_enableable()
	{
		$config = $this->container->get('config');
		if (!phpbb_version_compare($config['version'], '3.3.0', '>='))
		{
			return false;
		}

		$layer = $this->container->get('dbal.conn')->get_sql_layer();
		if (in_array($layer, self::TESTED_LAYERS, true))
		{
			return true;
		}

		$language = $this->container->get('language');
		$language->add_lang('info_acp_statcharts', 'biochaos/statcharts');
		return [$language->lang('STATCHARTS_DB_UNTESTED', $layer)];
	}
}
