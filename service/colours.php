<?php
/**
 * Stat Charts extension for phpBB.
 *
 * @copyright (c) 2026, biochaos
 * @license GNU General Public License, version 2 (GPL-2.0)
 */

namespace biochaos\statcharts\service;

use phpbb\config\config;
use phpbb\config\db_text;

/**
 * Entity order and pinned colours (SPEC section 7).
 *
 * Colour is pinned per forum, never per rank: a forum_id -> slot map, one map
 * for every grouping mode, since categories are forums too. A forum without a
 * valid slot takes the first free one in display order, persisted at once, so
 * it is stable from then on; one past the eighth colour has none and is drawn
 * in the grey "Other". A deleted forum's entry is pruned (phpBB reuses ids).
 * Nothing here repaints a forum unless it must (its slot was disabled, or two
 * entities of the current mode claim one slot) or the admin asks.
 */
class colours
{
	const SLOTS = 8;
	const OTHER = 'other';

	/** @var config */
	private $config;

	/** @var db_text */
	private $config_text;

	/** @var forums */
	private $forums;

	/** @var grouping */
	private $grouping;

	public function __construct(config $config, db_text $config_text, forums $forums, grouping $grouping)
	{
		$this->config      = $config;
		$this->config_text = $config_text;
		$this->forums      = $forums;
		$this->grouping    = $grouping;
	}

	/** @return array [forum_id => slot index | 'other'] */
	public function map()
	{
		$map = [];
		foreach ($this->json('statcharts_colour_slots') as $forum_id => $slot)
		{
			$map[(int) $forum_id] = $slot === self::OTHER ? self::OTHER : (int) $slot;
		}
		return $map;
	}

	/** @return int[] */
	public function disabled()
	{
		return array_values(array_unique(array_map('intval', $this->json('statcharts_disabled_slots'))));
	}

	/** @return int[] The custom entity order; empty = the board's own (left_id) */
	public function order()
	{
		return array_values(array_map('intval', $this->json('statcharts_entity_order')));
	}

	/** @return int[] Forums given a colour by themselves since the admin last looked (flagged in the ACP) */
	public function notices()
	{
		return array_values(array_map('intval', $this->json('statcharts_colour_notices')));
	}

	public function clear_notices()
	{
		$this->config_text->set('statcharts_colour_notices', '');
	}

	public function mode()
	{
		$mode = (string) $this->config['statcharts_grouping'];
		return in_array($mode, grouping::MODES, true) ? $mode : grouping::TOP_LEVEL;
	}

	/**
	 * The entities of a grouping mode, from the live forums the stats may show.
	 *
	 * @return array As grouping::resolve()['entities'], in display order
	 */
	public function entities($mode = null)
	{
		$all = $this->forums->all();
		list($allowed) = $this->forums->sets($all);
		$rows = array_intersect_key($all, array_flip($allowed));
		return $this->grouping->resolve(array_values($rows), $mode === null ? $this->mode() : $mode, $this->order())['entities'];
	}

	/** @return array [mode => number of entities], for the "more than 8" warning */
	public function counts()
	{
		$counts = [];
		foreach (grouping::MODES as $mode)
		{
			$counts[$mode] = count($this->entities($mode));
		}
		return $counts;
	}

	/**
	 * Prune entries of deleted forums, then make sure every entity of the
	 * current mode has a slot of its own, or 'other' if the admin chose it:
	 * a valid pinned slot is kept; a missing, disabled or already-taken one is
	 * replaced by the first free slot in display order and persisted. Called
	 * after every rebuild and whenever the ACP changes grouping, order or
	 * colours.
	 *
	 * @param bool $notify Flag forums given a slot for the ACP ("new"): on
	 *                     after a rebuild or on sight, off when the admin's
	 *                     own action is what moved them
	 * @return int[] Forums given a new slot by this call
	 */
	public function sync($notify = true)
	{
		$live = $this->forums->all();
		// The very first assignment gives every forum a colour; that is not news.
		$first = !$this->map();
		$map = array_intersect_key($this->map(), $live);
		$order = array_values(array_filter($this->order(), function ($id) use ($live) {
			return isset($live[$id]);
		}));
		if ($order !== $this->order())
		{
			$this->save('statcharts_entity_order', $order);
		}

		$disabled = array_flip($this->disabled());
		$used = [];
		$given = [];
		foreach ($this->entities() as $entity)
		{
			$id = $entity['id'];
			$slot = isset($map[$id]) ? $map[$id] : null;
			if ($slot === self::OTHER)
			{
				continue;
			}
			if ($slot !== null && $slot < self::SLOTS && !isset($disabled[$slot]) && !isset($used[$slot]))
			{
				$used[$slot] = true;
				continue;
			}
			$free = $this->first_free($used, $disabled);
			if ($free === null)
			{
				// No colour left: drawn in the grey "Other" until the admin chooses.
				unset($map[$id]);
				continue;
			}
			$map[$id] = $free;
			$used[$free] = true;
			$given[] = $id;
		}

		$this->save('statcharts_colour_slots', $map);
		if ($given && $notify && !$first)
		{
			$this->save('statcharts_colour_notices', array_values(array_unique(array_merge($this->notices(), $given))));
		}
		return $given;
	}

	/**
	 * The admin's colour choices for the current mode's entities, as submitted:
	 * [forum_id => slot | 'other']. Two entities cannot share a slot: the later
	 * one in display order keeps what it had, or takes the first free one.
	 */
	public function set_slots(array $choices)
	{
		$map = $this->map();
		foreach ($choices as $forum_id => $slot)
		{
			$map[(int) $forum_id] = $slot === self::OTHER ? self::OTHER : max(0, min(self::SLOTS - 1, (int) $slot));
		}
		$seen = [];
		foreach ($this->entities() as $entity)
		{
			$slot = isset($map[$entity['id']]) ? $map[$entity['id']] : null;
			if (is_int($slot))
			{
				if (isset($seen[$slot]))
				{
					unset($map[$entity['id']]);
				}
				$seen[$slot] = true;
			}
		}
		$this->save('statcharts_colour_slots', $map);
		$this->sync(false);
	}

	/** Only the entities that held a newly disabled slot move (SPEC section 7). */
	public function set_disabled(array $slots)
	{
		$slots = array_values(array_unique(array_filter(array_map('intval', $slots), function ($slot) {
			return $slot >= 0 && $slot < self::SLOTS;
		})));
		sort($slots);
		$this->save('statcharts_disabled_slots', $slots);
		$this->sync(false);
	}

	/** @param int[] $ids The entities of the current mode, in the order wanted */
	public function set_order(array $ids)
	{
		$this->save('statcharts_entity_order', array_values(array_unique(array_map('intval', $ids))));
		$this->sync(false);
	}

	/** "Reset to forum order": the board's own order again, and new forums take their natural place. */
	public function reset_order()
	{
		$this->save('statcharts_entity_order', []);
		$this->sync(false);
	}

	/**
	 * "Re-assign colours in this order": a repaint the admin asked for. Slot 1
	 * to the first entity, and so on, so drawn neighbours are the validated
	 * neighbours again.
	 */
	public function reassign_in_order()
	{
		$map = $this->map();
		foreach ($this->entities() as $entity)
		{
			if (isset($map[$entity['id']]) && $map[$entity['id']] !== self::OTHER)
			{
				unset($map[$entity['id']]);
			}
		}
		$this->save('statcharts_colour_slots', $map);
		$this->sync(false);
	}

	private function first_free(array $used, array $disabled)
	{
		for ($slot = 0; $slot < self::SLOTS; $slot++)
		{
			if (!isset($used[$slot]) && !isset($disabled[$slot]))
			{
				return $slot;
			}
		}
		return null;
	}

	private function json($name)
	{
		$value = json_decode((string) $this->config_text->get($name), true);
		return is_array($value) ? $value : [];
	}

	/** The slot map is always an object (forum id keys), the rest are lists. */
	private function save($name, array $value)
	{
		$flags = $name === 'statcharts_colour_slots' ? JSON_FORCE_OBJECT : 0;
		$this->config_text->set($name, $value ? json_encode($value, $flags) : '');
	}
}
