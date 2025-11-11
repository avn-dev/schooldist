<?php

namespace TsIvvy\Commands;

use Core\Command\AbstractCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use TsIvvy\Api;

class ApiPing extends AbstractCommand {

	protected function configure() {

		$this->setName("ivvy:ping")
			->setDescription("Api ping to test connection");

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

		try {

			Api::default()->ping();

			$output->writeln('...done!');

		} catch (\Throwable $e) {
			$output->writeln('Something went wrong: ' . $e->getMessage());
		}

		return Command::SUCCESS;
	}

}
