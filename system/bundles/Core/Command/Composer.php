<?php

namespace Core\Command;

use Core\Command\AbstractCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * v1
 */
class Composer extends AbstractCommand {

    protected function configure() {   

        $this->setName("core:composer:update")
             ->setDescription("Creates composer.json and executes update");

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

		$output->writeln('<comment>'.$this->getName().' command is deprecated. Use ./console core:composer:build && ./composer.phar update instead.</comment>');
		
		// Ein Updateobjekt instanziieren
		$oUpdate = new \Update();
		// Führe das Update durch und speichere den Erfolg
		$bSuccess = $oUpdate->executeComposerUpdate();
		
        $output->writeln(json_encode($bSuccess));

		return Command::SUCCESS;
    }
	
}