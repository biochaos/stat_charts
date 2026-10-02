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
	'STATCHARTS_TITLE'                   => 'Statistieken',
	'STATCHARTS_LOGIN'                   => 'Je moet ingelogd zijn om de statistieken te bekijken.',
	'STATCHARTS_NOT_BUILT'               => 'De statistieken zijn nog niet berekend.',
	'STATCHARTS_LINK_ELIDED'             => '[link]',
	'STATCHARTS_NOSCRIPT'                => 'De statistieken hebben JavaScript nodig.',
	'STATCHARTS_JS_TAB_GENERAL'          => 'Algemeen',
	'STATCHARTS_JS_TAB_FORUM'            => 'Forum',
	'STATCHARTS_JS_TAB_TOPIC'            => 'Onderwerp',
	'STATCHARTS_JS_TAB_USER'             => 'Gebruiker',
	'STATCHARTS_JS_AS_OF'                => 'Gegevens van {time}',
	'STATCHARTS_JS_NOT_BUILT'            => 'De statistieken zijn nog niet berekend. Ze verschijnen na de volgende geplande herberekening.',
	'STATCHARTS_JS_LOADING'              => 'Laden…',
	'STATCHARTS_JS_ERROR'                => 'De statistieken konden niet worden geladen.',
	'STATCHARTS_JS_NO_ACTIVITY'          => 'Geen activiteit in deze periode',
	'STATCHARTS_JS_NO_ROWS'              => 'Niets om te tonen',
	'STATCHARTS_JS_RANGE_ALL'            => 'Altijd',
	'STATCHARTS_JS_RANGE_YEAR'           => 'Afgelopen jaar',
	'STATCHARTS_JS_RANGE_MONTH'          => 'Afgelopen maand',
	'STATCHARTS_JS_RANGE_WEEK'           => 'Afgelopen week',
	'STATCHARTS_JS_RANGE_TODAY'          => 'Vandaag',
	'STATCHARTS_JS_RANGE_CUSTOM'         => 'Aangepaste periode',
	'STATCHARTS_JS_RANGE_FROM'           => 'Van',
	'STATCHARTS_JS_RANGE_TO'             => 'Tot',
	'STATCHARTS_JS_TODAY_CAPTION'        => 'Sinds middernacht, tot {time}',
	'STATCHARTS_JS_PAGE_RANGE'           => 'Periode van alle grafieken en tabellen',
	'STATCHARTS_JS_PAGE_RANGE_HINT'      => 'Geldt voor alle grafieken en tabellen op de pagina; elk kan daarna apart worden aangepast.',
	'STATCHARTS_JS_RANGE_OVERRIDDEN'     => 'Wijkt af van de periode van de pagina',
	'STATCHARTS_JS_MODE_AVG'             => '7-daags gemiddelde',
	'STATCHARTS_JS_MODE_DAY'             => 'Per dag',
	'STATCHARTS_JS_MODE_CUM'             => 'Cumulatief',
	'STATCHARTS_JS_SHOW_TABLE'           => 'Tabel',
	'STATCHARTS_JS_SHOW_CHART'           => 'Grafiek',
	'STATCHARTS_JS_TOTAL'                => 'Totaal',
	'STATCHARTS_JS_POSTS_OVER_TIME'      => 'Berichten door de tijd',
	'STATCHARTS_JS_POSTS_BY_FORUM'       => 'Berichten per forum',
	'STATCHARTS_JS_TOPICS_BY_FORUM'      => 'Nieuwe onderwerpen per forum',
	'STATCHARTS_JS_LIKES_BY_FORUM'       => 'Waarderingen per forum',
	'STATCHARTS_JS_LIKES_EXPLAIN'        => 'Waarderingen gegeven in de periode, ook voor oudere berichten.',
	'STATCHARTS_JS_VIEWS_BY_FORUM'       => 'Weergaven per forum',
	'STATCHARTS_JS_VIEWS_BY_FORUM_RANGE' => 'Weergaven per forum, van onderwerpen gestart: {range}',
	'STATCHARTS_JS_VIEWS_NOTE'           => 'phpBB telt weergaven over de hele levensduur van een onderwerp, dus ze kunnen niet worden gedateerd. Een periode selecteert de onderwerpen die erin zijn gestart.',
	'STATCHARTS_JS_USERS'                => 'Gebruikers',
	'STATCHARTS_JS_USERS_REGISTERED'     => 'Geregistreerd',
	'STATCHARTS_JS_USERS_POSTED'         => 'Geregistreerd en gepost',
	'STATCHARTS_JS_ACTIVE_7D'            => 'Actieve gebruikers (7 dagen)',
	'STATCHARTS_JS_ACTIVE_7D_EXPLAIN'    => 'Voor elke dag de gebruikers die in de zeven dagen tot en met die dag hebben gepost.',
	'STATCHARTS_JS_MOST_VIEWED'          => 'Meest bekeken onderwerpen',
	'STATCHARTS_JS_MOST_VIEWED_RANGE'    => 'Meest bekeken van de onderwerpen gestart: {range}',
	'STATCHARTS_JS_MOST_ACTIVE'          => 'Meest actieve onderwerpen',
	'STATCHARTS_JS_MOST_LIKED'           => 'Meest gewaardeerde berichten',
	'STATCHARTS_JS_COL_TOPIC'            => 'Onderwerp',
	'STATCHARTS_JS_COL_TOPICS'           => 'Onderwerpen',
	'STATCHARTS_JS_COL_FORUM'            => 'Forum',
	'STATCHARTS_JS_COL_USER'             => 'Gebruiker',
	'STATCHARTS_JS_COL_POST'             => 'Bericht',
	'STATCHARTS_JS_COL_VIEWS'            => 'Weergaven',
	'STATCHARTS_JS_COL_POSTS'            => 'Berichten',
	'STATCHARTS_JS_COL_LIKES'            => 'Waarderingen',
	'STATCHARTS_JS_COL_SHARE'            => 'Aandeel',
	'STATCHARTS_JS_COL_MONTH'            => 'Maand',
	'STATCHARTS_JS_FORUM_SELECT'         => 'Forum',
	'STATCHARTS_JS_FORUM_NOT_IN_STATS'   => 'Dit forum is niet opgenomen in de statistieken. Kies hierboven een ander.',
	'STATCHARTS_JS_FORUM_HEADER'         => '{posts} berichten in {topics} onderwerpen',
	'STATCHARTS_JS_USER_SEARCH'          => 'Gebruikers zoeken',
	'STATCHARTS_JS_USER_PICK'            => 'Kies een gebruiker om diens statistieken te zien.',
	'STATCHARTS_JS_USER_NOT_FOUND'       => 'Onbekende gebruiker.',
	'STATCHARTS_JS_USER_HEADER'          => '{posts} berichten, {likes} waarderingen ontvangen',
	'STATCHARTS_JS_USER_TOP_TOPICS'      => 'Onderwerpen met de meeste berichten',
	'STATCHARTS_JS_NO_PROFILES'          => 'Je hebt geen toestemming om de statistieken van gebruikers te bekijken.',
	'STATCHARTS_JS_TOPIC_SEARCH'         => 'Onderwerpen zoeken',
	'STATCHARTS_JS_TOPIC_ALL_FORUMS'     => 'Alle forums',
	'STATCHARTS_JS_SORT_TITLE'           => 'Op titel',
	'STATCHARTS_JS_SORT_REPLIES'         => 'Op reacties',
	'STATCHARTS_JS_TOPIC_NOT_FOUND'      => 'Onbekend onderwerp.',
	'STATCHARTS_JS_TOPIC_HEADER'         => 'Gestart door {poster} op {date} in {forum}',
	'STATCHARTS_JS_TOPIC_ACTIVITY'       => 'Activiteit in het onderwerp',
	'STATCHARTS_JS_TOPIC_ACTIVITY_ZOOM'  => 'Activiteit in het onderwerp, ingezoomd',
	'STATCHARTS_JS_TOPIC_TOP_USERS'      => 'Meest actieve gebruikers in het onderwerp',
	'STATCHARTS_JS_FORUM_TOP_USERS'      => 'Meest actieve gebruikers in het forum',
	'STATCHARTS_JS_FORUM_TOP_LIKED'      => 'Meest gewaardeerde gebruikers in het forum',
	'STATCHARTS_JS_GO'                   => 'Ga',
	'STATCHARTS_JS_OTHER'                => 'Overige',
	'STATCHARTS_JS_HIDDEN'               => 'Forums die je niet kunt zien',
	'STATCHARTS_JS_OPEN_ON_FORUM'        => 'Openen op het forum',
	'STATCHARTS_JS_SESSION_EXPIRED'      => 'Je sessie is verlopen.',
	'STATCHARTS_JS_RELOAD'               => 'Pagina opnieuw laden',
]);
