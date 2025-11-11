<?php

namespace Core\Command\Cache;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Forget extends Command {

	protected function configure() {

		$this
			->setName('core:cache:forget')
			->addArgument('key', InputArgument::REQUIRED)
			->setDescription('Remove an item from the cache');

	}

	protected function execute(InputInterface $input, OutputInterface $output): int
	{

		\WDCache::delete($input->getArgument('key'));

		return Command::SUCCESS;

	}

}