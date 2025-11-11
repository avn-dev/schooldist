<?php

namespace TsGel\Commands;

use Core\Command\AbstractCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use TsGel\Api;

class SendAllBookings extends AbstractCommand
{
	protected function configure()
	{
		$this->setName("gel:booking:all")
			->setDescription("Send booking to GEL-Api");
	}

	protected function execute(InputInterface $input, OutputInterface $output): int
	{
		$this->_setDebug($output);

		$attributes = \WDBasic_Attribute::query()->where('key', 'gel_sent')->get();

		if ($attributes->isEmpty()) {
			$this->components->info('No bookings has been sent yet.');
			return Command::SUCCESS;
		}

		$count = 0;
		foreach ($attributes as $attribute) {
			/* @var \WDBasic_Attribute $attribute */
			$inquiry = \Ext_TS_Inquiry::getInstance($attribute->entity_id);

			if ($inquiry->exist() && $inquiry->isActive()) {
				Api::default()->sendBooking($inquiry);
				++$count;
			}
		}

		$this->components->info(sprintf('Sent %d bookings', $count));

		return Command::SUCCESS;
	}
}