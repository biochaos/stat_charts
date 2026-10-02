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
	'STATCHARTS_TITLE'                   => 'Statistika',
	'STATCHARTS_LOGIN'                   => 'Morate biti prijavljeni da biste vidjeli statistiku.',
	'STATCHARTS_NOT_BUILT'               => 'Statistika još nije izračunata.',
	'STATCHARTS_LINK_ELIDED'             => '[poveznica]',
	'STATCHARTS_NOSCRIPT'                => 'Statistika zahtijeva JavaScript.',
	'STATCHARTS_JS_TAB_GENERAL'          => 'Općenito',
	'STATCHARTS_JS_TAB_FORUM'            => 'Forum',
	'STATCHARTS_JS_TAB_TOPIC'            => 'Tema',
	'STATCHARTS_JS_TAB_USER'             => 'Korisnik',
	'STATCHARTS_JS_AS_OF'                => 'Podaci na dan {time}',
	'STATCHARTS_JS_NOT_BUILT'            => 'Statistika još nije izračunata. Pojavit će se nakon sljedećeg planiranog izračuna.',
	'STATCHARTS_JS_LOADING'              => 'Učitavanje…',
	'STATCHARTS_JS_ERROR'                => 'Statistiku nije moguće učitati.',
	'STATCHARTS_JS_NO_ACTIVITY'          => 'Nema aktivnosti u ovom razdoblju',
	'STATCHARTS_JS_NO_ROWS'              => 'Nema se što prikazati',
	'STATCHARTS_JS_RANGE_ALL'            => 'Cijelo razdoblje',
	'STATCHARTS_JS_RANGE_YEAR'           => 'Posljednja godina',
	'STATCHARTS_JS_RANGE_MONTH'          => 'Posljednji mjesec',
	'STATCHARTS_JS_RANGE_WEEK'           => 'Posljednji tjedan',
	'STATCHARTS_JS_RANGE_TODAY'          => 'Danas',
	'STATCHARTS_JS_RANGE_CUSTOM'         => 'Prilagođeno razdoblje',
	'STATCHARTS_JS_RANGE_FROM'           => 'Od',
	'STATCHARTS_JS_RANGE_TO'             => 'Do',
	'STATCHARTS_JS_TODAY_CAPTION'        => 'Od ponoći, stanje u {time}',
	'STATCHARTS_JS_PAGE_RANGE'           => 'Razdoblje svih grafikona i tablica',
	'STATCHARTS_JS_PAGE_RANGE_HINT'      => 'Postavlja sve grafikone i tablice na stranici; svaki se zatim može zasebno promijeniti.',
	'STATCHARTS_JS_RANGE_OVERRIDDEN'     => 'Razlikuje se od razdoblja stranice',
	'STATCHARTS_JS_MODE_AVG'             => 'Prosjek 7 dana',
	'STATCHARTS_JS_MODE_DAY'             => 'Po danu',
	'STATCHARTS_JS_MODE_CUM'             => 'Kumulativno',
	'STATCHARTS_JS_SHOW_TABLE'           => 'Tablica',
	'STATCHARTS_JS_SHOW_CHART'           => 'Grafikon',
	'STATCHARTS_JS_TOTAL'                => 'Ukupno',
	'STATCHARTS_JS_POSTS_OVER_TIME'      => 'Poruke kroz vrijeme',
	'STATCHARTS_JS_POSTS_BY_FORUM'       => 'Poruke po forumu',
	'STATCHARTS_JS_TOPICS_BY_FORUM'      => 'Nove teme po forumu',
	'STATCHARTS_JS_LIKES_BY_FORUM'       => 'Zahvale po forumu',
	'STATCHARTS_JS_LIKES_EXPLAIN'        => 'Zahvale dane u razdoblju, uključujući zahvale za starije poruke.',
	'STATCHARTS_JS_VIEWS_BY_FORUM'       => 'Pregledi po forumu',
	'STATCHARTS_JS_VIEWS_BY_FORUM_RANGE' => 'Pregledi po forumu, tema pokrenutih: {range}',
	'STATCHARTS_JS_VIEWS_NOTE'           => 'phpBB broji preglede kroz cijeli vijek teme, pa ih nije moguće datirati. Razdoblje odabire teme pokrenute u njemu.',
	'STATCHARTS_JS_USERS'                => 'Korisnici',
	'STATCHARTS_JS_USERS_REGISTERED'     => 'Registrirani',
	'STATCHARTS_JS_USERS_POSTED'         => 'Registrirani koji su pisali',
	'STATCHARTS_JS_ACTIVE_7D'            => 'Aktivni korisnici (7 dana)',
	'STATCHARTS_JS_ACTIVE_7D_EXPLAIN'    => 'Za svaki dan, korisnici koji su pisali u sedam dana do tog dana.',
	'STATCHARTS_JS_MOST_VIEWED'          => 'Najgledanije teme',
	'STATCHARTS_JS_MOST_VIEWED_RANGE'    => 'Najgledanije među temama pokrenutim: {range}',
	'STATCHARTS_JS_MOST_ACTIVE'          => 'Najaktivnije teme',
	'STATCHARTS_JS_MOST_LIKED'           => 'Poruke s najviše zahvala',
	'STATCHARTS_JS_COL_TOPIC'            => 'Tema',
	'STATCHARTS_JS_COL_TOPICS'           => 'Teme',
	'STATCHARTS_JS_COL_FORUM'            => 'Forum',
	'STATCHARTS_JS_COL_USER'             => 'Korisnik',
	'STATCHARTS_JS_COL_POST'             => 'Poruka',
	'STATCHARTS_JS_COL_VIEWS'            => 'Pregledi',
	'STATCHARTS_JS_COL_POSTS'            => 'Poruke',
	'STATCHARTS_JS_COL_LIKES'            => 'Zahvale',
	'STATCHARTS_JS_COL_SHARE'            => 'Udio',
	'STATCHARTS_JS_COL_MONTH'            => 'Mjesec',
	'STATCHARTS_JS_FORUM_SELECT'         => 'Forum',
	'STATCHARTS_JS_FORUM_NOT_IN_STATS'   => 'Ovaj forum nije uključen u statistiku. Odaberite drugi gore.',
	'STATCHARTS_JS_FORUM_HEADER'         => 'Poruka: {posts} u temama: {topics}',
	'STATCHARTS_JS_USER_SEARCH'          => 'Traži korisnike',
	'STATCHARTS_JS_USER_PICK'            => 'Odaberite korisnika da biste vidjeli njegovu statistiku.',
	'STATCHARTS_JS_USER_NOT_FOUND'       => 'Nema takvog korisnika.',
	'STATCHARTS_JS_USER_HEADER'          => 'Poruka: {posts}, primljenih zahvala: {likes}',
	'STATCHARTS_JS_USER_TOP_TOPICS'      => 'Teme s najviše poruka',
	'STATCHARTS_JS_NO_PROFILES'          => 'Nemate dopuštenje za pregled statistike korisnika.',
	'STATCHARTS_JS_TOPIC_SEARCH'         => 'Traži teme',
	'STATCHARTS_JS_TOPIC_ALL_FORUMS'     => 'Svi forumi',
	'STATCHARTS_JS_SORT_TITLE'           => 'Po naslovu',
	'STATCHARTS_JS_SORT_REPLIES'         => 'Po odgovorima',
	'STATCHARTS_JS_TOPIC_NOT_FOUND'      => 'Nema takve teme.',
	'STATCHARTS_JS_TOPIC_HEADER'         => 'Pokrenuo/la {poster} dana {date} u forumu {forum}',
	'STATCHARTS_JS_TOPIC_ACTIVITY'       => 'Aktivnost teme',
	'STATCHARTS_JS_TOPIC_ACTIVITY_ZOOM'  => 'Aktivnost teme, uvećano',
	'STATCHARTS_JS_TOPIC_TOP_USERS'      => 'Najaktivniji korisnici u temi',
	'STATCHARTS_JS_FORUM_TOP_USERS'      => 'Najaktivniji korisnici u forumu',
	'STATCHARTS_JS_FORUM_TOP_LIKED'      => 'Korisnici s najviše zahvala u forumu',
	'STATCHARTS_JS_GO'                   => 'Idi',
	'STATCHARTS_JS_OTHER'                => 'Ostalo',
	'STATCHARTS_JS_HIDDEN'               => 'Forumi koje ne možete vidjeti',
	'STATCHARTS_JS_OPEN_ON_FORUM'        => 'Otvori na forumu',
	'STATCHARTS_JS_SESSION_EXPIRED'      => 'Vaša je sesija istekla.',
	'STATCHARTS_JS_RELOAD'               => 'Ponovno učitaj stranicu',
]);
