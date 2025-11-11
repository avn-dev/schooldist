<?php

namespace Core\Command\Bundles;

use Core\Command\AbstractCommand;
use Core\Helper\Bundle;
use Core\Service\BundleService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class Config extends AbstractCommand {

	protected function configure() {
		$this->setName('core:bundles:config --json')
			->addOption('json', null, InputOption::VALUE_NONE, 'Output as JSON (default)')
			->setDescription('Output active bundle config (internal usage)');
	}

	protected function execute(InputInterface $input, OutputInterface $output): int
	{

		$this->_setDebug($output);

		$bundles = (new BundleService())->getActiveBundleNames();
		$helper = new Bundle();

		$bundleConfigs = [];
		foreach ($bundles as $bundle) {
			$config = $helper->getBundleConfigData($bundle, false);
			$config['name'] = $bundle;
			$config['path'] = $helper->getBundleResourcesDirectory($bundle, false);
			$bundleConfigs[] = $config;
		}

		$output->writeln(json_encode($bundleConfigs));

		return Command::SUCCESS;

	}

}