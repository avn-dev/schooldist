<?php

namespace TsIvvy\Commands;

use Core\Command\AbstractCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use TsIvvy\Service\Synchronization;

class SyncClass extends AbstractCommand {

	protected function configure() {

		$this->setName("ivvy:sync:class")
			->setDescription("Sync Fidelo class to Ivvy");

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

		$io = new SymfonyStyle($input, $output);

		do {
			$classId = $io->ask('Class ID');

			/** @var \Ext_Thebing_Tuition_Class $class */
			$class = \Ext_Thebing_Tuition_Class::getRepository()->find((int)$classId);

			if($class === null) {
				$io->text('Class not exists...');
			}

		} while($class === null);

		try {

			$output->writeln('Start syncing...');

			$blocks = $class->getBlocks();

			foreach($blocks as $block) {
				$output->writeln('Syncing block '.$block->getId());
				Synchronization::syncEntityToIvvy($block);
			}

			$output->writeln('...finished');

		} catch (\Throwable $e) {
			$output->writeln('Something went wrong: ' . $e->getMessage());
		}

		return Command::SUCCESS;
	}

}
