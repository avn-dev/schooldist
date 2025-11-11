<?php

namespace Communication\Commands;

use AdminTools\Dto\Log;
use AdminTools\Service\LogViewer;
use Carbon\Carbon;
use Core\Command\AbstractCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ImapStats extends AbstractCommand {

	protected function configure()
	{
		$this->setName("communication:imap:stats")
			->setDescription("See statistics of latest imap syncronisations")
			->addOption('days', null, InputOption::VALUE_OPTIONAL)
			->addOption('anonym', null, InputOption::VALUE_OPTIONAL)
		;
	}

	/**
	 *
	 * @param \Symfony\Component\Console\Input\InputInterface $input
	 * @param \Symfony\Component\Console\Output\OutputInterface $output
	 */
	protected function execute(InputInterface $input, OutputInterface $output): int
	{
		// ACHTUNG! In einem Logs-File stehen teilweise auch Daten von dem Folgetag drin, d.h. in dem Log vom 16. stehen
		// noch die Anfangslogs vom 17. drin, weswegen es sein kann dass man hier unterschiedliche Ergebnisse erhält wenn
		// man $days ändert

		$anonym = (bool)$input->getOption('anonym', false);
		$days = (int)$input->getOption('days', 2);

		$from = Carbon::now()->subDays($days)->startOfDay();

		$imapAccounts = \Ext_TC_Communication_Imap::getAccounts();

		$logGroups = (new LogViewer())
			->period($from)
			->file('imap')
			->levels(['INFO'])
			->read(50000)
			->reverse()
			->mapToGroups(fn (Log $log) => [$log->getDate()->toDateString() => $log]);

		$table = [];
		foreach ($logGroups as $date => $dayGroup) {

			$cronjobs = [];
			$accounts = [];
			$mapping = [];

			$dayGroup = $dayGroup->sort(fn (Log $log1, Log $log2) => $log1->getDate() < $log2->getDate())->reverse();

			foreach ($dayGroup as $log) {
				/* @var Log $log */

				$message = $log->getMessage();
				$context = $log->getParsedContext();

				$map = function ($context) use (&$mapping) {
					if (!isset($mapping[$context['account']])) {
						$mapping[$context['account']] = 'account_'.(count($mapping) + 1);
					}
					return $mapping[$context['account']];
				};

				if ($message === 'Start cronjob') {
					$cronjobs[] = [$log->getDate(), null];
				} else if ($message === 'End cronjob' && !empty($cronjobs)) {
					//$cronjobs[count($cronjobs) - 1][1] = $log->getDate();
				} else if ($message === 'Start check mails') {
					//$key = $map($context);
					//$accounts[$key][] = [$log->getDate(), null];
				} else if ($message === 'End check mails') {
					$mailsLoaded = $context['loaded'] ?? null;

					$key = $map($context);
					$accounts[$key][] = [(float)$context['duration'], $mailsLoaded];
				}
			}

			$table[] = [$date, '', '', '', '', ''];

			/*$totalCronjobs = 0;
			foreach ($cronjobs as $cronjob) {
				if (empty($cronjob[0]) || empty($cronjob[1])) {
					continue;
				}

				$totalCronjobs += abs($cronjob[1]->diffInMilliseconds($cronjob[0]));
			}*/

			$table[] = ['', 'Cronjobs', count($cronjobs), '', '', ''];

			$mapping = array_flip($mapping);

			ksort($accounts);

			$totalSyncs = 0;
			$totalMailsLoaded = 0;
			foreach ($mapping as $key => $email) {

				$syncs = $accounts[$key];

				$totalAccountSyncDuration = 0;
				$totalAccountMailsLoaded = 0;
				foreach ($syncs as $sync) {
					[$duration, $loaded] = $sync;
					$totalAccountSyncDuration += $duration;
					$totalAccountMailsLoaded += (int)$loaded;
				}

				$table[] = ['', ($anonym) ? $key : $email, count($syncs), round(($totalAccountSyncDuration / count($syncs)), 2).'s/sync', round($totalAccountSyncDuration, 2).'s', $totalAccountMailsLoaded];

				$totalSyncs += $totalAccountSyncDuration;
				$totalMailsLoaded += $totalAccountMailsLoaded;
			}

			$table[] = ['', '', '', '', '', ''];
			if (!empty($cronjobs)) {
				$table[] = ['', 'Total time', '', round(($totalSyncs / count($cronjobs)) / 60, 2) . 'm/cron', round(($totalSyncs / 60), 2) . 'm', $totalMailsLoaded];
			}

		}

		$this->table(
			['Date', 'accounts = '.count($imapAccounts), 'Syncs', '⌀', 'Total', 'Mails'],
			$table
		);

		return Command::SUCCESS;
	}

}