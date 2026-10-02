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
	'STATCHARTS_TITLE'                   => 'Estadístiques',
	'STATCHARTS_LOGIN'                   => 'Heu d’iniciar la sessió per veure les estadístiques.',
	'STATCHARTS_NOT_BUILT'               => 'Les estadístiques encara no s’han calculat.',
	'STATCHARTS_LINK_ELIDED'             => '[enllaç]',
	'STATCHARTS_NOSCRIPT'                => 'Les estadístiques necessiten JavaScript.',
	'STATCHARTS_JS_TAB_GENERAL'          => 'General',
	'STATCHARTS_JS_TAB_FORUM'            => 'Fòrum',
	'STATCHARTS_JS_TAB_TOPIC'            => 'Tema',
	'STATCHARTS_JS_TAB_USER'             => 'Membre',
	'STATCHARTS_JS_AS_OF'                => 'Dades a {time}',
	'STATCHARTS_JS_NOT_BUILT'            => 'Les estadístiques encara no s’han calculat. Apareixeran després del proper recàlcul programat.',
	'STATCHARTS_JS_LOADING'              => 'S’està carregant…',
	'STATCHARTS_JS_ERROR'                => 'No s’han pogut carregar les estadístiques.',
	'STATCHARTS_JS_NO_ACTIVITY'          => 'Cap activitat en aquest període',
	'STATCHARTS_JS_NO_ROWS'              => 'Res a mostrar',
	'STATCHARTS_JS_RANGE_ALL'            => 'Des de sempre',
	'STATCHARTS_JS_RANGE_YEAR'           => 'Darrer any',
	'STATCHARTS_JS_RANGE_MONTH'          => 'Darrer mes',
	'STATCHARTS_JS_RANGE_WEEK'           => 'Darrera setmana',
	'STATCHARTS_JS_RANGE_TODAY'          => 'Avui',
	'STATCHARTS_JS_RANGE_CUSTOM'         => 'Període personalitzat',
	'STATCHARTS_JS_RANGE_FROM'           => 'Des de',
	'STATCHARTS_JS_RANGE_TO'             => 'Fins a',
	'STATCHARTS_JS_TODAY_CAPTION'        => 'Des de mitjanit, a {time}',
	'STATCHARTS_JS_PAGE_RANGE'           => 'Període de tots els gràfics i taules',
	'STATCHARTS_JS_PAGE_RANGE_HINT'      => 'S’aplica a tots els gràfics i taules de la pàgina; després cadascun es pot canviar per separat.',
	'STATCHARTS_JS_RANGE_OVERRIDDEN'     => 'Diferent del període de la pàgina',
	'STATCHARTS_JS_MODE_AVG'             => 'Mitjana de 7 dies',
	'STATCHARTS_JS_MODE_DAY'             => 'Per dia',
	'STATCHARTS_JS_MODE_CUM'             => 'Acumulat',
	'STATCHARTS_JS_SHOW_TABLE'           => 'Taula',
	'STATCHARTS_JS_SHOW_CHART'           => 'Gràfic',
	'STATCHARTS_JS_TOTAL'                => 'Total',
	'STATCHARTS_JS_POSTS_OVER_TIME'      => 'Missatges al llarg del temps',
	'STATCHARTS_JS_POSTS_BY_FORUM'       => 'Missatges per fòrum',
	'STATCHARTS_JS_TOPICS_BY_FORUM'      => 'Temes nous per fòrum',
	'STATCHARTS_JS_LIKES_BY_FORUM'       => 'Agraïments per fòrum',
	'STATCHARTS_JS_LIKES_EXPLAIN'        => 'Agraïments donats durant el període, inclosos els de missatges més antics.',
	'STATCHARTS_JS_VIEWS_BY_FORUM'       => 'Visites per fòrum',
	'STATCHARTS_JS_VIEWS_BY_FORUM_RANGE' => 'Visites per fòrum, dels temes creats: {range}',
	'STATCHARTS_JS_VIEWS_NOTE'           => 'El phpBB compta les visites durant tota la vida d’un tema, així que no es poden datar. Un període selecciona els temes creats en aquest.',
	'STATCHARTS_JS_USERS'                => 'Membres',
	'STATCHARTS_JS_USERS_REGISTERED'     => 'Registrats',
	'STATCHARTS_JS_USERS_POSTED'         => 'Registrats que han escrit',
	'STATCHARTS_JS_ACTIVE_7D'            => 'Membres actius (7 dies)',
	'STATCHARTS_JS_ACTIVE_7D_EXPLAIN'    => 'Per a cada dia, els membres que van escriure en els set dies fins a aquell dia.',
	'STATCHARTS_JS_MOST_VIEWED'          => 'Temes més vistos',
	'STATCHARTS_JS_MOST_VIEWED_RANGE'    => 'Els més vistos entre els temes creats: {range}',
	'STATCHARTS_JS_MOST_ACTIVE'          => 'Temes més actius',
	'STATCHARTS_JS_MOST_LIKED'           => 'Missatges més agraïts',
	'STATCHARTS_JS_COL_TOPIC'            => 'Tema',
	'STATCHARTS_JS_COL_TOPICS'           => 'Temes',
	'STATCHARTS_JS_COL_FORUM'            => 'Fòrum',
	'STATCHARTS_JS_COL_USER'             => 'Membre',
	'STATCHARTS_JS_COL_POST'             => 'Missatge',
	'STATCHARTS_JS_COL_VIEWS'            => 'Visites',
	'STATCHARTS_JS_COL_POSTS'            => 'Missatges',
	'STATCHARTS_JS_COL_LIKES'            => 'Agraïments',
	'STATCHARTS_JS_COL_SHARE'            => 'Proporció',
	'STATCHARTS_JS_COL_MONTH'            => 'Mes',
	'STATCHARTS_JS_FORUM_SELECT'         => 'Fòrum',
	'STATCHARTS_JS_FORUM_NOT_IN_STATS'   => 'Aquest fòrum no és a les estadístiques. Trieu-ne un altre a dalt.',
	'STATCHARTS_JS_FORUM_HEADER'         => '{posts} missatges en {topics} temes',
	'STATCHARTS_JS_USER_SEARCH'          => 'Cerca membres',
	'STATCHARTS_JS_USER_PICK'            => 'Trieu un membre per veure’n les estadístiques.',
	'STATCHARTS_JS_USER_NOT_FOUND'       => 'No hi ha cap membre amb aquest nom.',
	'STATCHARTS_JS_USER_HEADER'          => '{posts} missatges, {likes} agraïments rebuts',
	'STATCHARTS_JS_USER_TOP_TOPICS'      => 'Temes amb més missatges',
	'STATCHARTS_JS_NO_PROFILES'          => 'No teniu permís per veure les estadístiques dels membres.',
	'STATCHARTS_JS_TOPIC_SEARCH'         => 'Cerca temes',
	'STATCHARTS_JS_TOPIC_ALL_FORUMS'     => 'Tots els fòrums',
	'STATCHARTS_JS_SORT_TITLE'           => 'Per títol',
	'STATCHARTS_JS_SORT_REPLIES'         => 'Per respostes',
	'STATCHARTS_JS_TOPIC_NOT_FOUND'      => 'No hi ha cap tema amb aquest nom.',
	'STATCHARTS_JS_TOPIC_HEADER'         => 'Creat per {poster} el {date} a {forum}',
	'STATCHARTS_JS_TOPIC_ACTIVITY'       => 'Activitat del tema',
	'STATCHARTS_JS_TOPIC_ACTIVITY_ZOOM'  => 'Activitat del tema, ampliada',
	'STATCHARTS_JS_TOPIC_TOP_USERS'      => 'Membres més actius al tema',
	'STATCHARTS_JS_FORUM_TOP_USERS'      => 'Membres més actius al fòrum',
	'STATCHARTS_JS_FORUM_TOP_LIKED'      => 'Membres més agraïts al fòrum',
	'STATCHARTS_JS_GO'                   => 'Vés',
	'STATCHARTS_JS_OTHER'                => 'Altres',
	'STATCHARTS_JS_HIDDEN'               => 'Fòrums que no podeu veure',
	'STATCHARTS_JS_OPEN_ON_FORUM'        => 'Obre al fòrum',
	'STATCHARTS_JS_SESSION_EXPIRED'      => 'La vostra sessió ha caducat.',
	'STATCHARTS_JS_RELOAD'               => 'Torna a carregar la pàgina',
]);
