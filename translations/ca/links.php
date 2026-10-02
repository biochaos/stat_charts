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
// Translation: a first draft, not yet reviewed by a native speaker.
$lang = array_merge($lang, [
	'STATCHARTS_LINK_BOARD'  => 'Estadístiques',
	'STATCHARTS_LINK_MEMBER' => 'Més estadístiques',
	'STATCHARTS_LINK_FORUM'  => 'Més estadístiques d’aquest fòrum',
	'STATCHARTS_LINK_TOPIC'  => 'Més estadístiques d’aquest tema',
]);
