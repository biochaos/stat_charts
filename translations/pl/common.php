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
	'STATCHARTS_TITLE'                   => 'Statystyki',
	'STATCHARTS_LOGIN'                   => 'Musisz być zalogowany, aby zobaczyć statystyki.',
	'STATCHARTS_NOT_BUILT'               => 'Statystyki nie zostały jeszcze obliczone.',
	'STATCHARTS_LINK_ELIDED'             => '[link]',
	'STATCHARTS_NOSCRIPT'                => 'Statystyki wymagają JavaScriptu.',
	'STATCHARTS_JS_TAB_GENERAL'          => 'Ogólne',
	'STATCHARTS_JS_TAB_FORUM'            => 'Forum',
	'STATCHARTS_JS_TAB_TOPIC'            => 'Temat',
	'STATCHARTS_JS_TAB_USER'             => 'Użytkownik',
	'STATCHARTS_JS_AS_OF'                => 'Dane z {time}',
	'STATCHARTS_JS_NOT_BUILT'            => 'Statystyki nie zostały jeszcze obliczone. Pojawią się po następnym zaplanowanym przeliczeniu.',
	'STATCHARTS_JS_LOADING'              => 'Wczytywanie…',
	'STATCHARTS_JS_ERROR'                => 'Nie udało się wczytać statystyk.',
	'STATCHARTS_JS_NO_ACTIVITY'          => 'Brak aktywności w tym okresie',
	'STATCHARTS_JS_NO_ROWS'              => 'Brak danych',
	'STATCHARTS_JS_RANGE_ALL'            => 'Cały okres',
	'STATCHARTS_JS_RANGE_YEAR'           => 'Ostatni rok',
	'STATCHARTS_JS_RANGE_MONTH'          => 'Ostatni miesiąc',
	'STATCHARTS_JS_RANGE_WEEK'           => 'Ostatni tydzień',
	'STATCHARTS_JS_RANGE_TODAY'          => 'Dzisiaj',
	'STATCHARTS_JS_RANGE_CUSTOM'         => 'Własny zakres',
	'STATCHARTS_JS_RANGE_FROM'           => 'Od',
	'STATCHARTS_JS_RANGE_TO'             => 'Do',
	'STATCHARTS_JS_TODAY_CAPTION'        => 'Od północy, stan na {time}',
	'STATCHARTS_JS_PAGE_RANGE'           => 'Zakres wszystkich wykresów i tabel',
	'STATCHARTS_JS_PAGE_RANGE_HINT'      => 'Ustawia wszystkie wykresy i tabele na stronie; każdy można potem zmienić osobno.',
	'STATCHARTS_JS_RANGE_OVERRIDDEN'     => 'Inny niż zakres strony',
	'STATCHARTS_JS_MODE_AVG'             => 'Średnia 7-dniowa',
	'STATCHARTS_JS_MODE_DAY'             => 'Dziennie',
	'STATCHARTS_JS_MODE_CUM'             => 'Narastająco',
	'STATCHARTS_JS_SHOW_TABLE'           => 'Tabela',
	'STATCHARTS_JS_SHOW_CHART'           => 'Wykres',
	'STATCHARTS_JS_TOTAL'                => 'Razem',
	'STATCHARTS_JS_POSTS_OVER_TIME'      => 'Posty w czasie',
	'STATCHARTS_JS_POSTS_BY_FORUM'       => 'Posty według forum',
	'STATCHARTS_JS_TOPICS_BY_FORUM'      => 'Nowe tematy według forum',
	'STATCHARTS_JS_LIKES_BY_FORUM'       => 'Podziękowania według forum',
	'STATCHARTS_JS_LIKES_EXPLAIN'        => 'Podziękowania przyznane w danym okresie, także za starsze posty.',
	'STATCHARTS_JS_VIEWS_BY_FORUM'       => 'Wyświetlenia według forum',
	'STATCHARTS_JS_VIEWS_BY_FORUM_RANGE' => 'Wyświetlenia według forum, tematy założone: {range}',
	'STATCHARTS_JS_VIEWS_NOTE'           => 'phpBB liczy wyświetlenia przez cały czas istnienia tematu, więc nie da się ich datować. Zakres wybiera tematy założone w tym okresie.',
	'STATCHARTS_JS_USERS'                => 'Użytkownicy',
	'STATCHARTS_JS_USERS_REGISTERED'     => 'Zarejestrowani',
	'STATCHARTS_JS_USERS_POSTED'         => 'Zarejestrowani, którzy pisali',
	'STATCHARTS_JS_ACTIVE_7D'            => 'Aktywni użytkownicy (7 dni)',
	'STATCHARTS_JS_ACTIVE_7D_EXPLAIN'    => 'Dla każdego dnia użytkownicy, którzy pisali w ciągu siedmiu dni do tego dnia.',
	'STATCHARTS_JS_MOST_VIEWED'          => 'Najczęściej wyświetlane tematy',
	'STATCHARTS_JS_MOST_VIEWED_RANGE'    => 'Najczęściej wyświetlane spośród tematów założonych: {range}',
	'STATCHARTS_JS_MOST_ACTIVE'          => 'Najaktywniejsze tematy',
	'STATCHARTS_JS_MOST_LIKED'           => 'Posty z największą liczbą podziękowań',
	'STATCHARTS_JS_COL_TOPIC'            => 'Temat',
	'STATCHARTS_JS_COL_TOPICS'           => 'Tematy',
	'STATCHARTS_JS_COL_FORUM'            => 'Forum',
	'STATCHARTS_JS_COL_USER'             => 'Użytkownik',
	'STATCHARTS_JS_COL_POST'             => 'Post',
	'STATCHARTS_JS_COL_VIEWS'            => 'Wyświetlenia',
	'STATCHARTS_JS_COL_POSTS'            => 'Posty',
	'STATCHARTS_JS_COL_LIKES'            => 'Podziękowania',
	'STATCHARTS_JS_COL_SHARE'            => 'Udział',
	'STATCHARTS_JS_COL_MONTH'            => 'Miesiąc',
	'STATCHARTS_JS_FORUM_SELECT'         => 'Forum',
	'STATCHARTS_JS_FORUM_NOT_IN_STATS'   => 'To forum nie jest uwzględnione w statystykach. Wybierz inne powyżej.',
	'STATCHARTS_JS_FORUM_HEADER'         => 'Postów: {posts} w tematach: {topics}',
	'STATCHARTS_JS_USER_SEARCH'          => 'Szukaj użytkowników',
	'STATCHARTS_JS_USER_PICK'            => 'Wybierz użytkownika, aby zobaczyć jego statystyki.',
	'STATCHARTS_JS_USER_NOT_FOUND'       => 'Nie ma takiego użytkownika.',
	'STATCHARTS_JS_USER_HEADER'          => 'Postów: {posts}, otrzymanych podziękowań: {likes}',
	'STATCHARTS_JS_USER_TOP_TOPICS'      => 'Tematy z największą liczbą postów',
	'STATCHARTS_JS_NO_PROFILES'          => 'Nie masz uprawnień do przeglądania statystyk użytkowników.',
	'STATCHARTS_JS_TOPIC_SEARCH'         => 'Szukaj tematów',
	'STATCHARTS_JS_TOPIC_ALL_FORUMS'     => 'Wszystkie fora',
	'STATCHARTS_JS_SORT_TITLE'           => 'Według tytułu',
	'STATCHARTS_JS_SORT_REPLIES'         => 'Według odpowiedzi',
	'STATCHARTS_JS_TOPIC_NOT_FOUND'      => 'Nie ma takiego tematu.',
	'STATCHARTS_JS_TOPIC_HEADER'         => 'Założony przez {poster} dnia {date} w {forum}',
	'STATCHARTS_JS_TOPIC_ACTIVITY'       => 'Aktywność w temacie',
	'STATCHARTS_JS_TOPIC_ACTIVITY_ZOOM'  => 'Aktywność w temacie, powiększenie',
	'STATCHARTS_JS_TOPIC_TOP_USERS'      => 'Najaktywniejsi użytkownicy w temacie',
	'STATCHARTS_JS_FORUM_TOP_USERS'      => 'Najaktywniejsi użytkownicy na forum',
	'STATCHARTS_JS_FORUM_TOP_LIKED'      => 'Użytkownicy z największą liczbą podziękowań na forum',
	'STATCHARTS_JS_GO'                   => 'Przejdź',
	'STATCHARTS_JS_OTHER'                => 'Inne',
	'STATCHARTS_JS_HIDDEN'               => 'Fora, których nie widzisz',
	'STATCHARTS_JS_OPEN_ON_FORUM'        => 'Otwórz na forum',
	'STATCHARTS_JS_SESSION_EXPIRED'      => 'Twoja sesja wygasła.',
	'STATCHARTS_JS_RELOAD'               => 'Odśwież stronę',
]);
