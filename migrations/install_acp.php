<?php
/**
 * Stat Charts extension for phpBB.
 *
 * @copyright (c) 2026, biochaos
 * @license GNU General Public License, version 2 (GPL-2.0)
 */

namespace biochaos\statcharts\migrations;

/**
 * The ACP settings page (SPEC section 9) and the presentation data it edits
 * (section 7). All of it is instant to change; none needs a rebuild.
 */
class install_acp extends \phpbb\db\migration\migration
{
	public function effectively_installed()
	{
		$sql = 'SELECT module_id FROM ' . $this->table_prefix . "modules
			WHERE module_class = 'acp' AND module_langname = 'ACP_STATCHARTS'";
		$result = $this->db->sql_query($sql);
		$row = $this->db->sql_fetchrow($result);
		$this->db->sql_freeresult($result);
		return (bool) $row;
	}

	public static function depends_on()
	{
		return ['\biochaos\statcharts\migrations\install_config'];
	}

	public function update_data()
	{
		return [
			// Set once the admin first saves the page; until then it says "not configured yet".
			['config.add', ['statcharts_configured', 0]],

			// Palettes: {style_path: 'preset:<id>' | 'custom:<id>', '*': default}, and custom ones by value.
			['config_text.add', ['statcharts_style_palettes', '']],
			['config_text.add', ['statcharts_custom_palettes', '']],
			// Colour per forum ({forum_id: slot | 'other'}), disabled slots, custom order, new-forum notices.
			['config_text.add', ['statcharts_colour_slots', '']],
			['config_text.add', ['statcharts_disabled_slots', '']],
			['config_text.add', ['statcharts_entity_order', '']],
			['config_text.add', ['statcharts_colour_notices', '']],

			['module.add', ['acp', 'ACP_CAT_DOT_MODS', 'ACP_STATCHARTS']],
			['module.add', ['acp', 'ACP_STATCHARTS', [
				'module_basename' => '\biochaos\statcharts\acp\main_module',
				'modes'           => ['settings'],
			]]],
		];
	}

	public function revert_data()
	{
		return [
			['config.remove', ['statcharts_configured']],
			['config_text.remove', ['statcharts_style_palettes']],
			['config_text.remove', ['statcharts_custom_palettes']],
			['config_text.remove', ['statcharts_colour_slots']],
			['config_text.remove', ['statcharts_disabled_slots']],
			['config_text.remove', ['statcharts_entity_order']],
			['config_text.remove', ['statcharts_colour_notices']],
			['module.remove', ['acp', 'ACP_CAT_DOT_MODS', 'ACP_STATCHARTS']],
		];
	}
}
