<?php
/**
 * Stat Charts extension for phpBB.
 *
 * @copyright (c) 2026, biochaos
 * @license GNU General Public License, version 2 (GPL-2.0)
 */

namespace biochaos\statcharts\service;

/**
 * Read-time grouping (SPEC section 6): maps every forum to the entity it is
 * counted under. The rollups stay at forum grain; switching mode only changes
 * this map. Invariant: every postable forum maps to exactly one entity, so
 * pies always sum to 100%.
 */
class grouping
{
	const SEPARATE  = 'separate';
	const TOP_LEVEL = 'top_level';
	const CATEGORY  = 'category';

	const MODES = [self::SEPARATE, self::TOP_LEVEL, self::CATEGORY];

	/**
	 * @param array  $forums Rows of dim_forum: forum_id, parent_id, forum_type, left_id, forum_name
	 * @param string $mode   One of MODES; anything else falls back to TOP_LEVEL
	 * @param int[]  $order  The admin's custom entity order (forum ids); empty = the board's own
	 * @return array [
	 *     'entities'     => list of ['id', 'name', 'type' (forum|category), 'forums' => [ids], 'unordered' => bool],
	 *                       in display order,
	 *     'forum_entity' => [forum_id => entity id] for every forum that belongs to one,
	 * ]
	 */
	public function resolve(array $forums, $mode, array $order = [])
	{
		if (!in_array($mode, self::MODES, true))
		{
			$mode = self::TOP_LEVEL;
		}

		$by_id = [];
		foreach ($forums as $forum)
		{
			$by_id[(int) $forum['forum_id']] = $forum;
		}

		// Display order is the board's own: left_id (SPEC section 7, Entity order).
		uasort($by_id, function ($a, $b) {
			return (int) $a['left_id'] - (int) $b['left_id'];
		});

		$forum_entity = [];
		foreach ($by_id as $id => $forum)
		{
			$entity = $this->entity_of($id, $by_id, $mode);
			if ($entity !== null)
			{
				$forum_entity[$id] = $entity;
			}
		}

		$members = [];
		foreach ($forum_entity as $forum_id => $entity_id)
		{
			$members[$entity_id][] = $forum_id;
		}

		$entities = [];
		foreach ($by_id as $id => $forum)
		{
			if (isset($members[$id]))
			{
				$entities[] = [
					'id'        => $id,
					'name'      => html_entity_decode($forum['forum_name'], ENT_QUOTES, 'UTF-8'),
					'type'      => (int) $forum['forum_type'] === FORUM_CAT ? 'category' : 'forum',
					'forums'    => $members[$id],
					'unordered' => false,
				];
			}
		}

		return ['entities' => $this->apply_order($entities, $order), 'forum_entity' => $forum_entity];
	}

	/**
	 * A custom order, once saved, puts its entities first in its order; any
	 * entity it does not list (created since) follows in the board's order,
	 * flagged so the ACP can point it out (SPEC section 7, Entity order).
	 */
	private function apply_order(array $entities, array $order)
	{
		if (!$order)
		{
			return $entities;
		}
		$rank = array_flip(array_map('intval', $order));
		$listed = $unlisted = [];
		foreach ($entities as $entity)
		{
			if (isset($rank[$entity['id']]))
			{
				$listed[] = $entity;
			}
			else
			{
				$entity['unordered'] = true;
				$unlisted[] = $entity;
			}
		}
		usort($listed, function ($a, $b) use ($rank) {
			return $rank[$a['id']] - $rank[$b['id']];
		});
		return array_merge($listed, $unlisted);
	}

	/**
	 * @return int|null The entity a forum is counted under; null for a
	 *                  category that is not itself an entity (it has no posts)
	 */
	private function entity_of($id, array $by_id, $mode)
	{
		$postable = (int) $by_id[$id]['forum_type'] === FORUM_POST;

		if ($mode === self::CATEGORY)
		{
			$category = $this->topmost_category($id, $by_id);
			if ($category !== null)
			{
				return $category;
			}
		}

		if (!$postable)
		{
			return null;
		}

		if ($mode === self::SEPARATE)
		{
			return $id;
		}

		// Fold into the parent for as long as the parent is postable: never into a
		// category, and a chain of sub-forums folds all the way up.
		while (true)
		{
			$parent = (int) $by_id[$id]['parent_id'];
			if (!isset($by_id[$parent]) || (int) $by_id[$parent]['forum_type'] !== FORUM_POST)
			{
				return $id;
			}
			$id = $parent;
		}
	}

	/**
	 * The root-most category at or above a forum, or null when it sits under
	 * none. A forum outside every category is not dropped in category mode:
	 * that would break the partition invariant, so it stands as in top-level
	 * mode. (Hence one category mode, not the two the spec first had.)
	 */
	private function topmost_category($id, array $by_id)
	{
		$found = null;
		while (isset($by_id[$id]))
		{
			if ((int) $by_id[$id]['forum_type'] === FORUM_CAT)
			{
				$found = $id;
			}
			$id = (int) $by_id[$id]['parent_id'];
		}
		return $found;
	}
}
