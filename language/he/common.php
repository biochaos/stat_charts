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
	'STATCHARTS_TITLE'         => 'סטטיסטיקה',
	'STATCHARTS_LOGIN'         => 'יש להתחבר כדי לצפות בסטטיסטיקה.',
	'STATCHARTS_NOT_BUILT'     => 'הסטטיסטיקה עוד לא חושבה.',
	'STATCHARTS_LINK_ELIDED'   => '[לינק]',
	'STATCHARTS_NOSCRIPT'      => 'הסטטיסטיקה דורשת JavaScript.',

	'STATCHARTS_JS_TAB_GENERAL' => 'כללי',
	'STATCHARTS_JS_TAB_FORUM'   => 'פורום',
	'STATCHARTS_JS_TAB_TOPIC'   => 'אשכול',
	'STATCHARTS_JS_TAB_USER'    => 'משתמש',

	'STATCHARTS_JS_AS_OF'       => 'הנתונים נכונים ל-{time}',
	'STATCHARTS_JS_NOT_BUILT'   => 'הסטטיסטיקה עוד לא חושבה. היא תופיע אחרי החישוב המתוזמן הבא.',
	'STATCHARTS_JS_LOADING'     => 'טוען…',
	'STATCHARTS_JS_ERROR'       => 'טעינת הסטטיסטיקה נכשלה.',
	'STATCHARTS_JS_NO_ACTIVITY' => 'אין פעילות בתקופה זו',
	'STATCHARTS_JS_NO_ROWS'     => 'אין מה להציג',

	'STATCHARTS_JS_RANGE_ALL'    => 'כל הזמנים',
	'STATCHARTS_JS_RANGE_YEAR'   => 'השנה האחרונה',
	'STATCHARTS_JS_RANGE_MONTH'  => 'החודש האחרון',
	'STATCHARTS_JS_RANGE_WEEK'   => 'השבוע האחרון',
	'STATCHARTS_JS_RANGE_TODAY'  => 'היום',
	'STATCHARTS_JS_RANGE_CUSTOM' => 'טווח מותאם',
	'STATCHARTS_JS_RANGE_FROM'   => 'מ-',
	'STATCHARTS_JS_RANGE_TO'     => 'עד',
	'STATCHARTS_JS_TODAY_CAPTION' => 'מחצות, נכון ל-{time}',
	'STATCHARTS_JS_PAGE_RANGE'       => 'טווח לכל הגרפים והטבלאות',
	'STATCHARTS_JS_PAGE_RANGE_HINT'  => 'קובע את הטווח של כל הגרפים והטבלאות בעמוד; אחר כך אפשר לשנות כל אחד בנפרד.',
	'STATCHARTS_JS_RANGE_OVERRIDDEN' => 'שונה מהטווח של העמוד',

	'STATCHARTS_JS_MODE_AVG'   => 'ממוצע 7 ימים',
	'STATCHARTS_JS_MODE_DAY'   => 'ליום',
	'STATCHARTS_JS_MODE_CUM'   => 'מצטבר',
	'STATCHARTS_JS_SHOW_TABLE' => 'טבלה',
	'STATCHARTS_JS_SHOW_CHART' => 'גרף',
	'STATCHARTS_JS_TOTAL'      => 'סך הכול',

	'STATCHARTS_JS_POSTS_OVER_TIME'      => 'הודעות לאורך זמן',
	'STATCHARTS_JS_POSTS_BY_FORUM'       => 'הודעות לפי פורום',
	'STATCHARTS_JS_TOPICS_BY_FORUM'      => 'אשכולות חדשים לפי פורום',
	'STATCHARTS_JS_LIKES_BY_FORUM'       => 'לייקים לפי פורום',
	'STATCHARTS_JS_LIKES_EXPLAIN'        => 'לייקים שניתנו בתקופה, כולל לייקים להודעות ישנות.',
	'STATCHARTS_JS_VIEWS_BY_FORUM'       => 'צפיות לפי פורום',
	'STATCHARTS_JS_VIEWS_BY_FORUM_RANGE' => 'צפיות לפי פורום, באשכולות שנפתחו: {range}',
	'STATCHARTS_JS_VIEWS_NOTE'           => 'phpBB סופר צפיות לאורך כל חיי האשכול, ולכן אי אפשר לתארך אותן. טווח זמן בוחר את האשכולות שנפתחו בו.',

	'STATCHARTS_JS_USERS'             => 'משתמשים',
	'STATCHARTS_JS_USERS_REGISTERED'  => 'נרשמו',
	'STATCHARTS_JS_USERS_POSTED'      => 'נרשמו וכתבו הודעות',
	'STATCHARTS_JS_ACTIVE_7D'         => 'משתמשים פעילים (7 ימים)',
	'STATCHARTS_JS_ACTIVE_7D_EXPLAIN' => 'לכל יום: המשתמשים שכתבו הודעה בשבעת הימים שעד אליו.',

	'STATCHARTS_JS_MOST_VIEWED'       => 'האשכולות הנצפים ביותר',
	'STATCHARTS_JS_MOST_VIEWED_RANGE' => 'הנצפים ביותר מבין האשכולות שנפתחו: {range}',
	'STATCHARTS_JS_MOST_ACTIVE'       => 'האשכולות הפעילים ביותר',
	'STATCHARTS_JS_MOST_LIKED'        => 'ההודעות עם הכי הרבה לייקים',

	'STATCHARTS_JS_COL_TOPIC'  => 'אשכול',
	'STATCHARTS_JS_COL_TOPICS' => 'אשכולות',
	'STATCHARTS_JS_COL_FORUM' => 'פורום',
	'STATCHARTS_JS_COL_USER'  => 'משתמש',
	'STATCHARTS_JS_COL_POST'  => 'הודעה',
	'STATCHARTS_JS_COL_VIEWS' => 'צפיות',
	'STATCHARTS_JS_COL_POSTS' => 'הודעות',
	'STATCHARTS_JS_COL_LIKES' => 'לייקים',
	'STATCHARTS_JS_COL_SHARE' => 'חלק',
	'STATCHARTS_JS_COL_MONTH' => 'חודש',

	'STATCHARTS_JS_FORUM_SELECT' => 'פורום',
	'STATCHARTS_JS_FORUM_NOT_IN_STATS' => 'הפורום הזה לא נכלל בסטטיסטיקה. אפשר לבחור פורום אחר למעלה.',
	'STATCHARTS_JS_FORUM_HEADER' => '{posts} הודעות ב-{topics} אשכולות',

	'STATCHARTS_JS_USER_SEARCH'     => 'חיפוש משתמש',
	'STATCHARTS_JS_USER_PICK'       => 'בחרו משתמש כדי לראות את הסטטיסטיקה שלו.',
	'STATCHARTS_JS_USER_NOT_FOUND'  => 'אין משתמש כזה.',
	'STATCHARTS_JS_USER_HEADER'     => '{posts} הודעות, {likes} לייקים',
	'STATCHARTS_JS_USER_TOP_TOPICS' => 'אשכולות עם הכי הרבה הודעות',
	'STATCHARTS_JS_NO_PROFILES'     => 'אין לך הרשאה לצפות בסטטיסטיקה של משתמשים.',

	'STATCHARTS_JS_TOPIC_SEARCH'        => 'חיפוש אשכול',
	'STATCHARTS_JS_TOPIC_ALL_FORUMS'    => 'כל הפורומים',
	'STATCHARTS_JS_SORT_TITLE'          => 'לפי שם',
	'STATCHARTS_JS_SORT_REPLIES'        => 'לפי מספר תגובות',
	'STATCHARTS_JS_TOPIC_NOT_FOUND'     => 'אין אשכול כזה.',
	'STATCHARTS_JS_TOPIC_HEADER'        => 'נפתח על ידי {poster} ב-{date} בפורום {forum}',
	'STATCHARTS_JS_TOPIC_ACTIVITY'      => 'פעילות באשכול',
	'STATCHARTS_JS_TOPIC_ACTIVITY_ZOOM' => 'פעילות באשכול, הגדלה',
	'STATCHARTS_JS_TOPIC_TOP_USERS'     => 'המשתמשים הפעילים באשכול',
	'STATCHARTS_JS_FORUM_TOP_USERS'     => 'המשתמשים הפעילים בפורום',
	'STATCHARTS_JS_FORUM_TOP_LIKED'     => 'המשתמשים עם הכי הרבה לייקים בפורום',

	'STATCHARTS_JS_GO'            => 'מעבר',
	'STATCHARTS_JS_OTHER'         => 'אחר',
	'STATCHARTS_JS_HIDDEN'        => 'פורומים מוסתרים',
	'STATCHARTS_JS_OPEN_ON_FORUM' => 'פתיחה בפורום',
	'STATCHARTS_JS_SESSION_EXPIRED' => 'פג תוקף החיבור שלך.',
	'STATCHARTS_JS_RELOAD'          => 'טעינה מחדש של הדף',
]);
