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
	'STATCHARTS_TITLE'                   => 'Štatistiky',
	'STATCHARTS_LOGIN'                   => 'Na zobrazenie štatistík sa musíte prihlásiť.',
	'STATCHARTS_NOT_BUILT'               => 'Štatistiky ešte neboli vypočítané.',
	'STATCHARTS_LINK_ELIDED'             => '[odkaz]',
	'STATCHARTS_NOSCRIPT'                => 'Štatistiky vyžadujú JavaScript.',
	'STATCHARTS_JS_TAB_GENERAL'          => 'Všeobecné',
	'STATCHARTS_JS_TAB_FORUM'            => 'Fórum',
	'STATCHARTS_JS_TAB_TOPIC'            => 'Téma',
	'STATCHARTS_JS_TAB_USER'             => 'Používateľ',
	'STATCHARTS_JS_AS_OF'                => 'Údaje k {time}',
	'STATCHARTS_JS_NOT_BUILT'            => 'Štatistiky ešte neboli vypočítané. Objavia sa po najbližšom plánovanom prepočte.',
	'STATCHARTS_JS_LOADING'              => 'Načítava sa…',
	'STATCHARTS_JS_ERROR'                => 'Štatistiky sa nepodarilo načítať.',
	'STATCHARTS_JS_NO_ACTIVITY'          => 'V tomto období žiadna aktivita',
	'STATCHARTS_JS_NO_ROWS'              => 'Nič na zobrazenie',
	'STATCHARTS_JS_RANGE_ALL'            => 'Celé obdobie',
	'STATCHARTS_JS_RANGE_YEAR'           => 'Posledný rok',
	'STATCHARTS_JS_RANGE_MONTH'          => 'Posledný mesiac',
	'STATCHARTS_JS_RANGE_WEEK'           => 'Posledný týždeň',
	'STATCHARTS_JS_RANGE_TODAY'          => 'Dnes',
	'STATCHARTS_JS_RANGE_CUSTOM'         => 'Vlastné obdobie',
	'STATCHARTS_JS_RANGE_FROM'           => 'Od',
	'STATCHARTS_JS_RANGE_TO'             => 'Do',
	'STATCHARTS_JS_TODAY_CAPTION'        => 'Od polnoci, stav k {time}',
	'STATCHARTS_JS_PAGE_RANGE'           => 'Obdobie všetkých grafov a tabuliek',
	'STATCHARTS_JS_PAGE_RANGE_HINT'      => 'Nastaví všetky grafy a tabuľky na stránke; každý potom možno zmeniť zvlášť.',
	'STATCHARTS_JS_RANGE_OVERRIDDEN'     => 'Líši sa od obdobia stránky',
	'STATCHARTS_JS_MODE_AVG'             => '7-dňový priemer',
	'STATCHARTS_JS_MODE_DAY'             => 'Za deň',
	'STATCHARTS_JS_MODE_CUM'             => 'Kumulatívne',
	'STATCHARTS_JS_SHOW_TABLE'           => 'Tabuľka',
	'STATCHARTS_JS_SHOW_CHART'           => 'Graf',
	'STATCHARTS_JS_TOTAL'                => 'Spolu',
	'STATCHARTS_JS_POSTS_OVER_TIME'      => 'Príspevky v čase',
	'STATCHARTS_JS_POSTS_BY_FORUM'       => 'Príspevky podľa fóra',
	'STATCHARTS_JS_TOPICS_BY_FORUM'      => 'Nové témy podľa fóra',
	'STATCHARTS_JS_LIKES_BY_FORUM'       => 'Poďakovania podľa fóra',
	'STATCHARTS_JS_LIKES_EXPLAIN'        => 'Poďakovania udelené v danom období, aj za staršie príspevky.',
	'STATCHARTS_JS_VIEWS_BY_FORUM'       => 'Zobrazenia podľa fóra',
	'STATCHARTS_JS_VIEWS_BY_FORUM_RANGE' => 'Zobrazenia podľa fóra, pri témach založených: {range}',
	'STATCHARTS_JS_VIEWS_NOTE'           => 'phpBB počíta zobrazenia za celú dobu existencie témy, preto ich nemožno datovať. Obdobie vyberá témy v ňom založené.',
	'STATCHARTS_JS_USERS'                => 'Používatelia',
	'STATCHARTS_JS_USERS_REGISTERED'     => 'Registrovaní',
	'STATCHARTS_JS_USERS_POSTED'         => 'Registrovaní, ktorí písali',
	'STATCHARTS_JS_ACTIVE_7D'            => 'Aktívni používatelia (7 dní)',
	'STATCHARTS_JS_ACTIVE_7D_EXPLAIN'    => 'Pre každý deň používatelia, ktorí písali počas siedmich dní do tohto dňa.',
	'STATCHARTS_JS_MOST_VIEWED'          => 'Najzobrazovanejšie témy',
	'STATCHARTS_JS_MOST_VIEWED_RANGE'    => 'Najzobrazovanejšie z tém založených: {range}',
	'STATCHARTS_JS_MOST_ACTIVE'          => 'Najaktívnejšie témy',
	'STATCHARTS_JS_MOST_LIKED'           => 'Príspevky s najviac poďakovaniami',
	'STATCHARTS_JS_COL_TOPIC'            => 'Téma',
	'STATCHARTS_JS_COL_TOPICS'           => 'Témy',
	'STATCHARTS_JS_COL_FORUM'            => 'Fórum',
	'STATCHARTS_JS_COL_USER'             => 'Používateľ',
	'STATCHARTS_JS_COL_POST'             => 'Príspevok',
	'STATCHARTS_JS_COL_VIEWS'            => 'Zobrazenia',
	'STATCHARTS_JS_COL_POSTS'            => 'Príspevky',
	'STATCHARTS_JS_COL_LIKES'            => 'Poďakovania',
	'STATCHARTS_JS_COL_SHARE'            => 'Podiel',
	'STATCHARTS_JS_COL_MONTH'            => 'Mesiac',
	'STATCHARTS_JS_FORUM_SELECT'         => 'Fórum',
	'STATCHARTS_JS_FORUM_NOT_IN_STATS'   => 'Toto fórum nie je zahrnuté v štatistikách. Vyberte vyššie iné.',
	'STATCHARTS_JS_FORUM_HEADER'         => 'Príspevkov: {posts} v témach: {topics}',
	'STATCHARTS_JS_USER_SEARCH'          => 'Hľadať používateľov',
	'STATCHARTS_JS_USER_PICK'            => 'Vyberte používateľa na zobrazenie jeho štatistík.',
	'STATCHARTS_JS_USER_NOT_FOUND'       => 'Taký používateľ neexistuje.',
	'STATCHARTS_JS_USER_HEADER'          => 'Príspevkov: {posts}, prijatých poďakovaní: {likes}',
	'STATCHARTS_JS_USER_TOP_TOPICS'      => 'Témy s najviac príspevkami',
	'STATCHARTS_JS_NO_PROFILES'          => 'Nemáte oprávnenie zobraziť štatistiky používateľov.',
	'STATCHARTS_JS_TOPIC_SEARCH'         => 'Hľadať témy',
	'STATCHARTS_JS_TOPIC_ALL_FORUMS'     => 'Všetky fóra',
	'STATCHARTS_JS_SORT_TITLE'           => 'Podľa názvu',
	'STATCHARTS_JS_SORT_REPLIES'         => 'Podľa odpovedí',
	'STATCHARTS_JS_TOPIC_NOT_FOUND'      => 'Taká téma neexistuje.',
	'STATCHARTS_JS_TOPIC_HEADER'         => 'Založil(a) {poster} dňa {date} vo fóre {forum}',
	'STATCHARTS_JS_TOPIC_ACTIVITY'       => 'Aktivita v téme',
	'STATCHARTS_JS_TOPIC_ACTIVITY_ZOOM'  => 'Aktivita v téme, priblížené',
	'STATCHARTS_JS_TOPIC_TOP_USERS'      => 'Najaktívnejší používatelia v téme',
	'STATCHARTS_JS_FORUM_TOP_USERS'      => 'Najaktívnejší používatelia vo fóre',
	'STATCHARTS_JS_FORUM_TOP_LIKED'      => 'Používatelia s najviac poďakovaniami vo fóre',
	'STATCHARTS_JS_GO'                   => 'Prejsť',
	'STATCHARTS_JS_OTHER'                => 'Ostatné',
	'STATCHARTS_JS_HIDDEN'               => 'Fóra, ktoré nevidíte',
	'STATCHARTS_JS_OPEN_ON_FORUM'        => 'Otvoriť vo fóre',
	'STATCHARTS_JS_SESSION_EXPIRED'      => 'Vaša relácia vypršala.',
	'STATCHARTS_JS_RELOAD'               => 'Znova načítať stránku',
]);
