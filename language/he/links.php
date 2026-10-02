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

// Loaded on every page, for the links to the statistics: keep it small.
// The wording is the board's existing hand-placed links'.
$lang = array_merge($lang, [
	'STATCHARTS_LINK_BOARD'  => 'סטטיסטיקה',
	'STATCHARTS_LINK_MEMBER' => 'סטטיסטיקה נוספת',
	'STATCHARTS_LINK_FORUM'  => 'סטטיסטיקה נוספת לפורום',
	'STATCHARTS_LINK_TOPIC'  => 'סטטיסטיקה נוספת לאשכול',
]);
