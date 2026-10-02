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
	'ACP_STATCHARTS'          => 'סטטיסטיקה',
	'ACP_STATCHARTS_SETTINGS' => 'הגדרות',
	'STATCHARTS_DB_UNTESTED'  => 'Stat Charts תומכת ב-MySQL, MariaDB, PostgreSQL ו-SQLite. היא לא נבדקה על מסד הנתונים של הפורום הזה (%s), ולכן אי אפשר להפעיל אותה עליו.',
]);
