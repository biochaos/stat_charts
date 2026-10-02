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

$lang = array_merge($lang, [
	'ACP_STATCHARTS_EXPLAIN'         => 'עמוד הסטטיסטיקה מציג הודעות, אשכולות, לייקים ומשתמשים לאורך זמן, לפי פורום, משתמש ואשכול. רק ההגדרות תחת „מה נכלל בסטטיסטיקה” דורשות חישוב מחדש; כל השאר חל מיד.',
	'ACP_STATCHARTS_NOT_CONFIGURED'  => 'עוד לא הוגדר. כדאי להגדיר לפי הסדר: 1. איך הפורומים מקובצים, 2. ערכות הצבעים (המובנות מתאימות לרוב הפורומים), 3. מה נכלל בסטטיסטיקה, 4. הסדר והצבעים של הקבוצות; ואז לחשב את הסטטיסטיקה.',

	// Status
	'ACP_STATCHARTS_STATUS'          => 'מצב',
	'ACP_STATCHARTS_NOT_BUILT'       => 'הסטטיסטיקה עוד לא חושבה. אפשר לחשב אותה עכשיו, או שהמשימה המתוזמנת תחשב אותה בתוך שעה.',
	'ACP_STATCHARTS_LAST_BUILD'      => 'חושבה לאחרונה',
	'ACP_STATCHARTS_NEXT_BUILD'      => 'החישוב הבא',
	'ACP_STATCHARTS_NEXT_BUILD_EXPLAIN' => 'המשימה המתוזמנת רצה בצפייה הראשונה בעמוד אחרי מועד זה. בפורום שקט זה יכול לקרות מאוחר יותר.',
	'ACP_STATCHARTS_OVERDUE'         => 'החישוב באיחור: המשימה המתוזמנת לא רצה יותר משני מחזורים. בפורום שקט צפיות שמפעילות אותה נדירות; אפשר להריץ את phpbbcli.php statcharts:rebuild מטיימר של המערכת (ראו README).',
	'ACP_STATCHARTS_DIRTY'           => 'הסטטיסטיקה חושבה עם הגדרות אחרות וצריך לחשב אותה מחדש:',
	'ACP_STATCHARTS_DIRTY_EXCLUDED'  => 'הפורומים המוחרגים השתנו.',
	'ACP_STATCHARTS_DIRTY_ANONYMOUS' => 'ההגדרה של ספירת הודעות ללא חשבון השתנתה.',
	'ACP_STATCHARTS_DIRTY_TIMEZONE'  => 'אזור הזמן של הפורום השתנה (מ-%1$s ל-%2$s), ולכן כל גבולות הימים זזים.',
	'ACP_STATCHARTS_BUILDING'        => 'חישוב מחדש מתבצע כעת.',
	'ACP_STATCHARTS_BUILD_NOW'       => 'לחשב עכשיו',
	'ACP_STATCHARTS_REBUILD_NOW'     => 'לחשב מחדש עכשיו',
	'ACP_STATCHARTS_PURGE_WARNING'   => 'מחיקת נתוני ההרחבה (התאמה אישית ← ניהול הרחבות ← מחיקת נתונים) מוחקת את כל טבלאות הסטטיסטיקה ואת כל ההגדרות בעמוד זה, כולל ערכות צבעים מותאמות. את הסטטיסטיקה עצמה תמיד אפשר לחשב מחדש מהפורום; את ההגדרות לא.',
	'ACP_STATCHARTS_STEP'            => 'שלב %1$d מתוך %2$d: %3$s',
	'ACP_STATCHARTS_STEP_CREATE'     => 'הכנה',
	'ACP_STATCHARTS_STEP_DIMENSIONS' => 'פורומים ומשתמשים',
	'ACP_STATCHARTS_STEP_TOPICS'     => 'אשכולות',
	'ACP_STATCHARTS_STEP_POSTS'      => 'הודעות',
	'ACP_STATCHARTS_STEP_LIKES'      => 'לייקים',
	'ACP_STATCHARTS_STEP_MERGE_POSTS' => 'סיכום הודעות',
	'ACP_STATCHARTS_STEP_MERGE_LIKES' => 'סיכום לייקים',
	'ACP_STATCHARTS_STEP_USERS'      => 'משתמשים פעילים',
	'ACP_STATCHARTS_STEP_TOTALS'     => 'סיכומים',
	'ACP_STATCHARTS_STEP_SWAP'       => 'החלפה',

	// 1. Display
	'ACP_STATCHARTS_DISPLAY'         => '1. איך הפורומים מקובצים',
	'ACP_STATCHARTS_GROUPING'        => 'קיבוץ פורומים',
	'ACP_STATCHARTS_MODE_SEPARATE'   => 'בנפרד',
	'ACP_STATCHARTS_MODE_SEPARATE_EXPLAIN' => 'כל פורום ותת-פורום הוא פלח נפרד.',
	'ACP_STATCHARTS_MODE_TOP_LEVEL'  => 'רמה עליונה',
	'ACP_STATCHARTS_MODE_TOP_LEVEL_EXPLAIN' => 'תת-פורומים נספרים בפורום שהם נמצאים בו, עד לפורום שמתחת לקטגוריה.',
	'ACP_STATCHARTS_MODE_CATEGORY'   => 'לפי קטגוריה',
	'ACP_STATCHARTS_MODE_CATEGORY_EXPLAIN' => 'כל קטגוריה היא פלח אחד, שמסכם את כל הפורומים ותתי-הפורומים שבתוכה. לפורום שאינו בתוך קטגוריה אין קטגוריה להצטרף אליה, ולכן הוא נשאר פלח בפני עצמו, כמו ב„רמה עליונה”. בפורום בלי קטגוריות מצב זה נראה בדיוק כמו „רמה עליונה”.',
	'ACP_STATCHARTS_START_DATE'      => 'תאריך התחלה',
	'ACP_STATCHARTS_START_DATE_EXPLAIN' => 'הגרפים מתחילים כאן. ריק: מתחילים בהודעה הראשונה. סכומים מצטברים (משתמשים, הודעות מצטברות) עדיין סופרים את כל מה שלפניו. צפיות נספרות לאורך כל חיי האשכול ואינן יכולות להתחשב בתאריך התחלה.',

	// 2. Colours
	'ACP_STATCHARTS_COLOURS'         => '4. סדר וצבעים',
	'ACP_STATCHARTS_COLOURS_EXPLAIN' => 'כל פורום מקבל אחד משמונה מיקומי צבע, ושומר עליו בכל מקום ובכל טווח. כל ערכת צבעים צובעת את המיקומים בדרך שלה, ולכן הטבלה מציגה את כל הערכות שבשימוש, שורה לכל אחת. הערכות המובנות משתמשות באותו גוון בכל מיקום, בגוון המתאים לרקע שלהן: מיקום 4 צהוב בשתיהן. שינוי הסדר אף פעם לא צובע מחדש. שכנים שקשה להבחין ביניהם מפורטים מתחת לטבלה.',
	'ACP_STATCHARTS_ORDER_CUSTOM'    => 'בשימוש סדר מותאם.',
	'ACP_STATCHARTS_RESET_ORDER'     => 'חזרה לסדר הפורומים',
	'ACP_STATCHARTS_REASSIGN'        => 'הקצאת צבעים מחדש לפי הסדר הזה',
	'ACP_STATCHARTS_REASSIGN_EXPLAIN' => 'צבע 1 לפורום הראשון, 2 לשני וכן הלאה: כך השכנים הם הזוגות שערכת הצבעים נבדקה עבורם. פורומים שהצבע שלהם משתנה ייצבעו מחדש.',
	'ACP_STATCHARTS_DISABLED_SLOTS'  => 'לא להשתמש',
	'ACP_STATCHARTS_DISABLED_SLOTS_EXPLAIN' => '(בשום ערכה; רק הפורום שהיה צבוע בו עובר לצבע אחר)',

	// 3. Palettes
	'ACP_STATCHARTS_PALETTES'        => '2. ערכות צבעים',
	'ACP_STATCHARTS_PALETTES_EXPLAIN' => 'גרף צובע את הרקע שלו לפי ערכת הצבעים, כך שכל ערכה עובדת עם כל סגנון. phpBB לא יודע אילו סגנונות כהים, ולכן יש לבחור ערכה לכל סגנון.',
	'ACP_STATCHARTS_PALETTE_DEFAULT' => 'ערכת ברירת המחדל',
	'ACP_STATCHARTS_PALETTE_STYLE'   => 'סגנון „%s”',
	'ACP_STATCHARTS_PALETTE_INACTIVE' => '(לא פעיל)',
	'ACP_STATCHARTS_CUSTOM'          => 'ערכות מותאמות',
	'ACP_STATCHARTS_CUSTOM_EXPLAIN'  => 'מתחילים מעותק של ערכה קיימת. ערכה היא שמונה צבעים בסדר קבוע, האפור של „אחר”, וצבעי הרקע, הטקסט והרשת שהיא נבדקת מולם. בעיות מדווחות אבל אף פעם לא מונעות שמירה.',
	'ACP_STATCHARTS_CREATE_FROM'     => 'יצירת ערכה מותאמת מתוך',
	'ACP_STATCHARTS_CREATE'          => 'יצירה',
	'ACP_STATCHARTS_PALETTE_NAME'    => 'שם',
	'ACP_STATCHARTS_TOKEN_OTHER'     => 'אחר',
	'ACP_STATCHARTS_TOKEN_SURFACE'   => 'רקע',
	'ACP_STATCHARTS_TOKEN_INK'       => 'טקסט',
	'ACP_STATCHARTS_TOKEN_INK2'      => 'טקסט משני',
	'ACP_STATCHARTS_TOKEN_AXIS'      => 'צירים ומסגרות',
	'ACP_STATCHARTS_TOKEN_GRID'      => 'רשת',
	'ACP_STATCHARTS_PRESET_LIGHT'    => 'בהירה (מובנית)',
	'ACP_STATCHARTS_PRESET_DARK'     => 'כהה (מובנית)',
	'ACP_STATCHARTS_DELETE_PALETTE'  => 'מחיקה',

	// 4. Data
	'ACP_STATCHARTS_DATA'            => '3. מה נכלל בסטטיסטיקה',
	'ACP_STATCHARTS_DATA_EXPLAIN'    => 'שינויים כאן חלים בחישוב הבא.',
	'ACP_STATCHARTS_EXCLUDED'        => 'פורומים מוחרגים',
	'ACP_STATCHARTS_EXCLUDED_EXPLAIN' => 'שום דבר מפורומים אלה, או מפורום שבתוכם, לא נכנס לסטטיסטיקה. לבחירה של כמה: Ctrl או Cmd.',
	'ACP_STATCHARTS_COUNT_ANONYMOUS' => 'לספור הודעות ללא חשבון כמשתמשים פעילים',
	'ACP_STATCHARTS_COUNT_ANONYMOUS_EXPLAIN' => 'phpBB שומר שני סוגים של הודעות ללא חשבון: הודעות של <strong>אורחים</strong>, והודעות של <strong>משתמשים שנמחקו</strong> שההודעות שלהם נשמרו (phpBB מעביר אותן לחשבון האנונימי תחת השם שהיה למשתמש). שני הסוגים נשמרים באותו אופן, ולכן ההגדרה חלה על שניהם.<br><br><strong>כן:</strong> כל שם נספר כאדם אחד בימים שבהם כתב. <em>אורחים:</em> אורח שכותב נספר כמשתמש פעיל; אורחים בוחרים שם בכל פעם, כך ששני אורחים עם אותו שם נספרים כאחד, ואורח אחד עם שני שמות נספר כשניים. <em>משתמשים שנמחקו:</em> משתמש שנמחק עדיין נספר כפעיל בימים שבהם כתב, כמו כשהחשבון שלו היה קיים.<br><br><strong>לא:</strong> הודעות ללא חשבון לעולם לא נספרות במשתמשים פעילים. <em>אורחים:</em> פעילות של אורחים לא נראית בגרפים של משתמשים פעילים. <em>משתמשים שנמחקו:</em> מחיקת משתמש מסירה אותו מהיסטוריית המשתמשים הפעילים גם למפרע.<br><br>סיכומי ההודעות, האשכולות והלייקים כוללים הודעות אלה בכל מקרה.',

	// Results
	'ACP_STATCHARTS_SAVED'           => 'ההגדרות נשמרו.',
	'ACP_STATCHARTS_SAVED_REBUILD'   => 'ההגדרות נשמרו. הן יחולו בחישוב הבא; אפשר לחשב מחדש עכשיו מלוח המצב כדי לראות אותן מיד.',
	'ACP_STATCHARTS_ORDER_RESET'     => 'הסדר חזר להיות סדר הפורומים.',
	'ACP_STATCHARTS_REASSIGNED'      => 'הצבעים הוקצו מחדש לפי סדר התצוגה.',
	'ACP_STATCHARTS_PALETTE_CREATED' => 'הערכה המותאמת נוצרה. אפשר לערוך אותה תחת „ערכות מותאמות”.',
	'ACP_STATCHARTS_PALETTE_DELETE_CONFIRM' => 'למחוק את הערכה „%s”? סגנונות שמשתמשים בה יעברו לברירת המחדל.',
	'ACP_STATCHARTS_PALETTE_DELETED' => 'הערכה נמחקה. סגנונות שהשתמשו בה משתמשים עכשיו בברירת המחדל.',
	'ACP_STATCHARTS_COPY_OF'         => 'עותק של %s',

	// For the page script
	'ACP_STATCHARTS_JS_SLOT'         => 'צבע %s',
	'ACP_STATCHARTS_JS_OTHER'        => 'אחר (אפור)',
	'ACP_STATCHARTS_JS_NEW_FORUM'    => 'חדש: קיבל צבע אוטומטית',
	'ACP_STATCHARTS_JS_NEW_IN_ORDER' => 'חדש: נוסף בסוף הסדר שלך',
	'ACP_STATCHARTS_JS_MOVE_UP'      => 'למעלה',
	'ACP_STATCHARTS_JS_MOVE_DOWN'    => 'למטה',
	'ACP_STATCHARTS_JS_TOO_MANY'     => 'הקיבוץ הזה יוצר %d קבוצות, ויש 8 צבעים. יש לבחור למטה אילו יקבלו צבע משלהן; השאר יחלקו את האפור של „אחר”.',
	'ACP_STATCHARTS_JS_TOO_MANY_ALT' => 'או לבחור „%1$s” במקום: זה יוצר %2$d.',
	'ACP_STATCHARTS_JS_CHECK_BAND'   => 'בהירות: %s בהיר או כהה מדי לרקע הזה.',
	'ACP_STATCHARTS_JS_CHECK_CHROMA' => 'רוויה: %s אפור עד כדי כך שאפשר לבלבל אותו עם „אחר”.',
	'ACP_STATCHARTS_JS_CHECK_CVD'    => 'עיוורון צבעים: %1$s ו-%2$s נראים דומים לקוראים עם עיוורון אדום-ירוק (הפרש %3$s, מתחת ל-6).',
	'ACP_STATCHARTS_JS_CHECK_CVD_FLOOR' => 'עיוורון צבעים: %1$s ו-%2$s קרובים לקוראים עם עיוורון אדום-ירוק (הפרש %3$s, מתחת ל-8); התוויות ותצוגת הטבלה מבחינות ביניהם.',
	'ACP_STATCHARTS_JS_CHECK_NORMAL' => 'קשה להבחין: %1$s ו-%2$s (הפרש %3$s, מתחת ל-15).',
	'ACP_STATCHARTS_JS_CHECK_CONTRAST' => 'ניגודיות: %s חלש על הרקע הזה (%s:1, מתחת ל-3:1); התוויות ותצוגת הטבלה מבחינות בו.',
	'ACP_STATCHARTS_JS_NEIGHBOURS'   => 'שכנים כפי שהם מצוירים ב„%s”:',
	'ACP_STATCHARTS_JS_NEIGHBOURS_OK' => 'שכנים כפי שהם מצוירים ב„%s”: קל להבחין בין כולם.',
	'ACP_STATCHARTS_JS_PALETTE_OK'   => 'כל הבדיקות עברו.',
	'ACP_STATCHARTS_JS_PALETTE_FOR'  => 'בשימוש: %s',
	'ACP_STATCHARTS_JS_WRAP'         => '(האחרון והראשון נוגעים בעוגה)',
	'ACP_STATCHARTS_JS_DEFAULT'      => 'ברירת מחדל',
	'ACP_STATCHARTS_JS_BUILDING'     => 'מחשב…',
	'ACP_STATCHARTS_JS_BUILT'        => 'הסתיים. טוען מחדש…',
	'ACP_STATCHARTS_JS_LOCKED'       => 'המשימה המתוזמנת מחשבת עכשיו; ממתין לה…',
	'ACP_STATCHARTS_JS_BUILD_FAILED' => 'החישוב נעצר בשגיאה. הוא ממשיך מהמקום שבו עצר: אפשר לנסות שוב.',
	'ACP_STATCHARTS_JS_MODE_SEPARATE'  => 'בנפרד',
	'ACP_STATCHARTS_JS_MODE_TOP_LEVEL' => 'רמה עליונה',
	'ACP_STATCHARTS_JS_MODE_CATEGORY'  => 'לפי קטגוריה',
]);
