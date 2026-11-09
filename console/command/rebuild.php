<?php
/**
 * Stat Charts extension for phpBB.
 *
 * @copyright (c) 2026, biochaos
 * @license GNU General Public License, version 2 (GPL-2.0)
 */

namespace biochaos\statcharts\console\command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Runs the rebuild to completion in one pass, with no time budget. This is
 * what a systemd timer or crontab entry points at.
 */
class rebuild extends \phpbb\console\command\command
{
	/** @var \biochaos\statcharts\service\rebuild */
	private $rebuild;

	/** @var \phpbb\language\language */
	private $language;

	public function __construct(\phpbb\user $user, \phpbb\language\language $language, \biochaos\statcharts\service\rebuild $rebuild)
	{
		$this->rebuild = $rebuild;
		$this->language = $language;
		$language->add_lang('cli', 'biochaos/statcharts');
		parent::__construct($user);
	}

	protected function configure()
	{
		$this
			->setName('statcharts:rebuild')
			->setDescription($this->language->lang('STATCHARTS_CLI_REBUILD'))
			->addOption('restart', null, InputOption::VALUE_NONE, $this->language->lang('STATCHARTS_CLI_REBUILD_RESTART'))
		;
	}

	protected function execute(InputInterface $input, OutputInterface $output)
	{
		$io = new SymfonyStyle($input, $output);
		$start = microtime(true);
		$last_step = null;

		$progress = function ($step, $name, $done, $total, $unit) use ($output, &$last_step) {
			if ($output->isVerbose() || $step !== $last_step)
			{
				$output->writeln($this->language->lang($unit === 'day' ? 'STATCHARTS_CLI_PROGRESS_DAYS' : 'STATCHARTS_CLI_PROGRESS',
					$step, $name, $done, $total));
				$last_step = $step;
			}
		};

		$result = $this->rebuild->run(0, $progress, (bool) $input->getOption('restart'));

		if ($result === \biochaos\statcharts\service\rebuild::RESULT_LOCKED)
		{
			$io->error($this->language->lang('STATCHARTS_CLI_LOCKED'));
			return 1;
		}

		$io->success($this->language->lang('STATCHARTS_CLI_DONE', round(microtime(true) - $start, 1)));
		return 0;
	}
}
