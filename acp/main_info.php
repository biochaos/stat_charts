<?php
/**
 * Stat Charts extension for phpBB.
 *
 * @copyright (c) 2026, biochaos
 * @license GNU General Public License, version 2 (GPL-2.0)
 */

namespace biochaos\statcharts\acp;

class main_info
{
	public function module()
	{
		return [
			'filename' => '\biochaos\statcharts\acp\main_module',
			'title'    => 'ACP_STATCHARTS',
			'modes'    => [
				'settings' => [
					'title' => 'ACP_STATCHARTS_SETTINGS',
					'auth'  => 'ext_biochaos/statcharts && acl_a_board',
					'cat'   => ['ACP_STATCHARTS'],
				],
			],
		];
	}
}
