<?php
/**
 * Stat Charts extension for phpBB.
 *
 * @copyright (c) 2026, biochaos
 * @license GNU General Public License, version 2 (GPL-2.0)
 */

namespace biochaos\statcharts\controller;

use biochaos\statcharts\service\palettes;
use biochaos\statcharts\service\reader;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * app.php/stats (the page) and app.php/stats/data/* (its JSON). Both require
 * u_statcharts_view (SPEC section 10).
 */
class main
{
	/** Language keys the page script needs, each prefixed STATCHARTS_JS_. */
	const JS_STRINGS = [
		'TAB_GENERAL', 'TAB_FORUM', 'TAB_TOPIC', 'TAB_USER',
		'AS_OF', 'NOT_BUILT', 'LOADING', 'ERROR', 'NO_ACTIVITY', 'NO_ROWS',
		'RANGE_ALL', 'RANGE_YEAR', 'RANGE_MONTH', 'RANGE_WEEK', 'RANGE_TODAY', 'RANGE_CUSTOM', 'RANGE_FROM', 'RANGE_TO',
		'TODAY_CAPTION', 'MODE_AVG', 'MODE_DAY', 'MODE_CUM', 'SHOW_TABLE', 'SHOW_CHART', 'TOTAL',
		'POSTS_OVER_TIME', 'POSTS_BY_FORUM', 'TOPICS_BY_FORUM', 'LIKES_BY_FORUM', 'LIKES_EXPLAIN',
		'VIEWS_BY_FORUM', 'VIEWS_BY_FORUM_RANGE', 'VIEWS_NOTE',
		'USERS', 'USERS_REGISTERED', 'USERS_POSTED', 'ACTIVE_7D', 'ACTIVE_7D_EXPLAIN',
		'MOST_VIEWED', 'MOST_VIEWED_RANGE', 'MOST_ACTIVE', 'MOST_LIKED',
		'COL_TOPIC', 'COL_TOPICS', 'COL_FORUM', 'COL_USER', 'COL_POST', 'COL_VIEWS', 'COL_POSTS', 'COL_LIKES', 'COL_SHARE', 'COL_MONTH',
		'FORUM_SELECT', 'FORUM_NOT_IN_STATS', 'FORUM_HEADER',
		'USER_SEARCH', 'USER_PICK', 'USER_NOT_FOUND', 'USER_HEADER', 'USER_TOP_TOPICS', 'NO_PROFILES',
		'TOPIC_SEARCH', 'TOPIC_ALL_FORUMS', 'SORT_TITLE', 'SORT_REPLIES', 'TOPIC_NOT_FOUND', 'TOPIC_HEADER',
		'TOPIC_ACTIVITY', 'TOPIC_ACTIVITY_ZOOM', 'TOPIC_TOP_USERS', 'FORUM_TOP_USERS', 'FORUM_TOP_LIKED',
		'GO', 'OTHER', 'HIDDEN', 'OPEN_ON_FORUM', 'SESSION_EXPIRED', 'RELOAD',
		'PAGE_RANGE', 'PAGE_RANGE_HINT', 'RANGE_OVERRIDDEN',
	];

	/** @var \phpbb\auth\auth */
	private $auth;

	/** @var \phpbb\user */
	private $user;

	/** @var \phpbb\request\request_interface */
	private $request;

	/** @var \phpbb\controller\helper */
	private $helper;

	/** @var \phpbb\language\language */
	private $language;

	/** @var \phpbb\template\template */
	private $template;

	/** @var reader */
	private $reader;

	/** @var palettes */
	private $palettes;

	public function __construct(
		\phpbb\auth\auth $auth,
		\phpbb\user $user,
		\phpbb\request\request_interface $request,
		\phpbb\controller\helper $helper,
		\phpbb\language\language $language,
		\phpbb\template\template $template,
		reader $reader,
		palettes $palettes
	)
	{
		$this->auth     = $auth;
		$this->user     = $user;
		$this->request  = $request;
		$this->helper   = $helper;
		$this->language = $language;
		$this->template = $template;
		$this->reader   = $reader;
		$this->palettes = $palettes;
	}

	public function page()
	{
		$this->language->add_lang('common', 'biochaos/statcharts');

		if (!$this->auth->acl_get('u_statcharts_view'))
		{
			if ($this->user->data['user_id'] == ANONYMOUS)
			{
				login_box('', $this->language->lang('STATCHARTS_LOGIN'));
			}
			throw new \phpbb\exception\http_exception(403, 'NOT_AUTHORISED');
		}

		$strings = [];
		foreach (self::JS_STRINGS as $key)
		{
			$strings[$key] = $this->language->lang('STATCHARTS_JS_' . $key);
		}

		$urls = [];
		foreach (['overview', 'top', 'user', 'topic', 'topics', 'users'] as $what)
		{
			$urls[$what] = $this->helper->route('biochaos_statcharts_data', ['what' => $what]);
		}

		$config = [
			'urls'         => $urls,
			// With the session id when the board needs it in URLs (no cookies).
			'viewtopic'    => append_sid(generate_board_url() . '/viewtopic.php', false, false),
			'locale'       => $this->user->lang_name,
			'dir'          => $this->language->lang('DIRECTION'),
			'guest'        => $this->user->data['user_id'] == ANONYMOUS,
			'can_profiles' => (bool) $this->auth->acl_get('u_viewprofile'),
			'palette'      => $this->palettes->for_style($this->user->style['style_path']),
			'slots'        => $this->palettes->pinned_slots(),
			'disabled'     => $this->palettes->disabled_slots(),
			'lang'         => $strings,
		];

		// Safe inside a <script> element: the HEX_ flags escape < > & ' ".
		$this->template->assign_var('STATCHARTS_CONFIG', json_encode($config,
			JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

		return $this->helper->render('@biochaos_statcharts/statcharts_body.html', $this->language->lang('STATCHARTS_TITLE'));
	}

	/**
	 * @param string $what overview | top | user | topic | topics | users
	 */
	public function data($what)
	{
		$this->language->add_lang('common', 'biochaos/statcharts');

		if (!$this->auth->acl_get('u_statcharts_view'))
		{
			$status = ($this->user->data['user_id'] == ANONYMOUS) ? 401 : 403;
			return $this->json(['error' => 'not_authorised'], $status);
		}

		if ($this->reader->meta() === null)
		{
			return $this->json(['built' => false]);
		}

		$this->reader->set_viewer(
			array_keys($this->auth->acl_getf('f_list', true)),
			array_keys($this->auth->acl_getf('f_read', true))
		);

		// Member stats follow phpBB's profile permission, as the member list does.
		if (($what === 'user' || $what === 'users') && !$this->auth->acl_get('u_viewprofile'))
		{
			return $this->json(['error' => 'no_profiles'], 403);
		}

		switch ($what)
		{
			case 'overview':
				$data = $this->reader->overview();
			break;

			case 'top':
				$data = $this->reader->top(
					$this->request->variable('widget', ''),
					$this->request->variable('scope', 'all'),
					$this->request->variable('id', 0),
					$this->day_param('from'),
					$this->day_param('to'),
					$this->request->variable('limit', 0)
				);
			break;

			case 'user':
				$viewer = (int) $this->user->data['user_id'];
				$user_id = $this->request->variable('id', 0);

				if (!$user_id && $viewer == ANONYMOUS)
				{
					// A guest has no stats of their own: the tab shows the search.
					$data = ['user' => null];
					break;
				}

				$user_id = $user_id ?: $viewer;
				$data = $this->reader->user($user_id);

				// The viewer's own tab (the default) before their first post has
				// been through a rebuild: empty, not an error.
				if ($data === null && $user_id === $viewer)
				{
					$data = $this->reader->empty_user($viewer, $this->user->data['username'], $this->user->data['user_colour']);
				}
			break;

			case 'topic':
				$data = $this->reader->topic($this->request->variable('id', 0));
			break;

			case 'topics':
				$data = $this->reader->topics();
			break;

			case 'users':
				$data = $this->reader->users();
			break;

			default:
				$data = null;
		}

		return $data === null ? $this->json(['error' => 'not_found'], 404) : $this->json($data);
	}

	/** @return int|null A day number, or null when absent */
	private function day_param($name)
	{
		return $this->request->is_set($name) ? $this->request->variable($name, 0) : null;
	}

	/**
	 * Unescaped Unicode: \uXXXX would triple the size of Hebrew text. The
	 * default HEX_* flags stay, so the JSON is also safe inside HTML. Stats
	 * depend on who asks (the permission), so no shared caches.
	 */
	private function json($data, $status = 200)
	{
		$response = new JsonResponse();
		$response->setEncodingOptions(JsonResponse::DEFAULT_ENCODING_OPTIONS | JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
		$response->setData($data);
		$response->setStatusCode($status);
		$response->setPrivate();
		$response->headers->addCacheControlDirective('no-store');
		return $response;
	}
}
