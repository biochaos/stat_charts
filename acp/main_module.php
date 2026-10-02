<?php
/**
 * Stat Charts extension for phpBB.
 *
 * @copyright (c) 2026, biochaos
 * @license GNU General Public License, version 2 (GPL-2.0)
 */

namespace biochaos\statcharts\acp;

use biochaos\statcharts\service\colours;
use biochaos\statcharts\service\grouping;

/**
 * The one settings page (SPEC section 9): sections in setup order - grouping,
 * then entity order and colours, then build - plus palettes, what the stats
 * include, and the status panel. Each section saves on its own. Everything but
 * "what the stats include" is presentation, saved instantly with no rebuild.
 */
class main_module
{
	public $u_action;
	public $tpl_name;
	public $page_title;

	/** @var \Symfony\Component\DependencyInjection\ContainerInterface */
	private $container;

	/** @var \phpbb\config\config */
	private $config;

	/** @var \phpbb\language\language */
	private $language;

	/** @var \phpbb\request\request_interface */
	private $request;

	/** @var \phpbb\template\template */
	private $template;

	/** @var colours */
	private $colours;

	/** @var \biochaos\statcharts\service\palettes */
	private $palettes;

	/** @var \biochaos\statcharts\service\forums */
	private $forums;

	public function main($id, $mode)
	{
		global $phpbb_container;

		$this->container = $phpbb_container;
		$this->config    = $phpbb_container->get('config');
		$this->language  = $phpbb_container->get('language');
		$this->request   = $phpbb_container->get('request');
		$this->template  = $phpbb_container->get('template');
		$this->colours   = $phpbb_container->get('biochaos.statcharts.colours');
		$this->palettes  = $phpbb_container->get('biochaos.statcharts.palettes');
		$this->forums    = $phpbb_container->get('biochaos.statcharts.forums');

		$this->language->add_lang(['acp', 'common'], 'biochaos/statcharts');
		$this->tpl_name   = 'acp_statcharts';
		$this->page_title = 'ACP_STATCHARTS_SETTINGS';

		if ($this->request->variable('action', '') === 'rebuild' && $this->request->is_ajax())
		{
			$this->rebuild_step();
		}

		// Deleting a palette asks first. confirm_box() has its own key, and its
		// second request does not carry this page's form key.
		if ($this->request->variable('action', '') === 'custom_delete')
		{
			$this->delete_palette();
		}

		add_form_key('statcharts_settings');
		if ($this->request->is_set_post('action'))
		{
			if (!check_form_key('statcharts_settings'))
			{
				trigger_error($this->language->lang('FORM_INVALID') . adm_back_link($this->u_action), E_USER_WARNING);
			}
			$message = $this->save($this->request->variable('action', ''));
			trigger_error($this->language->lang($message) . adm_back_link($this->u_action));
		}

		// First sight: a forum created since the last visit takes its colour now.
		$this->colours->sync();
		$this->assign_page();
	}

	/* ------------------------------------------------------------------ */
	/* Saving                                                              */
	/* ------------------------------------------------------------------ */

	/** @return string Language key of the confirmation */
	private function save($action)
	{
		switch ($action)
		{
			case 'display':
				$mode = $this->request->variable('grouping', '');
				$this->config->set('statcharts_grouping', in_array($mode, grouping::MODES, true) ? $mode : grouping::TOP_LEVEL);
				$date = $this->request->variable('start_date', '');
				$this->config->set('statcharts_start_date', preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) ? $date : '');
				$this->config->set('statcharts_configured', 1);
				$this->colours->sync(false);
				return 'ACP_STATCHARTS_SAVED';

			case 'colours':
				$order = array_filter(array_map('intval', explode(',', $this->request->variable('order', ''))));
				if ($order && $order !== array_column($this->colours->entities(), 'id'))
				{
					$this->colours->set_order($order);
				}
				$choices = [];
				foreach ($this->request->variable('slot', ['' => '']) as $forum_id => $slot)
				{
					$choices[(int) $forum_id] = $slot === colours::OTHER ? colours::OTHER : (int) $slot;
				}
				$this->colours->set_slots($choices);
				$this->colours->set_disabled($this->request->variable('disabled', [0]));
				$this->colours->clear_notices();
				$this->config->set('statcharts_configured', 1);
				return 'ACP_STATCHARTS_SAVED';

			case 'reset_order':
				$this->colours->reset_order();
				return 'ACP_STATCHARTS_ORDER_RESET';

			case 'reassign':
				$this->colours->reassign_in_order();
				return 'ACP_STATCHARTS_REASSIGNED';

			case 'palettes':
				$map = ['*' => $this->request->variable('palette_default', 'preset:light')];
				foreach ($this->request->variable('palette_style', ['' => '']) as $style => $ref)
				{
					// A style set to the default's palette by name keeps it when the default changes.
					if ($ref !== '')
					{
						$map[$style] = $ref;
					}
				}
				$this->palettes->save_assignments($map);
				return 'ACP_STATCHARTS_SAVED';

			case 'custom_create':
				$from = $this->request->variable('copy_from', 'preset:light');
				$all = $this->palettes->all();
				$source = isset($all[$from]) ? $all[$from] : $all['preset:light'];
				$name = $source['custom'] ? $source['name'] : $this->language->lang('ACP_STATCHARTS_PRESET_' . strtoupper($source['name']));
				$source['name'] = $this->language->lang('ACP_STATCHARTS_COPY_OF', $name);
				$this->palettes->save_custom(null, $source);
				return 'ACP_STATCHARTS_PALETTE_CREATED';

			case 'custom_save':
				$id = $this->request->variable('palette_id', '');
				$values = ['name' => $this->request->variable('name', '', true), 'slots' => $this->request->variable('slots', [''])];
				foreach ($this->palettes::TOKENS as $token)
				{
					$values[$token] = $this->request->variable($token, '');
				}
				$this->palettes->save_custom($id, $values);
				return 'ACP_STATCHARTS_SAVED';

			case 'data':
				$excluded = array_values(array_unique(array_filter($this->request->variable('excluded', [0]))));
				$this->config->set('statcharts_excluded_forums', implode(',', $excluded));
				$this->config->set('statcharts_count_anonymous', $this->request->variable('count_anonymous', 0) ? 1 : 0);
				$this->colours->sync(false);
				return 'ACP_STATCHARTS_SAVED_REBUILD';
		}
		return 'ACP_STATCHARTS_SAVED';
	}

	/**
	 * Deletes a custom palette once the admin confirms. "No" falls through to
	 * the settings page.
	 */
	private function delete_palette()
	{
		$id = $this->request->variable('palette_id', '');
		$all = $this->palettes->all();
		if (!isset($all['custom:' . $id]))
		{
			trigger_error($this->language->lang('FORM_INVALID') . adm_back_link($this->u_action), E_USER_WARNING);
		}

		if (confirm_box(true))
		{
			$this->palettes->delete_custom($id);
			trigger_error($this->language->lang('ACP_STATCHARTS_PALETTE_DELETED') . adm_back_link($this->u_action));
		}

		confirm_box(false, $this->language->lang('ACP_STATCHARTS_PALETTE_DELETE_CONFIRM', $all['custom:' . $id]['name']),
			build_hidden_fields(['action' => 'custom_delete', 'palette_id' => $id]));
		redirect($this->u_action);
	}

	/**
	 * One slice of the rebuild for the "Build now" progress bar: the same
	 * time-boxed unit the web cron runs, polled until done (SPEC section 9).
	 */
	private function rebuild_step()
	{
		$json = new \phpbb\json_response();
		if (!check_link_hash($this->request->variable('hash', ''), 'statcharts_rebuild'))
		{
			$json->send(['error' => $this->language->lang('FORM_INVALID')]);
		}

		/** @var \biochaos\statcharts\service\rebuild $rebuild */
		$rebuild = $this->container->get('biochaos.statcharts.rebuild');
		$limit = (int) ini_get('max_execution_time');
		$result = $rebuild->run($limit > 0 ? max(1, min(5, $limit / 3)) : 5);
		$status = $rebuild->status();
		$json->send([
			'result'   => $result,
			'done'     => $result === \biochaos\statcharts\service\rebuild::RESULT_DONE,
			'progress' => $this->progress($status),
			'label'    => $this->language->lang('ACP_STATCHARTS_STEP', $status['step'] + 1, $status['steps'],
				$this->language->lang('ACP_STATCHARTS_STEP_' . strtoupper($status['step_name']))),
		]);
	}

	/** Rough completion, 0-100: steps count equally, the chunked ones by post id. */
	private function progress(array $status)
	{
		if ($status['state'] !== 'building')
		{
			return 100;
		}
		return (int) round(100 * ($status['step'] + $status['fraction']) / $status['steps']);
	}

	/* ------------------------------------------------------------------ */
	/* The page                                                            */
	/* ------------------------------------------------------------------ */

	private function assign_page()
	{
		/** @var \biochaos\statcharts\service\reader $reader */
		$reader = $this->container->get('biochaos.statcharts.reader');
		/** @var \biochaos\statcharts\service\rebuild $rebuild */
		$rebuild = $this->container->get('biochaos.statcharts.rebuild');

		$built = $reader->build_params();
		$status = $rebuild->status();
		$now = time();
		$interval = max(60, (int) $this->config['statcharts_rebuild_interval']);

		// What the stats include, now and at the last build: a difference needs a rebuild.
		$forums = $this->forums->all();
		list(, $excluded_now) = $this->forums->sets($forums);
		$timezone = (string) $this->config['board_timezone'];
		$dirty = [];
		if ($built)
		{
			if ($built['excluded'] !== $excluded_now)
			{
				$dirty[] = $this->language->lang('ACP_STATCHARTS_DIRTY_EXCLUDED');
			}
			if ($built['count_anonymous'] !== (bool) $this->config['statcharts_count_anonymous'])
			{
				$dirty[] = $this->language->lang('ACP_STATCHARTS_DIRTY_ANONYMOUS');
			}
			if ($built['timezone'] !== $timezone)
			{
				$dirty[] = $this->language->lang('ACP_STATCHARTS_DIRTY_TIMEZONE', $built['timezone'], $timezone);
			}
		}

		$user = $this->container->get('user');
		$this->template->assign_vars([
			'U_ACTION'            => $this->u_action,
			'U_REBUILD'           => $this->u_action . '&amp;action=rebuild&amp;hash=' . generate_link_hash('statcharts_rebuild'),
			'S_CONFIGURED'        => (bool) $this->config['statcharts_configured'],
			'S_BUILT'             => (bool) $built,
			'S_BUILDING'          => $status['state'] === 'building',
			'BUILDING_PROGRESS'   => $this->progress($status),
			'LAST_BUILD'          => $built ? $user->format_date($built['built_at']) : '',
			'NEXT_BUILD'          => $built ? $user->format_date($status['last'] + $interval) : '',
			'S_OVERDUE'           => $built && $now - $status['last'] > 2 * $interval,
			'DIRTY'               => implode(' ', $dirty),
			'GROUPING'            => $this->colours->mode(),
			'START_DATE'          => (string) $this->config['statcharts_start_date'],
			'S_COUNT_ANONYMOUS'   => (bool) $this->config['statcharts_count_anonymous'],
			'EXCLUDED_OPTIONS'    => $this->forum_options($this->forums->configured_exclusions()),
			'S_ORDER_CUSTOM'      => (bool) $this->colours->order(),
			'STATCHARTS_ACP_JSON' => $this->page_json($forums),
		]);

		foreach (grouping::MODES as $mode)
		{
			$this->template->assign_block_vars('modes', [
				'VALUE'    => $mode,
				'LABEL'    => $this->language->lang('ACP_STATCHARTS_MODE_' . strtoupper($mode)),
				'EXPLAIN'  => $this->language->lang('ACP_STATCHARTS_MODE_' . strtoupper($mode) . '_EXPLAIN'),
				'SELECTED' => $mode === $this->colours->mode(),
			]);
		}
	}

	/**
	 * Everything the page script needs to draw and check the colours: entities
	 * of the current mode in display order with their slots, entity counts per
	 * mode, palettes, style assignments.
	 */
	private function page_json(array $forums)
	{
		$map = $this->colours->map();
		$notices = array_flip($this->colours->notices());
		$entities = [];
		foreach ($this->colours->entities() as $entity)
		{
			$entities[] = [
				'id'        => $entity['id'],
				'name'      => $entity['name'],
				'type'      => $entity['type'],
				'slot'      => isset($map[$entity['id']]) ? $map[$entity['id']] : null,
				'new'       => isset($notices[$entity['id']]),
				'unordered' => $entity['unordered'],
			];
		}

		$styles = [];
		$db = $this->container->get('dbal.conn');
		$result = $db->sql_query('SELECT style_name, style_path, style_active FROM ' . STYLES_TABLE . ' ORDER BY style_name');
		while ($row = $db->sql_fetchrow($result))
		{
			$styles[] = ['name' => $row['style_name'], 'path' => $row['style_path'], 'active' => (bool) $row['style_active']];
		}
		$db->sql_freeresult($result);

		return json_encode([
			'entities'    => $entities,
			'counts'      => $this->colours->counts(),
			'mode'        => $this->colours->mode(),
			'disabled'    => $this->colours->disabled(),
			'palettes'    => $this->palettes->all(),
			'assignments' => $this->palettes->assignments(),
			'styles'      => $styles,
			'lang'        => $this->js_strings(),
		], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE);
	}

	private function js_strings()
	{
		$keys = ['SLOT', 'OTHER', 'NEW_FORUM', 'NEW_IN_ORDER', 'MOVE_UP', 'MOVE_DOWN', 'TOO_MANY', 'TOO_MANY_ALT',
			'CHECK_BAND', 'CHECK_CHROMA', 'CHECK_CVD', 'CHECK_CVD_FLOOR', 'CHECK_NORMAL', 'CHECK_CONTRAST',
			'NEIGHBOURS', 'NEIGHBOURS_OK', 'PALETTE_OK', 'PALETTE_FOR', 'WRAP', 'DEFAULT', 'BUILDING', 'BUILT',
			'LOCKED', 'BUILD_FAILED', 'MODE_SEPARATE', 'MODE_TOP_LEVEL', 'MODE_CATEGORY'];
		$out = [];
		foreach ($keys as $key)
		{
			$out[$key] = $this->language->lang('ACP_STATCHARTS_JS_' . $key);
		}
		// Page strings the script's own markup reuses.
		$page = ['PRESET_LIGHT', 'PRESET_DARK', 'PALETTE_DEFAULT', 'PALETTE_INACTIVE', 'PALETTE_NAME', 'DELETE_PALETTE',
			'DISABLED_SLOTS', 'DISABLED_SLOTS_EXPLAIN'];
		foreach ($this->palettes::TOKENS as $token)
		{
			$page[] = 'TOKEN_' . strtoupper($token);
		}
		foreach ($page as $key)
		{
			$out[$key] = $this->language->lang('ACP_STATCHARTS_' . $key);
		}
		$out['SUBMIT'] = $this->language->lang('SUBMIT');
		return $out;
	}

	/** <option>s of every forum, the given ones selected; categories and sub-forums indented. */
	private function forum_options(array $selected)
	{
		return make_forum_select($selected, false, true, false, false);
	}
}
