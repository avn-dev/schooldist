<?php

namespace TsIvvy\Commands;

use Core\Command\AbstractCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use TsIvvy\Api;
use TsIvvy\Service\Synchronization;

class ApiSync extends AbstractCommand {

	protected function configure() {

		$this->setName("ivvy:sync")
			->setDescription("Sync Ivvy bookings into Fidelo")
			->addOption('modified_after', null, InputOption::VALUE_OPTIONAL, 'Define modified after date (Y-m-d)');

	}

	/**
	 * Gibt den Stack als JSON aus
	 *
	 * @param \Symfony\Component\Console\Input\InputInterface $input
	 * @param \Symfony\Component\Console\Output\OutputInterface $output
	 */
	protected function execute(InputInterface $input, OutputInterface $output): int
	{

		$this->_setDebug($output);

		$modifiedAfter = $input->getOption('modified_after');
		if ($modifiedAfter !== null) {
			$modifiedAfter = \DateTime::createFromFormat('Y-m-d', $modifiedAfter);

			if (!$modifiedAfter) {
				$output->writeln('Invalid modified after date.');
				return Command::FAILURE;
			}

			$modifiedAfter->setTime(0, 0, 0);
		}

		try {

			Api::getLogger()->info('CLI: Sync', ['modified_after' => ($modifiedAfter) ? $modifiedAfter->format('Y-m-d H:i:s') : null]);

			$started = time();
			[$synced, $failed] = Synchronization::syncFromIvvy($modifiedAfter);

			\System::s('ivvy_last_sync', $started);

			Api::getLogger()->info('CLI: Sync finished', ['synced' => (int)$synced, 'failed' => (int)$failed]);

			$output->writeln('Synced: '. (int)$synced);
			if ($synced > 0) {
				$output->writeln('Failed: ' . (int)$failed);
			}

		} catch (\Throwable $e) {
			$output->writeln('Something went wrong: ' . $e->getMessage());

			Api::getLogger()->error('CLI: Sync failed', ['exception' => $e->getMessage(), 'file' => $e->getFile(), 'line' => $e->getLine()]);

			return Command::FAILURE;
		}

		return Command::SUCCESS;
	}

}
