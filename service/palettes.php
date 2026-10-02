<?php
/**
 * Stat Charts extension for phpBB.
 *
 * @copyright (c) 2026, biochaos
 * @license GNU General Public License, version 2 (GPL-2.0)
 */

namespace biochaos\statcharts\service;

use phpbb\config\db_text;

/**
 * Chart palettes (SPEC section 7, Appendix A). Presets are code, referenced by
 * id, so a style assigned "preset:dark" picks up improvements on update.
 * Custom palettes, the per-style assignment and pinned colours are data in
 * config_text, written by the ACP (phase 4); until then every style gets the
 * light preset and colours follow display order.
 */
class palettes
{
	const DEFAULT_REF = 'preset:light';

	/** Machine-validated values; do not hand-edit without re-validating (Appendix A). */
	const PRESETS = [
		'light' => [
			'slots'   => ['#2a78d6', '#eb6834', '#1baf7a', '#eda100', '#e87ba4', '#008300', '#4a3aa7', '#e34948'],
			'other'   => '#8c8c8c',
			'surface' => '#ffffff',
			'ink'     => '#333333',
			'ink2'    => '#666666',
			'axis'    => '#cccccc',
			'grid'    => '#ecf0f1',
		],
		'dark' => [
			'slots'   => ['#3987e5', '#d95926', '#199e70', '#c98500', '#d55181', '#008300', '#9085e9', '#e66767'],
			'other'   => '#8c8c8c',
			'surface' => '#1f2124',
			'ink'     => '#e8e8e8',
			'ink2'    => '#a0a0a0',
			'axis'    => '#3a3d42',
			'grid'    => '#3a3d42',
		],
	];

	/** @var db_text */
	private $config_text;

	public function __construct(db_text $config_text)
	{
		$this->config_text = $config_text;
	}

	/**
	 * The palette assigned to a style, keyed by its directory (style_path),
	 * which survives a rename; falls back to the default. Never inferred from
	 * the style's CSS (SPEC section 7, Palette assignment).
	 *
	 * @return array Palette tokens, plus 'id' and 'light' (whether the light-mode
	 *               relief - direct labels, table view - is required)
	 */
	public function for_style($style_path)
	{
		$map = $this->json('statcharts_style_palettes');
		$ref = isset($map[$style_path]) ? $map[$style_path] : (isset($map['*']) ? $map['*'] : self::DEFAULT_REF);
		return $this->resolve((string) $ref);
	}

	/** Colour tokens a palette carries besides its eight slots (SPEC section 7). */
	const TOKENS = ['other', 'surface', 'ink', 'ink2', 'axis', 'grid'];

	/**
	 * Every palette an admin can assign, presets first.
	 *
	 * @return array [ref => ['ref', 'name', 'custom' (bool), tokens...]]
	 */
	public function all()
	{
		$out = [];
		foreach (self::PRESETS as $id => $palette)
		{
			$out['preset:' . $id] = $palette + ['ref' => 'preset:' . $id, 'name' => $id, 'custom' => false];
		}
		foreach ($this->json('statcharts_custom_palettes') as $id => $palette)
		{
			$out['custom:' . $id] = $palette + ['ref' => 'custom:' . $id, 'custom' => true];
		}
		return $out;
	}

	/** @return array [style_path => ref], plus '*' => the default */
	public function assignments()
	{
		$map = $this->json('statcharts_style_palettes');
		$map += ['*' => self::DEFAULT_REF];
		return $map;
	}

	/**
	 * @param array $map [style_path => ref], '*' for the default; unknown refs are dropped
	 */
	public function save_assignments(array $map)
	{
		$known = $this->all();
		$clean = [];
		foreach ($map as $style => $ref)
		{
			if (isset($known[$ref]))
			{
				$clean[(string) $style] = $ref;
			}
		}
		$this->config_text->set('statcharts_style_palettes', json_encode($clean, JSON_FORCE_OBJECT));
	}

	/**
	 * Create a custom palette as a copy of another (never a blank form), or
	 * save one. Custom palettes are data, so an extension update never
	 * touches them (SPEC section 7).
	 *
	 * @param string|null $id     Null to create
	 * @param array       $values name, slots (8 × #rrggbb) and TOKENS
	 * @return string The palette's id
	 */
	public function save_custom($id, array $values)
	{
		$custom = $this->json('statcharts_custom_palettes');
		if ($id === null || !isset($custom[$id]))
		{
			$id = 'c' . base_convert((string) time(), 10, 36) . substr(hash('sha256', uniqid('', true)), 0, 4);
		}

		$hex = function ($value, $fallback) {
			$value = strtolower(trim((string) $value));
			return preg_match('/^#[0-9a-f]{6}$/', $value) ? $value : $fallback;
		};
		$base = self::PRESETS['light'];
		$palette = ['name' => utf8_substr(trim((string) (isset($values['name']) ? $values['name'] : '')), 0, 60) ?: $id, 'slots' => []];
		for ($i = 0; $i < 8; $i++)
		{
			$palette['slots'][] = $hex(isset($values['slots'][$i]) ? $values['slots'][$i] : '', $base['slots'][$i]);
		}
		foreach (self::TOKENS as $token)
		{
			$palette[$token] = $hex(isset($values[$token]) ? $values[$token] : '', $base[$token]);
		}

		$custom[$id] = $palette;
		$this->config_text->set('statcharts_custom_palettes', json_encode($custom, JSON_FORCE_OBJECT));
		return $id;
	}

	/** Styles that used a deleted palette fall back to the default. */
	public function delete_custom($id)
	{
		$custom = $this->json('statcharts_custom_palettes');
		unset($custom[$id]);
		$this->config_text->set('statcharts_custom_palettes', $custom ? json_encode($custom, JSON_FORCE_OBJECT) : '');

		$map = $this->json('statcharts_style_palettes');
		foreach ($map as $style => $ref)
		{
			if ($ref === 'custom:' . $id)
			{
				unset($map[$style]);
			}
		}
		$this->config_text->set('statcharts_style_palettes', $map ? json_encode($map, JSON_FORCE_OBJECT) : '');
	}

	/** @return array [forum_id => slot index], as pinned by the ACP */
	public function pinned_slots()
	{
		return $this->json('statcharts_colour_slots');
	}

	/** @return int[] Slot indexes the admin has disabled */
	public function disabled_slots()
	{
		return array_values(array_map('intval', $this->json('statcharts_disabled_slots')));
	}

	private function resolve($ref)
	{
		list($kind, $id) = array_pad(explode(':', $ref, 2), 2, '');

		if ($kind === 'custom')
		{
			$custom = $this->json('statcharts_custom_palettes');
			if (isset($custom[$id]['slots']))
			{
				return $custom[$id] + ['id' => $ref, 'light' => $this->is_light($custom[$id]['surface'])];
			}
		}

		$id = isset(self::PRESETS[$id]) ? $id : 'light';
		return self::PRESETS[$id] + ['id' => 'preset:' . $id, 'light' => $this->is_light(self::PRESETS[$id]['surface'])];
	}

	private function is_light($hex)
	{
		$hex = ltrim((string) $hex, '#');
		if (strlen($hex) !== 6)
		{
			return true;
		}
		list($r, $g, $b) = array_map('hexdec', str_split($hex, 2));
		return (0.299 * $r + 0.587 * $g + 0.114 * $b) > 127;
	}

	private function json($name)
	{
		$value = json_decode((string) $this->config_text->get($name), true);
		return is_array($value) ? $value : [];
	}
}
