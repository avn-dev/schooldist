<?php

namespace TsGel\Commands;

use Core\Command\AbstractCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use TsGel\Api;

class SendBooking extends AbstractCommand
{
	protected function configure()
	{
		$this->setName("gel:booking:send")
			->addArgument('id', InputArgument::REQUIRED, 'Booking ID')
			->setDescription("Send booking to GEL-Api");
	}

	protected function execute(InputInterface $input, OutputInterface $output): int
	{
		$this->_setDebug($output);

		$inquiry = \Ext_TS_Inquiry::query()->find($input->getArgument('id'));

		if ($inquiry === null) {
			$this->components->error('Unknown inquiry');
			return Command::INVALID;
		}

		$response = Api::default()->sendBooking($inquiry, true);

		if (!$response->successful()) {
			$this->components->error('API-Request failed');
			return Command::FAILURE;
		}

		$this->components->info('API-Request successfully');
		return Command::SUCCESS;
	}
}