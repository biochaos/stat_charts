<?php
/**
 * Stat Charts extension for phpBB.
 *
 * @copyright (c) 2026, biochaos
 * @license GNU General Public License, version 2 (GPL-2.0)
 */

namespace biochaos\statcharts\event;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Links to the statistics from the board (SPEC section 11): the quick-links
 * menu, a member's profile, and below the Information panel of forum and
 * topic pages. Template events only, so any style with phpBB's standard events
 * gets them, and no style or core file needs editing.
 *
 * Links show only to viewers who may see the statistics. A forum or topic link
 * shows only where the forum is in the statistics as last built: not in an
 * excluded forum, a category, or anything the stats have not seen yet.
 */
class listener implements EventSubscriberInterface
{
	/** @var \phpbb\auth\auth */
	private $auth;

	/** @var \phpbb\config\config */
	private $config;

	/** @var \phpbb\cache\driver\driver_interface */
	private $cache;

	/** @var \phpbb\db\driver\driver_interface */
	private $db;

	/** @var \phpbb\controller\helper */
	private $helper;

	/** @var \phpbb\template\template */
	private $template;

	/** @var \biochaos\statcharts\service\tables */
	private $tables;

	public function __construct(
		\phpbb\auth\auth $auth,
		\phpbb\config\config $config,
		\phpbb\cache\driver\driver_interface $cache,
		\phpbb\db\driver\driver_interface $db,
		\phpbb\controller\helper $helper,
		\phpbb\template\template $template,
		\biochaos\statcharts\service\tables $tables
	)
	{
		$this->auth     = $auth;
		$this->config   = $config;
		$this->cache    = $cache;
		$this->db       = $db;
		$this->helper   = $helper;
		$this->template = $template;
		$this->tables   = $tables;
	}

	public static function getSubscribedEvents()
	{
		return [
			'core.user_setup'                     => 'load_language',
			'core.page_header'                    => 'link_page',
			'core.viewforum_modify_page_title'    => 'link_forum',
			'core.viewtopic_modify_page_title'    => 'link_topic',
			'core.memberlist_view_profile'        => 'link_member',
		];
	}

	public function load_language($event)
	{
		$lang_set_ext = $event['lang_set_ext'];
		$lang_set_ext[] = ['ext_name' => 'biochaos/statcharts', 'lang_set' => 'links'];
		$event['lang_set_ext'] = $lang_set_ext;
	}

	public function link_page()
	{
		if ($this->can_view())
		{
			$this->template->assign_var('U_STATCHARTS', $this->helper->route('biochaos_statcharts_page'));
		}
	}

	public function link_forum($event)
	{
		$forum_id = (int) $event['forum_id'];
		$forums = $this->included_forums();
		// A category has no forum tab of its own: its forums may be separate groups.
		if ($this->can_view() && isset($forums[$forum_id]) && $forums[$forum_id] == FORUM_POST)
		{
			$this->template->assign_var('U_STATCHARTS_FORUM', $this->route('forum', $forum_id));
		}
	}

	public function link_topic($event)
	{
		$forums = $this->included_forums();
		if ($this->can_view() && isset($forums[(int) $event['forum_id']]))
		{
			$this->template->assign_var('U_STATCHARTS_TOPIC', $this->route('topic', (int) $event['topic_data']['topic_id']));
		}
	}

	public function link_member($event)
	{
		// Bots are not in the statistics; the user tab needs profile access too.
		if ($this->can_view() && $this->auth->acl_get('u_viewprofile') && $event['member']['user_type'] != USER_IGNORE)
		{
			$this->template->assign_var('U_STATCHARTS_MEMBER', $this->route('user', (int) $event['member']['user_id']));
		}
	}

	private function can_view()
	{
		return (bool) $this->auth->acl_get('u_statcharts_view');
	}

	private function route($tab, $id)
	{
		return $this->helper->route('biochaos_statcharts_page', ['tab' => $tab, 'id' => $id]);
	}

	/**
	 * [forum_id => forum_type] of every forum in the statistics as last built,
	 * read once per build: forum and topic pages are the board's busiest.
	 */
	private function included_forums()
	{
		$built = (int) $this->config['statcharts_rebuild_last'];
		if (!$built)
		{
			return [];
		}

		$key = '_statcharts_link_forums_' . $built;
		$forums = $this->cache->get($key);
		if ($forums === false)
		{
			$forums = [];
			$result = $this->db->sql_query('SELECT forum_id, forum_type FROM ' . $this->tables->live('dim_forum'));
			while ($row = $this->db->sql_fetchrow($result))
			{
				$forums[(int) $row['forum_id']] = (int) $row['forum_type'];
			}
			$this->db->sql_freeresult($result);
			$this->cache->put($key, $forums, 86400);
		}
		return $forums;
	}
}
