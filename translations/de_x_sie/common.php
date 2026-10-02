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

// STATCHARTS_JS_* go to the page script; {name} placeholders are filled there.
// Translation: a first draft, not yet reviewed by a native speaker.
$lang = array_merge($lang, [
	'STATCHARTS_TITLE'                   => 'Statistik',
	'STATCHARTS_LOGIN'                   => 'Sie müssen angemeldet sein, um die Statistik zu sehen.',
	'STATCHARTS_NOT_BUILT'               => 'Die Statistik wurde noch nicht erstellt.',
	'STATCHARTS_LINK_ELIDED'             => '[Link]',
	'STATCHARTS_NOSCRIPT'                => 'Die Statistik benötigt JavaScript.',
	'STATCHARTS_JS_TAB_GENERAL'          => 'Allgemein',
	'STATCHARTS_JS_TAB_FORUM'            => 'Forum',
	'STATCHARTS_JS_TAB_TOPIC'            => 'Thema',
	'STATCHARTS_JS_TAB_USER'             => 'Mitglied',
	'STATCHARTS_JS_AS_OF'                => 'Stand: {time}',
	'STATCHARTS_JS_NOT_BUILT'            => 'Die Statistik wurde noch nicht erstellt. Sie erscheint nach der nächsten geplanten Neuberechnung.',
	'STATCHARTS_JS_LOADING'              => 'Wird geladen …',
	'STATCHARTS_JS_ERROR'                => 'Die Statistik konnte nicht geladen werden.',
	'STATCHARTS_JS_NO_ACTIVITY'          => 'Keine Aktivität in diesem Zeitraum',
	'STATCHARTS_JS_NO_ROWS'              => 'Nichts anzuzeigen',
	'STATCHARTS_JS_RANGE_ALL'            => 'Gesamter Zeitraum',
	'STATCHARTS_JS_RANGE_YEAR'           => 'Letztes Jahr',
	'STATCHARTS_JS_RANGE_MONTH'          => 'Letzter Monat',
	'STATCHARTS_JS_RANGE_WEEK'           => 'Letzte Woche',
	'STATCHARTS_JS_RANGE_TODAY'          => 'Heute',
	'STATCHARTS_JS_RANGE_CUSTOM'         => 'Eigener Zeitraum',
	'STATCHARTS_JS_RANGE_FROM'           => 'Von',
	'STATCHARTS_JS_RANGE_TO'             => 'Bis',
	'STATCHARTS_JS_TODAY_CAPTION'        => 'Seit Mitternacht, Stand: {time}',
	'STATCHARTS_JS_PAGE_RANGE'           => 'Zeitraum aller Diagramme und Tabellen',
	'STATCHARTS_JS_PAGE_RANGE_HINT'      => 'Gilt für alle Diagramme und Tabellen der Seite; jedes lässt sich danach einzeln ändern.',
	'STATCHARTS_JS_RANGE_OVERRIDDEN'     => 'Weicht vom Zeitraum der Seite ab',
	'STATCHARTS_JS_MODE_AVG'             => '7-Tage-Durchschnitt',
	'STATCHARTS_JS_MODE_DAY'             => 'Pro Tag',
	'STATCHARTS_JS_MODE_CUM'             => 'Kumuliert',
	'STATCHARTS_JS_SHOW_TABLE'           => 'Tabelle',
	'STATCHARTS_JS_SHOW_CHART'           => 'Diagramm',
	'STATCHARTS_JS_TOTAL'                => 'Gesamt',
	'STATCHARTS_JS_POSTS_OVER_TIME'      => 'Beiträge im Zeitverlauf',
	'STATCHARTS_JS_POSTS_BY_FORUM'       => 'Beiträge nach Forum',
	'STATCHARTS_JS_TOPICS_BY_FORUM'      => 'Neue Themen nach Forum',
	'STATCHARTS_JS_LIKES_BY_FORUM'       => 'Danksagungen nach Forum',
	'STATCHARTS_JS_LIKES_EXPLAIN'        => 'Im Zeitraum vergebene Danksagungen, auch für ältere Beiträge.',
	'STATCHARTS_JS_VIEWS_BY_FORUM'       => 'Zugriffe nach Forum',
	'STATCHARTS_JS_VIEWS_BY_FORUM_RANGE' => 'Zugriffe nach Forum, Themen erstellt: {range}',
	'STATCHARTS_JS_VIEWS_NOTE'           => 'phpBB zählt die Zugriffe über die ganze Lebensdauer eines Themas, sie lassen sich also nicht datieren. Ein Zeitraum wählt die darin erstellten Themen aus.',
	'STATCHARTS_JS_USERS'                => 'Mitglieder',
	'STATCHARTS_JS_USERS_REGISTERED'     => 'Registriert',
	'STATCHARTS_JS_USERS_POSTED'         => 'Registriert und geschrieben',
	'STATCHARTS_JS_ACTIVE_7D'            => 'Aktive Mitglieder (7 Tage)',
	'STATCHARTS_JS_ACTIVE_7D_EXPLAIN'    => 'Für jeden Tag die Mitglieder, die in den sieben Tagen bis dahin geschrieben haben.',
	'STATCHARTS_JS_MOST_VIEWED'          => 'Meistgelesene Themen',
	'STATCHARTS_JS_MOST_VIEWED_RANGE'    => 'Meistgelesene unter den Themen erstellt: {range}',
	'STATCHARTS_JS_MOST_ACTIVE'          => 'Aktivste Themen',
	'STATCHARTS_JS_MOST_LIKED'           => 'Beiträge mit den meisten Danksagungen',
	'STATCHARTS_JS_COL_TOPIC'            => 'Thema',
	'STATCHARTS_JS_COL_TOPICS'           => 'Themen',
	'STATCHARTS_JS_COL_FORUM'            => 'Forum',
	'STATCHARTS_JS_COL_USER'             => 'Mitglied',
	'STATCHARTS_JS_COL_POST'             => 'Beitrag',
	'STATCHARTS_JS_COL_VIEWS'            => 'Zugriffe',
	'STATCHARTS_JS_COL_POSTS'            => 'Beiträge',
	'STATCHARTS_JS_COL_LIKES'            => 'Danksagungen',
	'STATCHARTS_JS_COL_SHARE'            => 'Anteil',
	'STATCHARTS_JS_COL_MONTH'            => 'Monat',
	'STATCHARTS_JS_FORUM_SELECT'         => 'Forum',
	'STATCHARTS_JS_FORUM_NOT_IN_STATS'   => 'Dieses Forum ist nicht in der Statistik. Wählen Sie oben ein anderes.',
	'STATCHARTS_JS_FORUM_HEADER'         => '{posts} Beiträge in {topics} Themen',
	'STATCHARTS_JS_USER_SEARCH'          => 'Mitglieder suchen',
	'STATCHARTS_JS_USER_PICK'            => 'Wählen Sie ein Mitglied, um seine Statistik zu sehen.',
	'STATCHARTS_JS_USER_NOT_FOUND'       => 'Kein solches Mitglied.',
	'STATCHARTS_JS_USER_HEADER'          => '{posts} Beiträge, {likes} Danksagungen erhalten',
	'STATCHARTS_JS_USER_TOP_TOPICS'      => 'Themen mit den meisten Beiträgen',
	'STATCHARTS_JS_NO_PROFILES'          => 'Sie haben keine Berechtigung, die Statistik von Mitgliedern zu sehen.',
	'STATCHARTS_JS_TOPIC_SEARCH'         => 'Themen suchen',
	'STATCHARTS_JS_TOPIC_ALL_FORUMS'     => 'Alle Foren',
	'STATCHARTS_JS_SORT_TITLE'           => 'Nach Titel',
	'STATCHARTS_JS_SORT_REPLIES'         => 'Nach Antworten',
	'STATCHARTS_JS_TOPIC_NOT_FOUND'      => 'Kein solches Thema.',
	'STATCHARTS_JS_TOPIC_HEADER'         => 'Erstellt von {poster} am {date} in {forum}',
	'STATCHARTS_JS_TOPIC_ACTIVITY'       => 'Aktivität im Thema',
	'STATCHARTS_JS_TOPIC_ACTIVITY_ZOOM'  => 'Aktivität im Thema, vergrößert',
	'STATCHARTS_JS_TOPIC_TOP_USERS'      => 'Aktivste Mitglieder im Thema',
	'STATCHARTS_JS_FORUM_TOP_USERS'      => 'Aktivste Mitglieder im Forum',
	'STATCHARTS_JS_FORUM_TOP_LIKED'      => 'Mitglieder mit den meisten Danksagungen im Forum',
	'STATCHARTS_JS_GO'                   => 'Los',
	'STATCHARTS_JS_OTHER'                => 'Andere',
	'STATCHARTS_JS_HIDDEN'               => 'Foren, die Sie nicht sehen können',
	'STATCHARTS_JS_OPEN_ON_FORUM'        => 'Im Forum öffnen',
	'STATCHARTS_JS_SESSION_EXPIRED'      => 'Ihre Sitzung ist abgelaufen.',
	'STATCHARTS_JS_RELOAD'               => 'Seite neu laden',
]);
