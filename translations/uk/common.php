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
	'STATCHARTS_LOGIN'                   => 'Щоб побачити статистику, потрібно увійти на форум.',
	'STATCHARTS_NOT_BUILT'               => 'Статистику ще не підраховано.',
	'STATCHARTS_LINK_ELIDED'             => '[посилання]',
	'STATCHARTS_NOSCRIPT'                => 'Для статистики потрібен JavaScript.',
	'STATCHARTS_JS_TAB_GENERAL'          => 'Загальна',
	'STATCHARTS_JS_TAB_FORUM'            => 'Форум',
	'STATCHARTS_JS_TAB_TOPIC'            => 'Тема',
	'STATCHARTS_JS_TAB_USER'             => 'Користувач',
	'STATCHARTS_JS_AS_OF'                => 'Дані на {time}',
	'STATCHARTS_JS_NOT_BUILT'            => 'Статистику ще не підраховано. Вона з’явиться після наступного планового перерахунку.',
	'STATCHARTS_JS_LOADING'              => 'Завантаження…',
	'STATCHARTS_JS_ERROR'                => 'Не вдалося завантажити статистику.',
	'STATCHARTS_JS_NO_ACTIVITY'          => 'За цей період активності немає',
	'STATCHARTS_JS_NO_ROWS'              => 'Нічого показати',
	'STATCHARTS_JS_RANGE_ALL'            => 'За весь час',
	'STATCHARTS_JS_RANGE_YEAR'           => 'За рік',
	'STATCHARTS_JS_RANGE_MONTH'          => 'За місяць',
	'STATCHARTS_JS_RANGE_WEEK'           => 'За тиждень',
	'STATCHARTS_JS_RANGE_TODAY'          => 'Сьогодні',
	'STATCHARTS_JS_RANGE_CUSTOM'         => 'Свій період',
	'STATCHARTS_JS_RANGE_FROM'           => 'З',
	'STATCHARTS_JS_RANGE_TO'             => 'По',
	'STATCHARTS_JS_TODAY_CAPTION'        => 'Від півночі, станом на {time}',
	'STATCHARTS_JS_PAGE_RANGE'           => 'Період усіх графіків і таблиць',
	'STATCHARTS_JS_PAGE_RANGE_HINT'      => 'Задає період для всіх графіків і таблиць сторінки; потім кожен можна змінити окремо.',
	'STATCHARTS_JS_RANGE_OVERRIDDEN'     => 'Відрізняється від періоду сторінки',
	'STATCHARTS_JS_MODE_AVG'             => 'Середнє за 7 днів',
	'STATCHARTS_JS_MODE_DAY'             => 'За день',
	'STATCHARTS_JS_MODE_CUM'             => 'Наростаючим підсумком',
	'STATCHARTS_JS_SHOW_TABLE'           => 'Таблиця',
	'STATCHARTS_JS_SHOW_CHART'           => 'Графік',
	'STATCHARTS_JS_TOTAL'                => 'Усього',
	'STATCHARTS_JS_POSTS_OVER_TIME'      => 'Повідомлення в часі',
	'STATCHARTS_JS_POSTS_BY_FORUM'       => 'Повідомлення за форумами',
	'STATCHARTS_JS_TOPICS_BY_FORUM'      => 'Нові теми за форумами',
	'STATCHARTS_JS_LIKES_BY_FORUM'       => 'Подяки за форумами',
	'STATCHARTS_JS_LIKES_EXPLAIN'        => 'Подяки, надані за період, зокрема за старіші повідомлення.',
	'STATCHARTS_JS_VIEWS_BY_FORUM'       => 'Перегляди за форумами',
	'STATCHARTS_JS_VIEWS_BY_FORUM_RANGE' => 'Перегляди за форумами, теми, створені: {range}',
	'STATCHARTS_JS_VIEWS_NOTE'           => 'phpBB рахує перегляди за все життя теми, тому їх не можна прив’язати до дат. Період відбирає теми, створені в ньому.',
	'STATCHARTS_JS_USERS'                => 'Користувачі',
	'STATCHARTS_JS_USERS_REGISTERED'     => 'Зареєстровані',
	'STATCHARTS_JS_USERS_POSTED'         => 'Зареєстровані, які писали',
	'STATCHARTS_JS_ACTIVE_7D'            => 'Активні користувачі (7 днів)',
	'STATCHARTS_JS_ACTIVE_7D_EXPLAIN'    => 'Для кожного дня — користувачі, які писали протягом семи днів до нього включно.',
	'STATCHARTS_JS_MOST_VIEWED'          => 'Найпереглядуваніші теми',
	'STATCHARTS_JS_MOST_VIEWED_RANGE'    => 'Найпереглядуваніші серед тем, створених: {range}',
	'STATCHARTS_JS_MOST_ACTIVE'          => 'Найактивніші теми',
	'STATCHARTS_JS_MOST_LIKED'           => 'Повідомлення з найбільшою кількістю подяк',
	'STATCHARTS_JS_COL_TOPIC'            => 'Тема',
	'STATCHARTS_JS_COL_TOPICS'           => 'Теми',
	'STATCHARTS_JS_COL_FORUM'            => 'Форум',
	'STATCHARTS_JS_COL_USER'             => 'Користувач',
	'STATCHARTS_JS_COL_POST'             => 'Повідомлення',
	'STATCHARTS_JS_COL_VIEWS'            => 'Перегляди',
	'STATCHARTS_JS_COL_POSTS'            => 'Повідомлення',
	'STATCHARTS_JS_COL_LIKES'            => 'Подяки',
	'STATCHARTS_JS_COL_SHARE'            => 'Частка',
	'STATCHARTS_JS_COL_MONTH'            => 'Місяць',
	'STATCHARTS_JS_FORUM_SELECT'         => 'Форум',
	'STATCHARTS_JS_FORUM_NOT_IN_STATS'   => 'Цей форум не входить до статистики. Виберіть інший вище.',
	'STATCHARTS_JS_FORUM_HEADER'         => 'Повідомлень: {posts}, тем: {topics}',
	'STATCHARTS_JS_USER_SEARCH'          => 'Пошук користувачів',
	'STATCHARTS_JS_USER_PICK'            => 'Виберіть користувача, щоб побачити його статистику.',
	'STATCHARTS_JS_USER_NOT_FOUND'       => 'Такого користувача немає.',
	'STATCHARTS_JS_USER_HEADER'          => 'Повідомлень: {posts}, отримано подяк: {likes}',
	'STATCHARTS_JS_USER_TOP_TOPICS'      => 'Теми з найбільшою кількістю повідомлень',
	'STATCHARTS_JS_NO_PROFILES'          => 'Ви не маєте права переглядати статистику користувачів.',
	'STATCHARTS_JS_TOPIC_SEARCH'         => 'Пошук тем',
	'STATCHARTS_JS_TOPIC_ALL_FORUMS'     => 'Усі форуми',
	'STATCHARTS_JS_SORT_TITLE'           => 'За назвою',
	'STATCHARTS_JS_SORT_REPLIES'         => 'За відповідями',
	'STATCHARTS_JS_TOPIC_NOT_FOUND'      => 'Такої теми немає.',
	'STATCHARTS_JS_TOPIC_HEADER'         => 'Створив(ла) {poster} {date} у форумі {forum}',
	'STATCHARTS_JS_TOPIC_ACTIVITY'       => 'Активність у темі',
	'STATCHARTS_JS_TOPIC_ACTIVITY_ZOOM'  => 'Активність у темі, збільшено',
	'STATCHARTS_JS_TOPIC_TOP_USERS'      => 'Найактивніші користувачі теми',
	'STATCHARTS_JS_FORUM_TOP_USERS'      => 'Найактивніші користувачі форуму',
	'STATCHARTS_JS_FORUM_TOP_LIKED'      => 'Користувачі з найбільшою кількістю подяк у форумі',
	'STATCHARTS_JS_GO'                   => 'Перейти',
	'STATCHARTS_JS_OTHER'                => 'Інші',
	'STATCHARTS_JS_HIDDEN'               => 'Форуми, яких ви не бачите',
	'STATCHARTS_JS_OPEN_ON_FORUM'        => 'Відкрити на форумі',
	'STATCHARTS_JS_SESSION_EXPIRED'      => 'Ваш сеанс завершився.',
	'STATCHARTS_JS_RELOAD'               => 'Перезавантажити сторінку',
]);
