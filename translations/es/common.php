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
	'STATCHARTS_TITLE'                   => 'Estadísticas',
	'STATCHARTS_LOGIN'                   => 'Debe iniciar sesión para ver las estadísticas.',
	'STATCHARTS_NOT_BUILT'               => 'Las estadísticas aún no se han calculado.',
	'STATCHARTS_LINK_ELIDED'             => '[enlace]',
	'STATCHARTS_NOSCRIPT'                => 'Las estadísticas necesitan JavaScript.',
	'STATCHARTS_JS_TAB_GENERAL'          => 'General',
	'STATCHARTS_JS_TAB_FORUM'            => 'Foro',
	'STATCHARTS_JS_TAB_TOPIC'            => 'Tema',
	'STATCHARTS_JS_TAB_USER'             => 'Miembro',
	'STATCHARTS_JS_AS_OF'                => 'Datos a {time}',
	'STATCHARTS_JS_NOT_BUILT'            => 'Las estadísticas aún no se han calculado. Aparecerán tras el próximo recálculo programado.',
	'STATCHARTS_JS_LOADING'              => 'Cargando…',
	'STATCHARTS_JS_ERROR'                => 'No se pudieron cargar las estadísticas.',
	'STATCHARTS_JS_NO_ACTIVITY'          => 'Sin actividad en este periodo',
	'STATCHARTS_JS_NO_ROWS'              => 'Nada que mostrar',
	'STATCHARTS_JS_RANGE_ALL'            => 'Desde siempre',
	'STATCHARTS_JS_RANGE_YEAR'           => 'Último año',
	'STATCHARTS_JS_RANGE_MONTH'          => 'Último mes',
	'STATCHARTS_JS_RANGE_WEEK'           => 'Última semana',
	'STATCHARTS_JS_RANGE_TODAY'          => 'Hoy',
	'STATCHARTS_JS_RANGE_CUSTOM'         => 'Periodo personalizado',
	'STATCHARTS_JS_RANGE_FROM'           => 'Desde',
	'STATCHARTS_JS_RANGE_TO'             => 'Hasta',
	'STATCHARTS_JS_TODAY_CAPTION'        => 'Desde medianoche, a {time}',
	'STATCHARTS_JS_PAGE_RANGE'           => 'Periodo de todos los gráficos y tablas',
	'STATCHARTS_JS_PAGE_RANGE_HINT'      => 'Se aplica a todos los gráficos y tablas de la página; después, cada uno se puede cambiar por separado.',
	'STATCHARTS_JS_RANGE_OVERRIDDEN'     => 'Distinto del periodo de la página',
	'STATCHARTS_JS_MODE_AVG'             => 'Media de 7 días',
	'STATCHARTS_JS_MODE_DAY'             => 'Por día',
	'STATCHARTS_JS_MODE_CUM'             => 'Acumulado',
	'STATCHARTS_JS_SHOW_TABLE'           => 'Tabla',
	'STATCHARTS_JS_SHOW_CHART'           => 'Gráfico',
	'STATCHARTS_JS_TOTAL'                => 'Total',
	'STATCHARTS_JS_POSTS_OVER_TIME'      => 'Mensajes a lo largo del tiempo',
	'STATCHARTS_JS_POSTS_BY_FORUM'       => 'Mensajes por foro',
	'STATCHARTS_JS_TOPICS_BY_FORUM'      => 'Temas nuevos por foro',
	'STATCHARTS_JS_LIKES_BY_FORUM'       => 'Agradecimientos por foro',
	'STATCHARTS_JS_LIKES_EXPLAIN'        => 'Agradecimientos dados en el periodo, incluidos los de mensajes más antiguos.',
	'STATCHARTS_JS_VIEWS_BY_FORUM'       => 'Visitas por foro',
	'STATCHARTS_JS_VIEWS_BY_FORUM_RANGE' => 'Visitas por foro, de los temas creados: {range}',
	'STATCHARTS_JS_VIEWS_NOTE'           => 'phpBB cuenta las visitas durante toda la vida de un tema, así que no se pueden fechar. Un periodo selecciona los temas creados en él.',
	'STATCHARTS_JS_USERS'                => 'Miembros',
	'STATCHARTS_JS_USERS_REGISTERED'     => 'Registrados',
	'STATCHARTS_JS_USERS_POSTED'         => 'Registrados que han escrito',
	'STATCHARTS_JS_ACTIVE_7D'            => 'Miembros activos (7 días)',
	'STATCHARTS_JS_ACTIVE_7D_EXPLAIN'    => 'Para cada día, los miembros que escribieron en los siete días hasta ese día.',
	'STATCHARTS_JS_MOST_VIEWED'          => 'Temas más vistos',
	'STATCHARTS_JS_MOST_VIEWED_RANGE'    => 'Los más vistos entre los temas creados: {range}',
	'STATCHARTS_JS_MOST_ACTIVE'          => 'Temas más activos',
	'STATCHARTS_JS_MOST_LIKED'           => 'Mensajes más agradecidos',
	'STATCHARTS_JS_COL_TOPIC'            => 'Tema',
	'STATCHARTS_JS_COL_TOPICS'           => 'Temas',
	'STATCHARTS_JS_COL_FORUM'            => 'Foro',
	'STATCHARTS_JS_COL_USER'             => 'Miembro',
	'STATCHARTS_JS_COL_POST'             => 'Mensaje',
	'STATCHARTS_JS_COL_VIEWS'            => 'Visitas',
	'STATCHARTS_JS_COL_POSTS'            => 'Mensajes',
	'STATCHARTS_JS_COL_LIKES'            => 'Agradecimientos',
	'STATCHARTS_JS_COL_SHARE'            => 'Proporción',
	'STATCHARTS_JS_COL_MONTH'            => 'Mes',
	'STATCHARTS_JS_FORUM_SELECT'         => 'Foro',
	'STATCHARTS_JS_FORUM_NOT_IN_STATS'   => 'Este foro no está incluido en las estadísticas. Elija otro arriba.',
	'STATCHARTS_JS_FORUM_HEADER'         => '{posts} mensajes en {topics} temas',
	'STATCHARTS_JS_USER_SEARCH'          => 'Buscar miembros',
	'STATCHARTS_JS_USER_PICK'            => 'Elija un miembro para ver sus estadísticas.',
	'STATCHARTS_JS_USER_NOT_FOUND'       => 'No existe ese miembro.',
	'STATCHARTS_JS_USER_HEADER'          => '{posts} mensajes, {likes} agradecimientos recibidos',
	'STATCHARTS_JS_USER_TOP_TOPICS'      => 'Temas con más mensajes',
	'STATCHARTS_JS_NO_PROFILES'          => 'No tiene permiso para ver las estadísticas de los miembros.',
	'STATCHARTS_JS_TOPIC_SEARCH'         => 'Buscar temas',
	'STATCHARTS_JS_TOPIC_ALL_FORUMS'     => 'Todos los foros',
	'STATCHARTS_JS_SORT_TITLE'           => 'Por título',
	'STATCHARTS_JS_SORT_REPLIES'         => 'Por respuestas',
	'STATCHARTS_JS_TOPIC_NOT_FOUND'      => 'No existe ese tema.',
	'STATCHARTS_JS_TOPIC_HEADER'         => 'Creado por {poster} el {date} en {forum}',
	'STATCHARTS_JS_TOPIC_ACTIVITY'       => 'Actividad del tema',
	'STATCHARTS_JS_TOPIC_ACTIVITY_ZOOM'  => 'Actividad del tema, ampliada',
	'STATCHARTS_JS_TOPIC_TOP_USERS'      => 'Miembros más activos en el tema',
	'STATCHARTS_JS_FORUM_TOP_USERS'      => 'Miembros más activos en el foro',
	'STATCHARTS_JS_FORUM_TOP_LIKED'      => 'Miembros más agradecidos en el foro',
	'STATCHARTS_JS_GO'                   => 'Ir',
	'STATCHARTS_JS_OTHER'                => 'Otros',
	'STATCHARTS_JS_HIDDEN'               => 'Foros que no puede ver',
	'STATCHARTS_JS_OPEN_ON_FORUM'        => 'Abrir en el foro',
	'STATCHARTS_JS_SESSION_EXPIRED'      => 'Su sesión ha caducado.',
	'STATCHARTS_JS_RELOAD'               => 'Recargar la página',
]);
