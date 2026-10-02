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
	'STATCHARTS_TITLE'                   => 'Statistiche',
	'STATCHARTS_LOGIN'                   => 'Devi essere connesso per vedere le statistiche.',
	'STATCHARTS_NOT_BUILT'               => 'Le statistiche non sono ancora state calcolate.',
	'STATCHARTS_LINK_ELIDED'             => '[link]',
	'STATCHARTS_NOSCRIPT'                => 'Le statistiche richiedono JavaScript.',
	'STATCHARTS_JS_TAB_GENERAL'          => 'Generale',
	'STATCHARTS_JS_TAB_FORUM'            => 'Forum',
	'STATCHARTS_JS_TAB_TOPIC'            => 'Argomento',
	'STATCHARTS_JS_TAB_USER'             => 'Utente',
	'STATCHARTS_JS_AS_OF'                => 'Dati al {time}',
	'STATCHARTS_JS_NOT_BUILT'            => 'Le statistiche non sono ancora state calcolate. Appariranno dopo il prossimo ricalcolo programmato.',
	'STATCHARTS_JS_LOADING'              => 'Caricamento…',
	'STATCHARTS_JS_ERROR'                => 'Impossibile caricare le statistiche.',
	'STATCHARTS_JS_NO_ACTIVITY'          => 'Nessuna attività in questo periodo',
	'STATCHARTS_JS_NO_ROWS'              => 'Niente da mostrare',
	'STATCHARTS_JS_RANGE_ALL'            => 'Da sempre',
	'STATCHARTS_JS_RANGE_YEAR'           => 'Ultimo anno',
	'STATCHARTS_JS_RANGE_MONTH'          => 'Ultimo mese',
	'STATCHARTS_JS_RANGE_WEEK'           => 'Ultima settimana',
	'STATCHARTS_JS_RANGE_TODAY'          => 'Oggi',
	'STATCHARTS_JS_RANGE_CUSTOM'         => 'Periodo personalizzato',
	'STATCHARTS_JS_RANGE_FROM'           => 'Dal',
	'STATCHARTS_JS_RANGE_TO'             => 'Al',
	'STATCHARTS_JS_TODAY_CAPTION'        => 'Dalla mezzanotte, al {time}',
	'STATCHARTS_JS_PAGE_RANGE'           => 'Periodo di tutti i grafici e le tabelle',
	'STATCHARTS_JS_PAGE_RANGE_HINT'      => 'Vale per tutti i grafici e le tabelle della pagina; ognuno può poi essere cambiato separatamente.',
	'STATCHARTS_JS_RANGE_OVERRIDDEN'     => 'Diverso dal periodo della pagina',
	'STATCHARTS_JS_MODE_AVG'             => 'Media su 7 giorni',
	'STATCHARTS_JS_MODE_DAY'             => 'Al giorno',
	'STATCHARTS_JS_MODE_CUM'             => 'Cumulativo',
	'STATCHARTS_JS_SHOW_TABLE'           => 'Tabella',
	'STATCHARTS_JS_SHOW_CHART'           => 'Grafico',
	'STATCHARTS_JS_TOTAL'                => 'Totale',
	'STATCHARTS_JS_POSTS_OVER_TIME'      => 'Messaggi nel tempo',
	'STATCHARTS_JS_POSTS_BY_FORUM'       => 'Messaggi per forum',
	'STATCHARTS_JS_TOPICS_BY_FORUM'      => 'Nuovi argomenti per forum',
	'STATCHARTS_JS_LIKES_BY_FORUM'       => 'Ringraziamenti per forum',
	'STATCHARTS_JS_LIKES_EXPLAIN'        => 'Ringraziamenti dati nel periodo, anche per messaggi più vecchi.',
	'STATCHARTS_JS_VIEWS_BY_FORUM'       => 'Visite per forum',
	'STATCHARTS_JS_VIEWS_BY_FORUM_RANGE' => 'Visite per forum, degli argomenti aperti: {range}',
	'STATCHARTS_JS_VIEWS_NOTE'           => 'phpBB conta le visite su tutta la vita di un argomento, quindi non si possono datare. Un periodo seleziona gli argomenti aperti in quel periodo.',
	'STATCHARTS_JS_USERS'                => 'Utenti',
	'STATCHARTS_JS_USERS_REGISTERED'     => 'Registrati',
	'STATCHARTS_JS_USERS_POSTED'         => 'Registrati che hanno scritto',
	'STATCHARTS_JS_ACTIVE_7D'            => 'Utenti attivi (7 giorni)',
	'STATCHARTS_JS_ACTIVE_7D_EXPLAIN'    => 'Per ogni giorno, gli utenti che hanno scritto nei sette giorni fino a quel giorno.',
	'STATCHARTS_JS_MOST_VIEWED'          => 'Argomenti più visti',
	'STATCHARTS_JS_MOST_VIEWED_RANGE'    => 'I più visti tra gli argomenti aperti: {range}',
	'STATCHARTS_JS_MOST_ACTIVE'          => 'Argomenti più attivi',
	'STATCHARTS_JS_MOST_LIKED'           => 'Messaggi più ringraziati',
	'STATCHARTS_JS_COL_TOPIC'            => 'Argomento',
	'STATCHARTS_JS_COL_TOPICS'           => 'Argomenti',
	'STATCHARTS_JS_COL_FORUM'            => 'Forum',
	'STATCHARTS_JS_COL_USER'             => 'Utente',
	'STATCHARTS_JS_COL_POST'             => 'Messaggio',
	'STATCHARTS_JS_COL_VIEWS'            => 'Visite',
	'STATCHARTS_JS_COL_POSTS'            => 'Messaggi',
	'STATCHARTS_JS_COL_LIKES'            => 'Ringraziamenti',
	'STATCHARTS_JS_COL_SHARE'            => 'Quota',
	'STATCHARTS_JS_COL_MONTH'            => 'Mese',
	'STATCHARTS_JS_FORUM_SELECT'         => 'Forum',
	'STATCHARTS_JS_FORUM_NOT_IN_STATS'   => 'Questo forum non è incluso nelle statistiche. Scegline un altro qui sopra.',
	'STATCHARTS_JS_FORUM_HEADER'         => '{posts} messaggi in {topics} argomenti',
	'STATCHARTS_JS_USER_SEARCH'          => 'Cerca utenti',
	'STATCHARTS_JS_USER_PICK'            => 'Scegli un utente per vedere le sue statistiche.',
	'STATCHARTS_JS_USER_NOT_FOUND'       => 'Utente inesistente.',
	'STATCHARTS_JS_USER_HEADER'          => '{posts} messaggi, {likes} ringraziamenti ricevuti',
	'STATCHARTS_JS_USER_TOP_TOPICS'      => 'Argomenti con più messaggi',
	'STATCHARTS_JS_NO_PROFILES'          => 'Non hai il permesso di vedere le statistiche degli utenti.',
	'STATCHARTS_JS_TOPIC_SEARCH'         => 'Cerca argomenti',
	'STATCHARTS_JS_TOPIC_ALL_FORUMS'     => 'Tutti i forum',
	'STATCHARTS_JS_SORT_TITLE'           => 'Per titolo',
	'STATCHARTS_JS_SORT_REPLIES'         => 'Per risposte',
	'STATCHARTS_JS_TOPIC_NOT_FOUND'      => 'Argomento inesistente.',
	'STATCHARTS_JS_TOPIC_HEADER'         => 'Aperto da {poster} il {date} in {forum}',
	'STATCHARTS_JS_TOPIC_ACTIVITY'       => 'Attività dell’argomento',
	'STATCHARTS_JS_TOPIC_ACTIVITY_ZOOM'  => 'Attività dell’argomento, ingrandita',
	'STATCHARTS_JS_TOPIC_TOP_USERS'      => 'Utenti più attivi nell’argomento',
	'STATCHARTS_JS_FORUM_TOP_USERS'      => 'Utenti più attivi nel forum',
	'STATCHARTS_JS_FORUM_TOP_LIKED'      => 'Utenti più ringraziati nel forum',
	'STATCHARTS_JS_GO'                   => 'Vai',
	'STATCHARTS_JS_OTHER'                => 'Altri',
	'STATCHARTS_JS_HIDDEN'               => 'Forum che non puoi vedere',
	'STATCHARTS_JS_OPEN_ON_FORUM'        => 'Apri nel forum',
	'STATCHARTS_JS_SESSION_EXPIRED'      => 'La tua sessione è scaduta.',
	'STATCHARTS_JS_RELOAD'               => 'Ricarica la pagina',
]);
