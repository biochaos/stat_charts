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
	'STATCHARTS_TITLE'                   => 'Statistiques',
	'STATCHARTS_LOGIN'                   => 'Vous devez être connecté pour voir les statistiques.',
	'STATCHARTS_NOT_BUILT'               => 'Les statistiques n’ont pas encore été calculées.',
	'STATCHARTS_LINK_ELIDED'             => '[lien]',
	'STATCHARTS_NOSCRIPT'                => 'Les statistiques nécessitent JavaScript.',
	'STATCHARTS_JS_TAB_GENERAL'          => 'Général',
	'STATCHARTS_JS_TAB_FORUM'            => 'Forum',
	'STATCHARTS_JS_TAB_TOPIC'            => 'Sujet',
	'STATCHARTS_JS_TAB_USER'             => 'Membre',
	'STATCHARTS_JS_AS_OF'                => 'Données au {time}',
	'STATCHARTS_JS_NOT_BUILT'            => 'Les statistiques n’ont pas encore été calculées. Elles apparaîtront après le prochain calcul programmé.',
	'STATCHARTS_JS_LOADING'              => 'Chargement…',
	'STATCHARTS_JS_ERROR'                => 'Impossible de charger les statistiques.',
	'STATCHARTS_JS_NO_ACTIVITY'          => 'Aucune activité sur cette période',
	'STATCHARTS_JS_NO_ROWS'              => 'Rien à afficher',
	'STATCHARTS_JS_RANGE_ALL'            => 'Depuis le début',
	'STATCHARTS_JS_RANGE_YEAR'           => 'Année écoulée',
	'STATCHARTS_JS_RANGE_MONTH'          => 'Mois écoulé',
	'STATCHARTS_JS_RANGE_WEEK'           => 'Semaine écoulée',
	'STATCHARTS_JS_RANGE_TODAY'          => 'Aujourd’hui',
	'STATCHARTS_JS_RANGE_CUSTOM'         => 'Période personnalisée',
	'STATCHARTS_JS_RANGE_FROM'           => 'Du',
	'STATCHARTS_JS_RANGE_TO'             => 'Au',
	'STATCHARTS_JS_TODAY_CAPTION'        => 'Depuis minuit, au {time}',
	'STATCHARTS_JS_PAGE_RANGE'           => 'Période de tous les graphiques et tableaux',
	'STATCHARTS_JS_PAGE_RANGE_HINT'      => 'S’applique à tous les graphiques et tableaux de la page ; chacun peut ensuite être modifié séparément.',
	'STATCHARTS_JS_RANGE_OVERRIDDEN'     => 'Diffère de la période de la page',
	'STATCHARTS_JS_MODE_AVG'             => 'Moyenne sur 7 jours',
	'STATCHARTS_JS_MODE_DAY'             => 'Par jour',
	'STATCHARTS_JS_MODE_CUM'             => 'Cumulé',
	'STATCHARTS_JS_SHOW_TABLE'           => 'Tableau',
	'STATCHARTS_JS_SHOW_CHART'           => 'Graphique',
	'STATCHARTS_JS_TOTAL'                => 'Total',
	'STATCHARTS_JS_POSTS_OVER_TIME'      => 'Messages au fil du temps',
	'STATCHARTS_JS_POSTS_BY_FORUM'       => 'Messages par forum',
	'STATCHARTS_JS_TOPICS_BY_FORUM'      => 'Nouveaux sujets par forum',
	'STATCHARTS_JS_LIKES_BY_FORUM'       => 'Remerciements par forum',
	'STATCHARTS_JS_LIKES_EXPLAIN'        => 'Remerciements donnés pendant la période, y compris pour des messages plus anciens.',
	'STATCHARTS_JS_VIEWS_BY_FORUM'       => 'Vues par forum',
	'STATCHARTS_JS_VIEWS_BY_FORUM_RANGE' => 'Vues par forum, des sujets créés : {range}',
	'STATCHARTS_JS_VIEWS_NOTE'           => 'phpBB compte les vues sur toute la vie d’un sujet : elles ne peuvent pas être datées. Une période sélectionne les sujets créés pendant celle-ci.',
	'STATCHARTS_JS_USERS'                => 'Membres',
	'STATCHARTS_JS_USERS_REGISTERED'     => 'Inscrits',
	'STATCHARTS_JS_USERS_POSTED'         => 'Inscrits ayant posté',
	'STATCHARTS_JS_ACTIVE_7D'            => 'Membres actifs (7 jours)',
	'STATCHARTS_JS_ACTIVE_7D_EXPLAIN'    => 'Pour chaque jour, les membres ayant posté au cours des sept jours précédents, ce jour compris.',
	'STATCHARTS_JS_MOST_VIEWED'          => 'Sujets les plus vus',
	'STATCHARTS_JS_MOST_VIEWED_RANGE'    => 'Les plus vus parmi les sujets créés : {range}',
	'STATCHARTS_JS_MOST_ACTIVE'          => 'Sujets les plus actifs',
	'STATCHARTS_JS_MOST_LIKED'           => 'Messages les plus remerciés',
	'STATCHARTS_JS_COL_TOPIC'            => 'Sujet',
	'STATCHARTS_JS_COL_TOPICS'           => 'Sujets',
	'STATCHARTS_JS_COL_FORUM'            => 'Forum',
	'STATCHARTS_JS_COL_USER'             => 'Membre',
	'STATCHARTS_JS_COL_POST'             => 'Message',
	'STATCHARTS_JS_COL_VIEWS'            => 'Vues',
	'STATCHARTS_JS_COL_POSTS'            => 'Messages',
	'STATCHARTS_JS_COL_LIKES'            => 'Remerciements',
	'STATCHARTS_JS_COL_SHARE'            => 'Part',
	'STATCHARTS_JS_COL_MONTH'            => 'Mois',
	'STATCHARTS_JS_FORUM_SELECT'         => 'Forum',
	'STATCHARTS_JS_FORUM_NOT_IN_STATS'   => 'Ce forum n’est pas inclus dans les statistiques. Choisissez-en un autre ci-dessus.',
	'STATCHARTS_JS_FORUM_HEADER'         => '{posts} messages dans {topics} sujets',
	'STATCHARTS_JS_USER_SEARCH'          => 'Rechercher un membre',
	'STATCHARTS_JS_USER_PICK'            => 'Choisissez un membre pour voir ses statistiques.',
	'STATCHARTS_JS_USER_NOT_FOUND'       => 'Aucun membre de ce nom.',
	'STATCHARTS_JS_USER_HEADER'          => '{posts} messages, {likes} remerciements reçus',
	'STATCHARTS_JS_USER_TOP_TOPICS'      => 'Sujets avec le plus de messages',
	'STATCHARTS_JS_NO_PROFILES'          => 'Vous n’avez pas la permission de voir les statistiques des membres.',
	'STATCHARTS_JS_TOPIC_SEARCH'         => 'Rechercher un sujet',
	'STATCHARTS_JS_TOPIC_ALL_FORUMS'     => 'Tous les forums',
	'STATCHARTS_JS_SORT_TITLE'           => 'Par titre',
	'STATCHARTS_JS_SORT_REPLIES'         => 'Par réponses',
	'STATCHARTS_JS_TOPIC_NOT_FOUND'      => 'Aucun sujet de ce nom.',
	'STATCHARTS_JS_TOPIC_HEADER'         => 'Créé par {poster} le {date} dans {forum}',
	'STATCHARTS_JS_TOPIC_ACTIVITY'       => 'Activité du sujet',
	'STATCHARTS_JS_TOPIC_ACTIVITY_ZOOM'  => 'Activité du sujet, agrandie',
	'STATCHARTS_JS_TOPIC_TOP_USERS'      => 'Membres les plus actifs du sujet',
	'STATCHARTS_JS_FORUM_TOP_USERS'      => 'Membres les plus actifs du forum',
	'STATCHARTS_JS_FORUM_TOP_LIKED'      => 'Membres les plus remerciés du forum',
	'STATCHARTS_JS_GO'                   => 'Aller',
	'STATCHARTS_JS_OTHER'                => 'Autres',
	'STATCHARTS_JS_HIDDEN'               => 'Forums que vous ne pouvez pas voir',
	'STATCHARTS_JS_OPEN_ON_FORUM'        => 'Ouvrir sur le forum',
	'STATCHARTS_JS_SESSION_EXPIRED'      => 'Votre session a expiré.',
	'STATCHARTS_JS_RELOAD'               => 'Recharger la page',
]);
