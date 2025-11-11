<?php

namespace Tc\Command;

use Core\Command\AbstractCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class StoredFunctions extends AbstractCommand {

    protected function configure() {   

        $this->setName("tc-core:stored-functions:init")
             ->setDescription("Creates stored functions in database");

    }

	/**
	 * 
	 * @param \Symfony\Component\Console\Input\InputInterface $input
	 * @param \Symfony\Component\Console\Output\OutputInterface $output
	 */
    protected function execute(InputInterface $input, OutputInterface $output): int
	{

		$this->_setDebug($output);

		\Factory::executeStatic(\Ext_TC_Db_StoredFunctions::class, 'updateStoredFunctions');

		$this->components->info('Stored functions updated successfully.');

		return Command::SUCCESS;
    }
	
}