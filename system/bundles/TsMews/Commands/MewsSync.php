<?php

namespace TsMews\Commands;

use Core\Command\AbstractCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use TsMews\Hook\CronjobHook;

class MewsSync extends AbstractCommand {

    protected function configure() {   

        $this->setName("mews:sync")
             ->setDescription("Sync Mews reservations");

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

        $output->writeln('Start syncing from Mews Systems');

        try {

            $operation = (new CronjobHook('backend', ''))->fromCLI();
			$operation->run();

        } catch (\Exception $ex) {
            $output->writeln('Something went wrong: ' . $ex->getMessage());
        } catch (\Error $e) {
            $output->writeln('Something went wrong: ' . $e->getMessage());
        }

        $output->writeln('...finished!');

		return Command::SUCCESS;
    }
	
}
