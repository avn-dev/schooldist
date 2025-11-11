<?php

namespace TsAccounting\Command;

use Core\Command\AbstractCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use TsAccounting\Service\AutomationService;


class EntityRelease extends AbstractCommand {

    protected function configure() {   

        $this->setName("ts-accounting:entity:release")
             ->setDescription("Automatic entity release");

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

        $output->writeln('Start releasing documents');

		AutomationService::startDocumentRelease();

		$output->writeln('...finished!');

		$output->writeln('Start releasing payments');

		AutomationService::startPaymentRelease();

        $output->writeln('...finished!');

		return Command::SUCCESS;
    }
	
}
