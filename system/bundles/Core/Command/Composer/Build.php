<?php

namespace Core\Command\Composer;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Core\Command\AbstractBuild;
use Core\Factory\ValidatorFactory;

/**
 * Build composer.json from composer-base.json and append all active bundles.
 */
class Build extends AbstractBuild {

	protected function configure() {
		$this->setName('core:composer:build')
			->setDescription('Build composer.json file from composer-base.json including all active bundles');
	}

	protected function execute(InputInterface $input, OutputInterface $output): int
	{

		$cMergeCallback = function (array $aFile, array &$aResult, string $sBundle) {

			$aResult['require'][$aFile['name']] = '*';

			// https://getcomposer.org/doc/faqs/why-can%27t-composer-load-repositories-recursively.md
			if (!empty($aFile['repositories'])) {
				$aResult['repositories'] = array_merge($aResult['repositories'], $aFile['repositories']);
			}

			if (!empty($aFile['require-dev'])) {
				$aResult['require-dev'] = array_merge($aResult['require-dev'] ?? [], $aFile['require-dev']);
			}

			// https://github.com/composer/composer/issues/1193
			if (!empty($aFile['scripts'])) {
				$aResult['scripts'] = array_merge_recursive($aResult['scripts'], $aFile['scripts']);
			}

		};

		$oValidator = (new ValidatorFactory())->make([], [
			'name' => ['required', 'starts_with:fidelo-bundle/', 'regex:/[a-z0-9_.-]+\/[a-z0-9_.-]+/'], // Regex: Composer name format from init
			'version' => ['required']
		]);

		$sFile = \Util::getDocumentRoot().'composer.json';
		$oMerge = new \Core\Service\BundleJsonFileMerge($sFile, $cMergeCallback, $oValidator);
		$oMerge->write();

		$this->outputLog($oMerge, $output);

		$this->components->info('Updated composer.json with '.count($oMerge->getResult()['require']).' requirements');

		return Command::SUCCESS;

	}

}
