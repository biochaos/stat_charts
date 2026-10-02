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
$lang = array_merge($lang, [
	'STATCHARTS_LINK_BOARD'  => 'Statistics',
	'STATCHARTS_LINK_MEMBER' => 'More statistics',
	'STATCHARTS_LINK_FORUM'  => 'More statistics for this forum',
	'STATCHARTS_LINK_TOPIC'  => 'More statistics for this topic',
]);
