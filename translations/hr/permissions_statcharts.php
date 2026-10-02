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

// Translation: a first draft, not yet reviewed by a native speaker.
$lang = array_merge($lang, [
	'ACL_U_STATCHARTS_VIEW' => 'Može vidjeti statistiku foruma',
]);
