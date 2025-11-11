<?php

namespace Core\Command\Routing;

use Core\Command\AbstractCommand;
use Core\Service\RoutingService;
use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Update extends AbstractCommand {

    protected function configure() {   

        $this->setName("core:routing:update")
             ->setDescription("Create routes");

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
		
		// Ein Updateobjekt instanziieren
		$oRoutingService = new RoutingService();
		// Führe das Update durch und speichere den Erfolg
		$bSuccess = $oRoutingService->buildRoutes();

		if (!$bSuccess) {
			$this->components->error('Building of routes failed.');
			return Command::FAILURE;
		}

		$this->components->info('Updated routes successfully.');

        return Command::SUCCESS;

    }
	
}