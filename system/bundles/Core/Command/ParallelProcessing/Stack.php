<?php

namespace Core\Command\ParallelProcessing;

use Core\Command\AbstractCommand;
use Core\Entity\ParallelProcessing\Stack as StackEntity;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Stack extends AbstractCommand {

    protected function configure() {   

        $this->setName("core:parallelprocessing:stack")
             ->setDescription("Returns all waiting tasks");

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
		
		$oRepository = StackEntity::getRepository();
		/* @var $oRepository \Core\Entity\ParallelProcessing\StackRepository */
		$aItems = $oRepository->getEntries(null, 2000);
		
		$aReturn = array(
			'data' => (array) $aItems,
			'license_key' => \System::d('license'),
			'version' => 3
		);
		
        $output->writeln(json_encode($aReturn));

		return Command::SUCCESS;

    }
	
}