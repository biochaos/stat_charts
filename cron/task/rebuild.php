<?php
/**
 * Stat Charts extension for phpBB.
 *
 * @copyright (c) 2026, biochaos
 * @license GNU General Public License, version 2 (GPL-2.0)
 */

namespace biochaos\statcharts\cron\task;

/**
 * Web-triggered rebuild: works with no setup. Each run does one time-boxed
 * slice; while a build is in progress the task stays due, so the next cron
 * trigger continues it. Admins with shell access can schedule
 * "phpbbcli.php statcharts:rebuild" instead (SPEC section 5).
 */
class rebuild extends \phpbb\cron\task\base
{
	/** @var \phpbb\config\config */
	private $config;

	/** @var \biochaos\statcharts\service\rebuild */
	private $rebuild;

	public function __construct(
		\phpbb\config\config $config,
		\biochaos\statcharts\service\rebuild $rebuild
	)
	{
		$this->config  = $config;
		$this->rebuild = $rebuild;
	}

	public function get_name()
	{
		return 'biochaos.statcharts.cron.rebuild';
	}

	public function run()
	{
		// A third of the request's limit, leaving room for the rest of it.
		$limit = (int) ini_get('max_execution_time');
		$this->rebuild->run($limit > 0 ? max(1, $limit / 3) : 10);
	}

	public function should_run()
	{
		if ($this->rebuild->is_building())
		{
			return true;
		}

		$interval = max(60, (int) $this->config['statcharts_rebuild_interval']);
		return time() - (int) $this->config['statcharts_rebuild_last'] >= $interval;
	}
}
