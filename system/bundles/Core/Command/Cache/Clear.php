<?php

namespace Core\Command\Cache;

use Core\Command\AbstractCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Clear extends AbstractCommand {

    protected function configure() {   

        $this->setName("core:cache:clear")
             ->setDescription("Clear cache");

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

		$oCacheHelper = new \Core\Helper\Cache();
		$oCacheHelper->clearAll();

		$this->components->info('Cached cleared successfully.');

		return Command::SUCCESS;
    }
	
}