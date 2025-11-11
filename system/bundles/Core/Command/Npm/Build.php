<?php

namespace Core\Command\Npm;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Core\Factory\ValidatorFactory;
use Core\Command\AbstractBuild;
use Core\Helper\Bundle;

/**
 * Build package.json from package-base.json and append all active bundles.
 */
class Build extends AbstractBuild {

	protected function configure() {
		$this->setName('core:npm:build')
			->setDescription('Build package.json file from package-base.json including all active bundles');
	}

	protected function execute(InputInterface $input, OutputInterface $output): int
	{

		$cMergeCallback = function(array $aFile, array &$aResult, string $sBundle) {

			$sDir = (new Bundle())->getBundleDirectory($sBundle);
			$sDir = './'.str_replace(\Util::getDocumentRoot(), '', $sDir);

			$aResult['dependencies'][$aFile['name']] = 'file:'.$sDir;

			if (!empty($aFile['devDependencies'])) {
				$aResult['devDependencies'] = array_merge($aResult['devDependencies'] ?? [], $aFile['devDependencies']);
			}

		};

		$oValidator = (new ValidatorFactory())->make([], [
			'name' => ['required', 'starts_with:@fidelo-bundle/', 'regex:/[a-z0-9_.-]+\/[a-z0-9_.-]+/'],
			'version' => ['required'] // Required for npm update
		]);

		$sFile = \Util::getDocumentRoot().'package.json';
		$oMerge = new \Core\Service\BundleJsonFileMerge($sFile, $cMergeCallback, $oValidator);
		$oMerge->write();

		$this->outputLog($oMerge, $output);

		$this->components->info('Updated package.json with '.count($oMerge->getResult()['dependencies']).' dependencies');

		return Command::SUCCESS;

	}

}
