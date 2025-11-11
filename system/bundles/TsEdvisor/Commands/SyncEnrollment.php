<?php

namespace TsEdvisor\Commands;

use Core\Command\AbstractCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use TsEdvisor\Service\Sync;

class SyncEnrollment extends AbstractCommand
{
	protected function configure()
	{
		$this->setName('ts-edvisor:sync-enrollment')
			->setDescription('Import or update Edvisor enrollment')
			->addArgument('enrollment-id', InputArgument::REQUIRED, 'Edvisor Enrollment ID');
	}

	protected function execute(InputInterface $input, OutputInterface $output): int
	{
		$id = (int)$input->getArgument('enrollment-id');

		(new Sync())->syncEnrollment($id);

		return Command::SUCCESS;
	}
}
