<?php
/**
 * Stat Charts extension for phpBB.
 *
 * @copyright (c) 2026, biochaos
 * @license GNU General Public License, version 2 (GPL-2.0)
 */

if (!defined('IN_PHPBB'))
{
	exit;
}

if (empty($lang) || !is_array($lang))
{
	$lang = [];
}

// Loaded by phpBB on every ACP page (info_acp_*), for the module menu.
$lang = array_merge($lang, [
	'ACP_STATCHARTS'          => 'Statistics',
	'ACP_STATCHARTS_SETTINGS' => 'Settings',
	'STATCHARTS_DB_UNTESTED'  => 'Stat Charts supports MySQL, MariaDB, PostgreSQL and SQLite. It has not been tested on this board’s database (%s), so it cannot be enabled on it.',
]);
