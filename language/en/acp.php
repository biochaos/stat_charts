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
	'ACP_STATCHARTS_EXPLAIN'         => 'The statistics page shows posts, topics, thanks and members over time, by forum, member and topic. Only the settings under “What the statistics include” need a rebuild; everything else applies at once.',
	'ACP_STATCHARTS_NOT_CONFIGURED'  => 'Not configured yet. Set up in this order: 1. how forums are grouped, 2. the palettes (the built-in ones suit most boards), 3. what the statistics include, 4. the order and colours of the groups; then build the statistics.',

	// Status
	'ACP_STATCHARTS_STATUS'          => 'Status',
	'ACP_STATCHARTS_NOT_BUILT'       => 'The statistics have not been built yet. Build them now, or they will be built by the scheduled task within the hour.',
	'ACP_STATCHARTS_LAST_BUILD'      => 'Last built',
	'ACP_STATCHARTS_NEXT_BUILD'      => 'Next rebuild due',
	'ACP_STATCHARTS_NEXT_BUILD_EXPLAIN' => 'The scheduled task runs on the first page view after this time. On a quiet board that can be later.',
	'ACP_STATCHARTS_OVERDUE'         => 'The statistics are overdue for a rebuild: the scheduled task has not run for over two intervals. On a quiet board, page views that trigger it are rare; consider running phpbbcli.php statcharts:rebuild from a system timer (see the README).',
	'ACP_STATCHARTS_DIRTY'           => 'The statistics were built with different settings and need a rebuild:',
	'ACP_STATCHARTS_DIRTY_EXCLUDED'  => 'the excluded forums have changed.',
	'ACP_STATCHARTS_DIRTY_ANONYMOUS' => 'counting posts without an account has changed.',
	'ACP_STATCHARTS_DIRTY_TIMEZONE'  => 'the board timezone has changed (%1$s to %2$s), so every day boundary moves.',
	'ACP_STATCHARTS_BUILDING'        => 'A rebuild is in progress.',
	'ACP_STATCHARTS_BUILD_NOW'       => 'Build now',
	'ACP_STATCHARTS_REBUILD_NOW'     => 'Rebuild now',
	'ACP_STATCHARTS_PURGE_WARNING'   => 'Purging the extension (Customise → Manage extensions → Delete data) drops all statistics tables and every setting on this page, custom palettes included. The statistics themselves can always be rebuilt from the forum; the settings cannot.',
	'ACP_STATCHARTS_STEP'            => 'Step %1$d of %2$d: %3$s',
	'ACP_STATCHARTS_STEP_CREATE'     => 'preparing',
	'ACP_STATCHARTS_STEP_DIMENSIONS' => 'forums and members',
	'ACP_STATCHARTS_STEP_TOPICS'     => 'topics',
	'ACP_STATCHARTS_STEP_POSTS'      => 'posts',
	'ACP_STATCHARTS_STEP_LIKES'      => 'thanks',
	'ACP_STATCHARTS_STEP_MERGE_POSTS' => 'adding up posts',
	'ACP_STATCHARTS_STEP_MERGE_LIKES' => 'adding up thanks',
	'ACP_STATCHARTS_STEP_USERS'      => 'active members',
	'ACP_STATCHARTS_STEP_TOTALS'     => 'totals',
	'ACP_STATCHARTS_STEP_SWAP'       => 'switching over',

	// 1. Display
	'ACP_STATCHARTS_DISPLAY'         => '1. How forums are grouped',
	'ACP_STATCHARTS_GROUPING'        => 'Group forums',
	'ACP_STATCHARTS_MODE_SEPARATE'   => 'Separate',
	'ACP_STATCHARTS_MODE_SEPARATE_EXPLAIN' => 'Every forum and sub-forum is its own slice.',
	'ACP_STATCHARTS_MODE_TOP_LEVEL'  => 'Top-level',
	'ACP_STATCHARTS_MODE_TOP_LEVEL_EXPLAIN' => 'Sub-forums are counted in the forum they sit in, all the way up to the forum below any category.',
	'ACP_STATCHARTS_MODE_CATEGORY'   => 'By category',
	'ACP_STATCHARTS_MODE_CATEGORY_EXPLAIN' => 'Each category becomes one slice, adding up every forum and sub-forum inside it. A forum that is not inside any category has no category to join, so it keeps its own slice, as in “top-level”. On a board with no categories this mode therefore looks exactly like “top-level”.',
	'ACP_STATCHARTS_START_DATE'      => 'Start date',
	'ACP_STATCHARTS_START_DATE_EXPLAIN' => 'Charts start here. Leave empty to start at the first post. Totals that run over time (members, cumulative posts) still count everything before it. Views are counted over a topic’s whole life and cannot follow a start date.',

	// 2. Colours
	'ACP_STATCHARTS_COLOURS'         => '4. Order and colours',
	'ACP_STATCHARTS_COLOURS_EXPLAIN' => 'Each forum is given one of eight colour positions, and keeps it wherever it appears, in every range. Each palette paints the positions its own way, so the table shows every palette in use, one row each. The built-in palettes use the same hue at each position, shaded for their background: position 4 is yellow in both. Changing the order never repaints anything. Neighbours that are hard to tell apart are named below the table.',
	'ACP_STATCHARTS_ORDER_CUSTOM'    => 'A custom order is in use.',
	'ACP_STATCHARTS_RESET_ORDER'     => 'Reset to forum order',
	'ACP_STATCHARTS_REASSIGN'        => 'Re-assign colours in this order',
	'ACP_STATCHARTS_REASSIGN_EXPLAIN' => 'Gives colour 1 to the first forum, 2 to the second, and so on: neighbours are then the pairs the palette is checked for. This repaints the forums whose colour changes.',
	'ACP_STATCHARTS_DISABLED_SLOTS'  => 'Don’t use',
	'ACP_STATCHARTS_DISABLED_SLOTS_EXPLAIN' => '(in any palette; only the forum that had it moves)',

	// 3. Palettes
	'ACP_STATCHARTS_PALETTES'        => '2. Palettes',
	'ACP_STATCHARTS_PALETTES_EXPLAIN' => 'A chart paints its own background from its palette, so a palette works on any style. phpBB cannot tell which styles are dark, so choose a palette for each one.',
	'ACP_STATCHARTS_PALETTE_DEFAULT' => 'Default palette',
	'ACP_STATCHARTS_PALETTE_STYLE'   => 'Style “%s”',
	'ACP_STATCHARTS_PALETTE_INACTIVE' => '(inactive)',
	'ACP_STATCHARTS_CUSTOM'          => 'Custom palettes',
	'ACP_STATCHARTS_CUSTOM_EXPLAIN'  => 'Start from a copy of an existing palette. A palette is eight colours in a fixed order, the grey for “Other”, and the background, text and grid colours it is checked against. Problems are reported but never block saving.',
	'ACP_STATCHARTS_CREATE_FROM'     => 'Create a custom palette from',
	'ACP_STATCHARTS_CREATE'          => 'Create',
	'ACP_STATCHARTS_PALETTE_NAME'    => 'Name',
	'ACP_STATCHARTS_TOKEN_OTHER'     => 'Other',
	'ACP_STATCHARTS_TOKEN_SURFACE'   => 'Background',
	'ACP_STATCHARTS_TOKEN_INK'       => 'Text',
	'ACP_STATCHARTS_TOKEN_INK2'      => 'Secondary text',
	'ACP_STATCHARTS_TOKEN_AXIS'      => 'Axis and borders',
	'ACP_STATCHARTS_TOKEN_GRID'      => 'Grid',
	'ACP_STATCHARTS_PRESET_LIGHT'    => 'Light (built in)',
	'ACP_STATCHARTS_PRESET_DARK'     => 'Dark (built in)',
	'ACP_STATCHARTS_DELETE_PALETTE'  => 'Delete',

	// 4. Data
	'ACP_STATCHARTS_DATA'            => '3. What the statistics include',
	'ACP_STATCHARTS_DATA_EXPLAIN'    => 'Changes here apply at the next rebuild.',
	'ACP_STATCHARTS_EXCLUDED'        => 'Excluded forums',
	'ACP_STATCHARTS_EXCLUDED_EXPLAIN' => 'Nothing from these forums, or from any forum inside them, enters the statistics. Choose several with Ctrl or Cmd.',
	'ACP_STATCHARTS_COUNT_ANONYMOUS' => 'Count posts without an account as active members',
	'ACP_STATCHARTS_COUNT_ANONYMOUS_EXPLAIN' => 'phpBB files two kinds of post without an account: posts by <strong>guests</strong>, and the posts of <strong>deleted members</strong> whose posts were kept (phpBB moves them to the anonymous account under the name the member had). The two are stored identically, so this setting covers both.<br><br><strong>Yes:</strong> each name counts as one person on the days it posted. <em>Guests:</em> a guest who posts counts as an active member; guests pick their own name each time, so two guests using the same name count as one, and one guest using two names counts as two. <em>Deleted members:</em> a deleted member still counts as active on the days they posted, as they did while their account existed.<br><br><strong>No:</strong> posts without an account never count toward active members. <em>Guests:</em> guest activity is invisible in the active-member charts. <em>Deleted members:</em> deleting a member removes them from the active-member history retroactively.<br><br>Post, topic and thanks totals include these posts either way.',

	// Results
	'ACP_STATCHARTS_SAVED'           => 'Settings saved.',
	'ACP_STATCHARTS_SAVED_REBUILD'   => 'Settings saved. They apply at the next rebuild; rebuild now from the status panel to see them at once.',
	'ACP_STATCHARTS_ORDER_RESET'     => 'The forum order is the board’s own again.',
	'ACP_STATCHARTS_REASSIGNED'      => 'Colours re-assigned in display order.',
	'ACP_STATCHARTS_PALETTE_CREATED' => 'Custom palette created. Edit it under “Custom palettes”.',
	'ACP_STATCHARTS_PALETTE_DELETE_CONFIRM' => 'Delete the palette “%s”? Styles that use it will use the default palette.',
	'ACP_STATCHARTS_PALETTE_DELETED' => 'Palette deleted. Styles that used it now use the default.',
	'ACP_STATCHARTS_COPY_OF'         => 'Copy of %s',

	// For the page script
	'ACP_STATCHARTS_JS_SLOT'         => 'Colour %s',
	'ACP_STATCHARTS_JS_OTHER'        => 'Other (grey)',
	'ACP_STATCHARTS_JS_NEW_FORUM'    => 'new: given a colour by itself',
	'ACP_STATCHARTS_JS_NEW_IN_ORDER' => 'new: added at the end of your order',
	'ACP_STATCHARTS_JS_MOVE_UP'      => 'Move up',
	'ACP_STATCHARTS_JS_MOVE_DOWN'    => 'Move down',
	'ACP_STATCHARTS_JS_TOO_MANY'     => 'This grouping makes %d groups, and there are 8 colours. Choose below which get their own colour; the rest share the grey “Other”.',
	'ACP_STATCHARTS_JS_TOO_MANY_ALT' => 'Or choose “%1$s” instead: that makes %2$d.',
	'ACP_STATCHARTS_JS_CHECK_BAND'   => 'Lightness: %s is too light or too dark for this background.',
	'ACP_STATCHARTS_JS_CHECK_CHROMA' => 'Colourfulness: %s is so grey it could be read as “Other”.',
	'ACP_STATCHARTS_JS_CHECK_CVD'    => 'Colour blindness: %1$s and %2$s look alike to readers with red-green colour blindness (difference %3$s, below 6).',
	'ACP_STATCHARTS_JS_CHECK_CVD_FLOOR' => 'Colour blindness: %1$s and %2$s are close for readers with red-green colour blindness (difference %3$s, below 8); the labels and the table view carry them.',
	'ACP_STATCHARTS_JS_CHECK_NORMAL' => 'Hard to tell apart: %1$s and %2$s (difference %3$s, below 15).',
	'ACP_STATCHARTS_JS_CHECK_CONTRAST' => 'Contrast: %s is faint on this background (%s:1, below 3:1); the labels and the table view carry it.',
	'ACP_STATCHARTS_JS_NEIGHBOURS'   => 'Neighbours as drawn with “%s”:',
	'ACP_STATCHARTS_JS_NEIGHBOURS_OK' => 'Neighbours as drawn with “%s”: all easy to tell apart.',
	'ACP_STATCHARTS_JS_PALETTE_OK'   => 'All checks pass.',
	'ACP_STATCHARTS_JS_PALETTE_FOR'  => 'used by: %s',
	'ACP_STATCHARTS_JS_WRAP'         => '(last and first touch in a pie)',
	'ACP_STATCHARTS_JS_DEFAULT'      => 'default',
	'ACP_STATCHARTS_JS_BUILDING'     => 'Building…',
	'ACP_STATCHARTS_JS_BUILT'        => 'Done. Reloading…',
	'ACP_STATCHARTS_JS_LOCKED'       => 'The scheduled task is building right now; waiting for it…',
	'ACP_STATCHARTS_JS_BUILD_FAILED' => 'The rebuild stopped with an error. It resumes where it left off: try again.',
	'ACP_STATCHARTS_JS_MODE_SEPARATE'  => 'Separate',
	'ACP_STATCHARTS_JS_MODE_TOP_LEVEL' => 'Top-level',
	'ACP_STATCHARTS_JS_MODE_CATEGORY'  => 'By category',
]);
