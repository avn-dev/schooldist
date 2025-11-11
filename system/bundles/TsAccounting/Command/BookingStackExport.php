<?php

namespace TsAccounting\Command;

use Core\Command\AbstractCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use TsAccounting\Service\AutomationService;

class BookingStackExport extends AbstractCommand {

    protected function configure() {   

        $this->setName("ts-accounting:bookingstack:export")
             ->setDescription("Automatic bookingstack export");

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

        $output->writeln('Start exporting');

        AutomationService::startBookingstackExport();

        $output->writeln('...finished!');

		return Command::SUCCESS;
    }
	
}
