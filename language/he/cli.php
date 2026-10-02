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
	'STATCHARTS_CLI_REBUILD'         => 'חישוב מחדש של טבלאות הסטטיסטיקה מהפורום, עד הסוף.',
	'STATCHARTS_CLI_REBUILD_RESTART' => 'לבטל חישוב שבאמצע ולהתחיל מההתחלה.',
	'STATCHARTS_CLI_PROGRESS'        => 'שלב %1$d (%2$s), הודעה %3$d מתוך %4$d',
	'STATCHARTS_CLI_PROGRESS_DAYS'   => 'שלב %1$d (%2$s), יום %3$d מתוך %4$d',
	'STATCHARTS_CLI_LOCKED'          => 'חישוב אחר רץ כעת. אפשר לנסות שוב כשיסתיים.',
	'STATCHARTS_CLI_DONE'            => 'הסטטיסטיקה חושבה מחדש תוך %s שניות.',
]);
