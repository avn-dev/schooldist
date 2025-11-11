<?php

namespace Office\Command;

use Core\Command\AbstractCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Command\Command;

class Cronjob extends AbstractCommand {

    protected function configure() {   

        $this->setName("office:cronjob")
             ->setDescription("Execute timeclock checks and send out payment reminders.");

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

		$oLog = \Log::getLogger('office');

		$oLog->addInfo('Cronjob start');

		$oTimeClockService = new \Office\Service\Timeclock;
		$oTimeClockService->execute();
		
		$oReminderService = new \Office\Service\Reminder;
		$oReminderService->execute();
		
		$oLog->addInfo('Cronjob end');
		
        $output->writeln(json_encode(true));

		return Command::SUCCESS;
    }
	
}