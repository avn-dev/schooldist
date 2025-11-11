<?php

namespace TsFlywire\Commands;

use Core\Command\AbstractCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use TsFlywire\Service\SyncFiles;
use TsMews\Hook\CronjobHook;

class FileSync extends AbstractCommand
{
	protected function configure()
	{
		$this->setName("flywire:sync")
			->setDescription("Sync Flywire files");
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

		$output->writeln('Start syncing from Flywire');

		SyncFiles::logger()->info('Start sync from cli');

		try {

			SyncFiles::default()->sync();

			\Ext_Gui2_Index_Stack::save(true);

		} catch (\Throwable $e) {
			$output->writeln('Something went wrong: ' . $e->getMessage());
			return Command::FAILURE;
		}

		$output->writeln('...finished!');

		return Command::SUCCESS;
	}
}