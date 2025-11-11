<?php

namespace Core\Command\Database;

use Core\Command\AbstractCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class SqlFileMigration extends AbstractCommand
{
	protected function configure()
	{
		// NICHT auf einem Live-System ausführen!
		$this->setName("core:migrate:sql")
			->addOption('system', null, InputOption::VALUE_OPTIONAL)
			->setDescription("Migrate sql files for local development");
	}

	protected function execute(InputInterface $input, OutputInterface $output): int
	{
		$systems = ['v5', 'core', 'school', 'agency'];
		if (!empty($system = $input->getOption('system'))) {
			if (!in_array($system, $systems)) {
				throw new \InvalidArgumentException("The argument 'system' must be one of 'v5', 'core', 'school', 'agency'");
			}
			$systems = [$system];
		}

		foreach ($systems as $system) {

			$this->components->info("Migrating system '{$system}'");

			if (!is_dir(\Util::getDocumentRoot().'update_queries/'.$system)) {
				$this->components->error("The update queries directory '{$system}' does not exist.");
				continue;
			}

			$debug = \Ext_LocalDev_Sql::update($system);

			if (!empty($debug['query'])) {
				foreach ($debug['query'] as $query) {
					if (!$query['success']) {
						$this->components->error("Query failed with error.");
						$this->components->error($query['query']);
						$this->components->error($query['error']);
					}
				}
			}

			$this->components->info("Done...");
		}

		return Command::SUCCESS;
	}
}