<?php

namespace Tc\Command;

use Core\Command\AbstractCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ResetIndex extends AbstractCommand {

    protected function configure() {   

        $this->setName("tc-core:index:reset")
             ->setDescription("Resets index");

    }

	/**
	 * 
	 * @param \Symfony\Component\Console\Input\InputInterface $input
	 * @param \Symfony\Component\Console\Output\OutputInterface $output
	 */
    protected function execute(InputInterface $input, OutputInterface $output): int
	{

		$this->_setDebug($output);
		
		$toolsService = \Ext_TC_System_Tools::getToolsService();

		$data = [
			'index_name' => 'all',
			'fill_stack' => true
		];

		$toolsService->executeIndexReset($data);

		$this->components->info('Indexes resetted successfully.');

		return Command::SUCCESS;
    }
	
}