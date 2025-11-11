<?php

namespace Core\Command;

use Illuminate\Console\Command;
use Illuminate\Foundation\Console\KeyGenerateCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class KeyGenerate extends KeyGenerateCommand
{
	protected function configure()
	{
		$this->setName("core:key:generate")
			->addOption('if-needed')
			->setDescription("Creates application key");
	}

	protected function execute(InputInterface $input, OutputInterface $output): int
	{

		if ($this->option('if-needed') && !empty($this->laravel['config']['app.key'])) {
			$this->components->info('Application key already generated.');
			return Command::SUCCESS;
		}

		$key = $this->generateRandomKey();

		// Next, we will replace the application key in the environment file so it is
		// automatically setup for this developer. This key gets generated using a
		// secure random byte generator and is later base64 encoded for storage.
		if (! $this->setKeyInEnvironmentFile($key)) {
			return Command::FAILURE;
		}

		$this->laravel['config']['app.key'] = $key;

		$this->components->info('Application key set successfully.');

		return Command::SUCCESS;
	}

	protected function writeNewEnvironmentFileWith($key)
	{
		$file = config_path('config.php');

		$content = file_get_contents($file);

		if (!str_contains($content, "define('APP_KEY'")) {
			$contentWithoutPhpTags = str_replace(['<?php', '<?', '?>'], '', $content);
			file_put_contents($file, '<?php'.PHP_EOL.PHP_EOL."define('APP_KEY', '".$key."');".PHP_EOL.$contentWithoutPhpTags);
		} else {
			// Alten Key ersetzen
			$content = str_replace("'".$this->laravel['config']['app.key']."'", "'".$key."'", $content);
			file_put_contents($file, $content);
		}

	}

}