<?php

namespace Core\Command\Globalchecks;

use Core\Command\AbstractCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;

/**
 * v3
 */
class Execute extends AbstractCommand {
	
	/**
	 * Konfiguriert eigene Befehle für die Symfony2-Konsole
	 */
    protected function configure() {
		
        $this->setName("core:globalchecks:execute")
             ->setDescription("Executes check")
			 ->addArgument('check', InputArgument::OPTIONAL, 'Name of Globalcheck class. If empty, first check in stack will be executed!');

    }

	/**
	 * Führt die übergebenen Tasks aus
	 * 
	 * @param \Symfony\Component\Console\Input\InputInterface $input
	 * @param \Symfony\Component\Console\Output\OutputInterface $output
	 */
    protected function execute(InputInterface $input, OutputInterface $output): int
	{

		\Core\Handler\SessionHandler::getInstance()->set('system_checks_execute_checks', true);

		$this->_setDebug($output);
		
		$aReturn = array();
		$aReturn['success'] = 0;
		
		$sCheck = $input->getArgument('check');

		if(empty($sCheck)) {
			
			$oGlobalChecks = new \GlobalChecks;
			$oCheck = $oGlobalChecks->getUsedClass();
			
			if(empty($oCheck)) {
				$output->writeln('No pending check found!');
				return 0;
			}
				
		} else {
			
			if(!class_exists($sCheck)) {
				$output->writeln('Check does not exist!');
				return 1;
			}
				
			$oCheck = new $sCheck;
			
		}
		
		$output->writeln('Check "'.get_class($oCheck).'"!');
		
		if(!$oCheck instanceof \GlobalChecks) {
			$output->writeln('Check is not instance of GlobalChecks!');
			return 1;
		}

		$bSuccess = $oCheck->executeCheck();
		
		if($bSuccess === true) {
			
			if(isset($oGlobalChecks)) {
				$oGlobalChecks->updateCheck();
			}
			
			$output->writeln('Check executed successfully!');
			
		} else {
			
			$output->writeln('Check failed!');
			
		}

		return Command::SUCCESS;

    }

}