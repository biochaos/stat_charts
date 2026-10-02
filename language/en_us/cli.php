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

$lang = array_merge($lang, [
	'STATCHARTS_CLI_REBUILD'         => 'Rebuild the statistics tables from the forum, to completion.',
	'STATCHARTS_CLI_REBUILD_RESTART' => 'Discard a rebuild in progress and start over.',
	'STATCHARTS_CLI_PROGRESS'        => 'step %1$d (%2$s), post id %3$d of %4$d',
	'STATCHARTS_CLI_PROGRESS_DAYS'   => 'step %1$d (%2$s), day %3$d of %4$d',
	'STATCHARTS_CLI_LOCKED'          => 'Another rebuild is running. Try again once it has finished.',
	'STATCHARTS_CLI_DONE'            => 'Statistics rebuilt in %s seconds.',
]);
