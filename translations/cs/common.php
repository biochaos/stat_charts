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
	'STATCHARTS_TITLE'                   => 'Statistiky',
	'STATCHARTS_LOGIN'                   => 'Pro zobrazení statistik se musíte přihlásit.',
	'STATCHARTS_NOT_BUILT'               => 'Statistiky ještě nebyly spočítány.',
	'STATCHARTS_LINK_ELIDED'             => '[odkaz]',
	'STATCHARTS_NOSCRIPT'                => 'Statistiky vyžadují JavaScript.',
	'STATCHARTS_JS_TAB_GENERAL'          => 'Obecné',
	'STATCHARTS_JS_TAB_FORUM'            => 'Fórum',
	'STATCHARTS_JS_TAB_TOPIC'            => 'Téma',
	'STATCHARTS_JS_TAB_USER'             => 'Uživatel',
	'STATCHARTS_JS_AS_OF'                => 'Údaje k {time}',
	'STATCHARTS_JS_NOT_BUILT'            => 'Statistiky ještě nebyly spočítány. Objeví se po příštím plánovaném přepočtu.',
	'STATCHARTS_JS_LOADING'              => 'Načítání…',
	'STATCHARTS_JS_ERROR'                => 'Statistiky se nepodařilo načíst.',
	'STATCHARTS_JS_NO_ACTIVITY'          => 'V tomto období žádná aktivita',
	'STATCHARTS_JS_NO_ROWS'              => 'Nic k zobrazení',
	'STATCHARTS_JS_RANGE_ALL'            => 'Celé období',
	'STATCHARTS_JS_RANGE_YEAR'           => 'Poslední rok',
	'STATCHARTS_JS_RANGE_MONTH'          => 'Poslední měsíc',
	'STATCHARTS_JS_RANGE_WEEK'           => 'Poslední týden',
	'STATCHARTS_JS_RANGE_TODAY'          => 'Dnes',
	'STATCHARTS_JS_RANGE_CUSTOM'         => 'Vlastní období',
	'STATCHARTS_JS_RANGE_FROM'           => 'Od',
	'STATCHARTS_JS_RANGE_TO'             => 'Do',
	'STATCHARTS_JS_TODAY_CAPTION'        => 'Od půlnoci, stav k {time}',
	'STATCHARTS_JS_PAGE_RANGE'           => 'Období všech grafů a tabulek',
	'STATCHARTS_JS_PAGE_RANGE_HINT'      => 'Nastaví všechny grafy a tabulky na stránce; každý pak lze změnit zvlášť.',
	'STATCHARTS_JS_RANGE_OVERRIDDEN'     => 'Liší se od období stránky',
	'STATCHARTS_JS_MODE_AVG'             => '7denní průměr',
	'STATCHARTS_JS_MODE_DAY'             => 'Za den',
	'STATCHARTS_JS_MODE_CUM'             => 'Kumulativně',
	'STATCHARTS_JS_SHOW_TABLE'           => 'Tabulka',
	'STATCHARTS_JS_SHOW_CHART'           => 'Graf',
	'STATCHARTS_JS_TOTAL'                => 'Celkem',
	'STATCHARTS_JS_POSTS_OVER_TIME'      => 'Příspěvky v čase',
	'STATCHARTS_JS_POSTS_BY_FORUM'       => 'Příspěvky podle fóra',
	'STATCHARTS_JS_TOPICS_BY_FORUM'      => 'Nová témata podle fóra',
	'STATCHARTS_JS_LIKES_BY_FORUM'       => 'Poděkování podle fóra',
	'STATCHARTS_JS_LIKES_EXPLAIN'        => 'Poděkování udělená v daném období, i za starší příspěvky.',
	'STATCHARTS_JS_VIEWS_BY_FORUM'       => 'Zobrazení podle fóra',
	'STATCHARTS_JS_VIEWS_BY_FORUM_RANGE' => 'Zobrazení podle fóra, u témat založených: {range}',
	'STATCHARTS_JS_VIEWS_NOTE'           => 'phpBB počítá zobrazení za celou dobu existence tématu, proto je nelze datovat. Období vybírá témata v něm založená.',
	'STATCHARTS_JS_USERS'                => 'Uživatelé',
	'STATCHARTS_JS_USERS_REGISTERED'     => 'Registrovaní',
	'STATCHARTS_JS_USERS_POSTED'         => 'Registrovaní, kteří psali',
	'STATCHARTS_JS_ACTIVE_7D'            => 'Aktivní uživatelé (7 dní)',
	'STATCHARTS_JS_ACTIVE_7D_EXPLAIN'    => 'Pro každý den uživatelé, kteří psali během sedmi dnů do tohoto dne.',
	'STATCHARTS_JS_MOST_VIEWED'          => 'Nejzobrazovanější témata',
	'STATCHARTS_JS_MOST_VIEWED_RANGE'    => 'Nejzobrazovanější z témat založených: {range}',
	'STATCHARTS_JS_MOST_ACTIVE'          => 'Nejaktivnější témata',
	'STATCHARTS_JS_MOST_LIKED'           => 'Příspěvky s nejvíce poděkováními',
	'STATCHARTS_JS_COL_TOPIC'            => 'Téma',
	'STATCHARTS_JS_COL_TOPICS'           => 'Témata',
	'STATCHARTS_JS_COL_FORUM'            => 'Fórum',
	'STATCHARTS_JS_COL_USER'             => 'Uživatel',
	'STATCHARTS_JS_COL_POST'             => 'Příspěvek',
	'STATCHARTS_JS_COL_VIEWS'            => 'Zobrazení',
	'STATCHARTS_JS_COL_POSTS'            => 'Příspěvky',
	'STATCHARTS_JS_COL_LIKES'            => 'Poděkování',
	'STATCHARTS_JS_COL_SHARE'            => 'Podíl',
	'STATCHARTS_JS_COL_MONTH'            => 'Měsíc',
	'STATCHARTS_JS_FORUM_SELECT'         => 'Fórum',
	'STATCHARTS_JS_FORUM_NOT_IN_STATS'   => 'Toto fórum není zahrnuto ve statistikách. Vyberte výše jiné.',
	'STATCHARTS_JS_FORUM_HEADER'         => 'Příspěvků: {posts} v tématech: {topics}',
	'STATCHARTS_JS_USER_SEARCH'          => 'Hledat uživatele',
	'STATCHARTS_JS_USER_PICK'            => 'Vyberte uživatele pro zobrazení jeho statistik.',
	'STATCHARTS_JS_USER_NOT_FOUND'       => 'Takový uživatel neexistuje.',
	'STATCHARTS_JS_USER_HEADER'          => 'Příspěvků: {posts}, obdržených poděkování: {likes}',
	'STATCHARTS_JS_USER_TOP_TOPICS'      => 'Témata s nejvíce příspěvky',
	'STATCHARTS_JS_NO_PROFILES'          => 'Nemáte oprávnění zobrazit statistiky uživatelů.',
	'STATCHARTS_JS_TOPIC_SEARCH'         => 'Hledat témata',
	'STATCHARTS_JS_TOPIC_ALL_FORUMS'     => 'Všechna fóra',
	'STATCHARTS_JS_SORT_TITLE'           => 'Podle názvu',
	'STATCHARTS_JS_SORT_REPLIES'         => 'Podle odpovědí',
	'STATCHARTS_JS_TOPIC_NOT_FOUND'      => 'Takové téma neexistuje.',
	'STATCHARTS_JS_TOPIC_HEADER'         => 'Založil(a) {poster} dne {date} ve fóru {forum}',
	'STATCHARTS_JS_TOPIC_ACTIVITY'       => 'Aktivita v tématu',
	'STATCHARTS_JS_TOPIC_ACTIVITY_ZOOM'  => 'Aktivita v tématu, přiblíženo',
	'STATCHARTS_JS_TOPIC_TOP_USERS'      => 'Nejaktivnější uživatelé v tématu',
	'STATCHARTS_JS_FORUM_TOP_USERS'      => 'Nejaktivnější uživatelé ve fóru',
	'STATCHARTS_JS_FORUM_TOP_LIKED'      => 'Uživatelé s nejvíce poděkováními ve fóru',
	'STATCHARTS_JS_GO'                   => 'Přejít',
	'STATCHARTS_JS_OTHER'                => 'Ostatní',
	'STATCHARTS_JS_HIDDEN'               => 'Fóra, která nevidíte',
	'STATCHARTS_JS_OPEN_ON_FORUM'        => 'Otevřít ve fóru',
	'STATCHARTS_JS_SESSION_EXPIRED'      => 'Vaše relace vypršela.',
	'STATCHARTS_JS_RELOAD'               => 'Znovu načíst stránku',
]);
