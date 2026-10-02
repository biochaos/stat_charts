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
$lang = array_merge($lang, [
	'STATCHARTS_TITLE'         => 'Statistics',
	'STATCHARTS_LOGIN'         => 'You need to be logged in to view the statistics.',
	'STATCHARTS_NOT_BUILT'     => 'The statistics have not been built yet.',
	'STATCHARTS_LINK_ELIDED'   => '[link]',
	'STATCHARTS_NOSCRIPT'      => 'The statistics need JavaScript.',

	'STATCHARTS_JS_TAB_GENERAL' => 'General',
	'STATCHARTS_JS_TAB_FORUM'   => 'Forum',
	'STATCHARTS_JS_TAB_TOPIC'   => 'Topic',
	'STATCHARTS_JS_TAB_USER'    => 'Member',

	'STATCHARTS_JS_AS_OF'       => 'Data as of {time}',
	'STATCHARTS_JS_NOT_BUILT'   => 'The statistics have not been built yet. They will appear after the next scheduled rebuild.',
	'STATCHARTS_JS_LOADING'     => 'Loading…',
	'STATCHARTS_JS_ERROR'       => 'The statistics could not be loaded.',
	'STATCHARTS_JS_NO_ACTIVITY' => 'No activity in this period',
	'STATCHARTS_JS_NO_ROWS'     => 'Nothing to show',

	'STATCHARTS_JS_RANGE_ALL'    => 'All time',
	'STATCHARTS_JS_RANGE_YEAR'   => 'Past year',
	'STATCHARTS_JS_RANGE_MONTH'  => 'Past month',
	'STATCHARTS_JS_RANGE_WEEK'   => 'Past week',
	'STATCHARTS_JS_RANGE_TODAY'  => 'Today',
	'STATCHARTS_JS_RANGE_CUSTOM' => 'Custom range',
	'STATCHARTS_JS_RANGE_FROM'   => 'From',
	'STATCHARTS_JS_RANGE_TO'     => 'To',
	'STATCHARTS_JS_TODAY_CAPTION' => 'Since midnight, as of {time}',
	'STATCHARTS_JS_PAGE_RANGE'       => 'Range of all charts and tables',
	'STATCHARTS_JS_PAGE_RANGE_HINT'  => 'Sets every chart and table on the page; each can then be changed on its own.',
	'STATCHARTS_JS_RANGE_OVERRIDDEN' => 'Differs from the range of the page',

	'STATCHARTS_JS_MODE_AVG'   => '7-day average',
	'STATCHARTS_JS_MODE_DAY'   => 'Per day',
	'STATCHARTS_JS_MODE_CUM'   => 'Cumulative',
	'STATCHARTS_JS_SHOW_TABLE' => 'Table',
	'STATCHARTS_JS_SHOW_CHART' => 'Chart',
	'STATCHARTS_JS_TOTAL'      => 'Total',

	'STATCHARTS_JS_POSTS_OVER_TIME'      => 'Posts over time',
	'STATCHARTS_JS_POSTS_BY_FORUM'       => 'Posts by forum',
	'STATCHARTS_JS_TOPICS_BY_FORUM'      => 'New topics by forum',
	'STATCHARTS_JS_LIKES_BY_FORUM'       => 'Thanks by forum',
	'STATCHARTS_JS_LIKES_EXPLAIN'        => 'Thanks given during the period, including thanks for older posts.',
	'STATCHARTS_JS_VIEWS_BY_FORUM'       => 'Views by forum',
	'STATCHARTS_JS_VIEWS_BY_FORUM_RANGE' => 'Views by forum, of topics started: {range}',
	'STATCHARTS_JS_VIEWS_NOTE'           => 'phpBB counts views over a topic’s whole life, so views cannot be dated. A range selects the topics started in it.',

	'STATCHARTS_JS_USERS'             => 'Members',
	'STATCHARTS_JS_USERS_REGISTERED'  => 'Registered',
	'STATCHARTS_JS_USERS_POSTED'      => 'Registered and posted',
	'STATCHARTS_JS_ACTIVE_7D'         => 'Active members (7 days)',
	'STATCHARTS_JS_ACTIVE_7D_EXPLAIN' => 'For each day, the members who posted in the seven days up to it.',

	'STATCHARTS_JS_MOST_VIEWED'       => 'Most viewed topics',
	'STATCHARTS_JS_MOST_VIEWED_RANGE' => 'Most viewed among topics started: {range}',
	'STATCHARTS_JS_MOST_ACTIVE'       => 'Most active topics',
	'STATCHARTS_JS_MOST_LIKED'        => 'Most thanked posts',

	'STATCHARTS_JS_COL_TOPIC'  => 'Topic',
	'STATCHARTS_JS_COL_TOPICS' => 'Topics',
	'STATCHARTS_JS_COL_FORUM' => 'Forum',
	'STATCHARTS_JS_COL_USER'  => 'Member',
	'STATCHARTS_JS_COL_POST'  => 'Post',
	'STATCHARTS_JS_COL_VIEWS' => 'Views',
	'STATCHARTS_JS_COL_POSTS' => 'Posts',
	'STATCHARTS_JS_COL_LIKES' => 'Thanks',
	'STATCHARTS_JS_COL_SHARE' => 'Share',
	'STATCHARTS_JS_COL_MONTH' => 'Month',

	'STATCHARTS_JS_FORUM_SELECT' => 'Forum',
	'STATCHARTS_JS_FORUM_NOT_IN_STATS' => 'This forum is not in the statistics. Choose another above.',
	'STATCHARTS_JS_FORUM_HEADER' => '{posts} posts in {topics} topics',

	'STATCHARTS_JS_USER_SEARCH'     => 'Search members',
	'STATCHARTS_JS_USER_PICK'       => 'Choose a member to see their statistics.',
	'STATCHARTS_JS_USER_NOT_FOUND'  => 'No such member.',
	'STATCHARTS_JS_USER_HEADER'     => '{posts} posts, {likes} thanks received',
	'STATCHARTS_JS_USER_TOP_TOPICS' => 'Topics with the most posts',
	'STATCHARTS_JS_NO_PROFILES'     => 'You do not have permission to view members’ statistics.',

	'STATCHARTS_JS_TOPIC_SEARCH'        => 'Search topics',
	'STATCHARTS_JS_TOPIC_ALL_FORUMS'    => 'All forums',
	'STATCHARTS_JS_SORT_TITLE'          => 'By title',
	'STATCHARTS_JS_SORT_REPLIES'        => 'By replies',
	'STATCHARTS_JS_TOPIC_NOT_FOUND'     => 'No such topic.',
	'STATCHARTS_JS_TOPIC_HEADER'        => 'Started by {poster} on {date} in {forum}',
	'STATCHARTS_JS_TOPIC_ACTIVITY'      => 'Topic activity',
	'STATCHARTS_JS_TOPIC_ACTIVITY_ZOOM' => 'Topic activity, zoomed in',
	'STATCHARTS_JS_TOPIC_TOP_USERS'     => 'Most active members in the topic',
	'STATCHARTS_JS_FORUM_TOP_USERS'     => 'Most active members in the forum',
	'STATCHARTS_JS_FORUM_TOP_LIKED'     => 'Most thanked members in the forum',

	'STATCHARTS_JS_GO'            => 'Go',
	'STATCHARTS_JS_OTHER'         => 'Other',
	'STATCHARTS_JS_HIDDEN'        => 'Forums you cannot see',
	'STATCHARTS_JS_OPEN_ON_FORUM' => 'Open on the forum',
	'STATCHARTS_JS_SESSION_EXPIRED' => 'Your session has ended.',
	'STATCHARTS_JS_RELOAD'          => 'Reload the page',
]);
