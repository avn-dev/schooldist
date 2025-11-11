<?php

namespace Core\Service;

use Core\Helper\Config\FileCollector;
use Illuminate\Console\Command;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\OutputInterface;

class Console extends \Illuminate\Console\Application {
	
	public function addBundleCommands()
	{
		$files = (new FileCollector())->collectAllFileParts();

		foreach ($files as $file) {
			$commands = $file->get('commands');
			
			if (!empty($commands)) {
				foreach($commands as $commandClass) {

					if(!class_exists($commandClass)) {
						throw new \RuntimeException('Class "'.$commandClass.'" is not available!');
					}

					$this->add($this->laravel->make($commandClass));
				}
			}
		}
	}

	public function run(InputInterface $input = null, OutputInterface $output = null): int
	{
		try {

			$exitCode = parent::run($input, $output);

		} catch (\Throwable $e) {

			if (null === $output) {
				$output = new ConsoleOutput();
			}

			$this->laravel[ExceptionHandler::class]->renderForConsole($output, $e);
			$exitCode = Command::FAILURE;
		}

		return $exitCode;
	}



}