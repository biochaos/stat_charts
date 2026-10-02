<?php
/**
 * Stat Charts extension for phpBB.
 *
 * @copyright (c) 2026, biochaos
 * @license GNU General Public License, version 2 (GPL-2.0)
 */

namespace biochaos\statcharts\migrations;

class install_config extends \phpbb\db\migration\migration
{
	public function effectively_installed()
	{
		return isset($this->config['statcharts_rebuild_state']);
	}

	public static function depends_on()
	{
		return ['\biochaos\statcharts\migrations\install_schema'];
	}

	public function update_data()
	{
		return [
			// Settings. Comma-separated forum ids; descendants are excluded too.
			['config.add', ['statcharts_excluded_forums', '']],
			['config.add', ['statcharts_rebuild_interval', 3600]],
			// Count guests' and deleted members' posts as active users (SPEC 15).
			['config.add', ['statcharts_count_anonymous', 1]],

			// Presentation, read-time only (SPEC sections 6 and 15, 6c).
			// separate | top_level | category
			['config.add', ['statcharts_grouping', 'top_level']],
			// Y-m-d; empty = the first post.
			['config.add', ['statcharts_start_date', '']],

			// Rebuild state machine (SPEC section 5). Dynamic: written every chunk.
			['config.add', ['statcharts_rebuild_state', 'idle', true]],
			['config.add', ['statcharts_rebuild_step', 0, true]],
			['config.add', ['statcharts_rebuild_cursor', 0, true]],
			['config.add', ['statcharts_rebuild_started', 0, true]],
			['config.add', ['statcharts_rebuild_last', 0, true]],
			['config.add', ['statcharts_rebuild_lock', '0', true]],

			// Parameters frozen at build start, so every invocation of one build
			// uses the same timezone, forum set and post-id ceiling.
			['config_text.add', ['statcharts_rebuild_params', '']],
		];
	}

	public function revert_data()
	{
		return [
			['config.remove', ['statcharts_excluded_forums']],
			['config.remove', ['statcharts_rebuild_interval']],
			['config.remove', ['statcharts_count_anonymous']],
			['config.remove', ['statcharts_grouping']],
			['config.remove', ['statcharts_start_date']],
			['config.remove', ['statcharts_rebuild_state']],
			['config.remove', ['statcharts_rebuild_step']],
			['config.remove', ['statcharts_rebuild_cursor']],
			['config.remove', ['statcharts_rebuild_started']],
			['config.remove', ['statcharts_rebuild_last']],
			['config.remove', ['statcharts_rebuild_lock']],
			['config_text.remove', ['statcharts_rebuild_params']],
		];
	}
}
