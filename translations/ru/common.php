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
	'STATCHARTS_TITLE'                   => 'Статистика',
	'STATCHARTS_LOGIN'                   => 'Чтобы увидеть статистику, нужно войти на форум.',
	'STATCHARTS_NOT_BUILT'               => 'Статистика ещё не подсчитана.',
	'STATCHARTS_LINK_ELIDED'             => '[ссылка]',
	'STATCHARTS_NOSCRIPT'                => 'Для статистики нужен JavaScript.',
	'STATCHARTS_JS_TAB_GENERAL'          => 'Общая',
	'STATCHARTS_JS_TAB_FORUM'            => 'Форум',
	'STATCHARTS_JS_TAB_TOPIC'            => 'Тема',
	'STATCHARTS_JS_TAB_USER'             => 'Пользователь',
	'STATCHARTS_JS_AS_OF'                => 'Данные на {time}',
	'STATCHARTS_JS_NOT_BUILT'            => 'Статистика ещё не подсчитана. Она появится после следующего планового пересчёта.',
	'STATCHARTS_JS_LOADING'              => 'Загрузка…',
	'STATCHARTS_JS_ERROR'                => 'Не удалось загрузить статистику.',
	'STATCHARTS_JS_NO_ACTIVITY'          => 'За этот период активности нет',
	'STATCHARTS_JS_NO_ROWS'              => 'Нечего показать',
	'STATCHARTS_JS_RANGE_ALL'            => 'За всё время',
	'STATCHARTS_JS_RANGE_YEAR'           => 'За год',
	'STATCHARTS_JS_RANGE_MONTH'          => 'За месяц',
	'STATCHARTS_JS_RANGE_WEEK'           => 'За неделю',
	'STATCHARTS_JS_RANGE_TODAY'          => 'Сегодня',
	'STATCHARTS_JS_RANGE_CUSTOM'         => 'Свой период',
	'STATCHARTS_JS_RANGE_FROM'           => 'С',
	'STATCHARTS_JS_RANGE_TO'             => 'По',
	'STATCHARTS_JS_TODAY_CAPTION'        => 'С полуночи, на {time}',
	'STATCHARTS_JS_PAGE_RANGE'           => 'Период всех графиков и таблиц',
	'STATCHARTS_JS_PAGE_RANGE_HINT'      => 'Задаёт период для всех графиков и таблиц страницы; затем каждый можно изменить отдельно.',
	'STATCHARTS_JS_RANGE_OVERRIDDEN'     => 'Отличается от периода страницы',
	'STATCHARTS_JS_MODE_AVG'             => 'Среднее за 7 дней',
	'STATCHARTS_JS_MODE_DAY'             => 'За день',
	'STATCHARTS_JS_MODE_CUM'             => 'Нарастающим итогом',
	'STATCHARTS_JS_SHOW_TABLE'           => 'Таблица',
	'STATCHARTS_JS_SHOW_CHART'           => 'График',
	'STATCHARTS_JS_TOTAL'                => 'Всего',
	'STATCHARTS_JS_POSTS_OVER_TIME'      => 'Сообщения во времени',
	'STATCHARTS_JS_POSTS_BY_FORUM'       => 'Сообщения по форумам',
	'STATCHARTS_JS_TOPICS_BY_FORUM'      => 'Новые темы по форумам',
	'STATCHARTS_JS_LIKES_BY_FORUM'       => 'Благодарности по форумам',
	'STATCHARTS_JS_LIKES_EXPLAIN'        => 'Благодарности, выданные за период, в том числе за более старые сообщения.',
	'STATCHARTS_JS_VIEWS_BY_FORUM'       => 'Просмотры по форумам',
	'STATCHARTS_JS_VIEWS_BY_FORUM_RANGE' => 'Просмотры по форумам, темы, созданные: {range}',
	'STATCHARTS_JS_VIEWS_NOTE'           => 'phpBB считает просмотры за всю жизнь темы, поэтому их нельзя привязать к датам. Период отбирает темы, созданные в нём.',
	'STATCHARTS_JS_USERS'                => 'Пользователи',
	'STATCHARTS_JS_USERS_REGISTERED'     => 'Зарегистрированные',
	'STATCHARTS_JS_USERS_POSTED'         => 'Зарегистрированные и писавшие',
	'STATCHARTS_JS_ACTIVE_7D'            => 'Активные пользователи (7 дней)',
	'STATCHARTS_JS_ACTIVE_7D_EXPLAIN'    => 'Для каждого дня — пользователи, писавшие в течение семи дней до него включительно.',
	'STATCHARTS_JS_MOST_VIEWED'          => 'Самые просматриваемые темы',
	'STATCHARTS_JS_MOST_VIEWED_RANGE'    => 'Самые просматриваемые среди тем, созданных: {range}',
	'STATCHARTS_JS_MOST_ACTIVE'          => 'Самые активные темы',
	'STATCHARTS_JS_MOST_LIKED'           => 'Сообщения с наибольшим числом благодарностей',
	'STATCHARTS_JS_COL_TOPIC'            => 'Тема',
	'STATCHARTS_JS_COL_TOPICS'           => 'Темы',
	'STATCHARTS_JS_COL_FORUM'            => 'Форум',
	'STATCHARTS_JS_COL_USER'             => 'Пользователь',
	'STATCHARTS_JS_COL_POST'             => 'Сообщение',
	'STATCHARTS_JS_COL_VIEWS'            => 'Просмотры',
	'STATCHARTS_JS_COL_POSTS'            => 'Сообщения',
	'STATCHARTS_JS_COL_LIKES'            => 'Благодарности',
	'STATCHARTS_JS_COL_SHARE'            => 'Доля',
	'STATCHARTS_JS_COL_MONTH'            => 'Месяц',
	'STATCHARTS_JS_FORUM_SELECT'         => 'Форум',
	'STATCHARTS_JS_FORUM_NOT_IN_STATS'   => 'Этот форум не входит в статистику. Выберите другой выше.',
	'STATCHARTS_JS_FORUM_HEADER'         => 'Сообщений: {posts}, тем: {topics}',
	'STATCHARTS_JS_USER_SEARCH'          => 'Поиск пользователей',
	'STATCHARTS_JS_USER_PICK'            => 'Выберите пользователя, чтобы увидеть его статистику.',
	'STATCHARTS_JS_USER_NOT_FOUND'       => 'Такого пользователя нет.',
	'STATCHARTS_JS_USER_HEADER'          => 'Сообщений: {posts}, получено благодарностей: {likes}',
	'STATCHARTS_JS_USER_TOP_TOPICS'      => 'Темы с наибольшим числом сообщений',
	'STATCHARTS_JS_NO_PROFILES'          => 'У вас нет прав на просмотр статистики пользователей.',
	'STATCHARTS_JS_TOPIC_SEARCH'         => 'Поиск тем',
	'STATCHARTS_JS_TOPIC_ALL_FORUMS'     => 'Все форумы',
	'STATCHARTS_JS_SORT_TITLE'           => 'По названию',
	'STATCHARTS_JS_SORT_REPLIES'         => 'По ответам',
	'STATCHARTS_JS_TOPIC_NOT_FOUND'      => 'Такой темы нет.',
	'STATCHARTS_JS_TOPIC_HEADER'         => 'Создал(а) {poster} {date} в форуме {forum}',
	'STATCHARTS_JS_TOPIC_ACTIVITY'       => 'Активность в теме',
	'STATCHARTS_JS_TOPIC_ACTIVITY_ZOOM'  => 'Активность в теме, крупнее',
	'STATCHARTS_JS_TOPIC_TOP_USERS'      => 'Самые активные пользователи темы',
	'STATCHARTS_JS_FORUM_TOP_USERS'      => 'Самые активные пользователи форума',
	'STATCHARTS_JS_FORUM_TOP_LIKED'      => 'Пользователи с наибольшим числом благодарностей в форуме',
	'STATCHARTS_JS_GO'                   => 'Перейти',
	'STATCHARTS_JS_OTHER'                => 'Другие',
	'STATCHARTS_JS_HIDDEN'               => 'Форумы, которые вы не видите',
	'STATCHARTS_JS_OPEN_ON_FORUM'        => 'Открыть на форуме',
	'STATCHARTS_JS_SESSION_EXPIRED'      => 'Ваш сеанс завершён.',
	'STATCHARTS_JS_RELOAD'               => 'Перезагрузить страницу',
]);
