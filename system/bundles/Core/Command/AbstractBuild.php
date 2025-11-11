<?php

namespace Core\Command;

use Core\Command\AbstractCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Output\OutputInterface;
use Core\Service\BundleJsonFileMerge;

abstract class AbstractBuild extends AbstractCommand {

	public function outputLog(BundleJsonFileMerge $merge, OutputInterface $output)
	{
		foreach($merge->getLog() as $level => $log) {

			foreach($log as $message) {

				if($level === \Monolog\Logger::INFO && $output->isVerbose()) {
					$output->writeln('<info>'.$message.'</info>');
				}

				if($level === \Monolog\Logger::ERROR) {
					$output->writeln('<error>'.$message.'</error>');
				}

			}

		}
	}

}
