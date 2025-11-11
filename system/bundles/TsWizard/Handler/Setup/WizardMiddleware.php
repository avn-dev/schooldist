<?php

namespace TsWizard\Handler\Setup;

use Tc\Middleware\AbstractWizardMiddleware;
use Tc\Service\Language\Backend;
use Tc\Service\Wizard;
use Illuminate\Http\Request;
use TsWizard\Handler\Setup\Conditions\InstallationHasSchools;

class WizardMiddleware extends AbstractWizardMiddleware
{
	protected function init(Request $request): Wizard
	{
		$l10n = (new Backend(\System::getInterfaceLanguage()))
			->setContext('Fidelo » Setup Wizard');

		$structure = function ($wizard) {
			$tree = $this->buildStructureArray();
			return Wizard\Structure::fromArray($wizard, $tree);
		};

		$access = \Access_Backend::getInstance();

		return (new Wizard('setup', 'TsWizard.setup.', $l10n, new LogStorage(), $structure))
			->heading($l10n->translate('Setup-Wizard'))
			->user($access->getUser(), $access);
	}

	protected function buildStructureArray(): array
	{
		$config = require __DIR__ . '/../../Resources/config/wizard/setup.php';

		$schools = \Ext_Thebing_School::query()->get();

		$schoolBlocks = [];
		foreach ($schools as $school) {
			$schoolBlocks['school_'.$school->id] = [
				'type' => Wizard\Structure::BLOCK,
				'title' => $school->getName(),
				'translate_title' => false,
				'info_texts' => 'school_*', // Damit die Texte für alle Schulen direkt da sind
				'queries' => ['school_id' => $school->id],
				'elements' => $config['school_block']
			];
		}

		$separator = ['type' => Wizard\Structure::SEPARATOR, 'conditions' => [InstallationHasSchools::class]];

		return array_merge(
			$config['start'],
			$config['main'],
			['separator1' => $separator],
			$schoolBlocks,
			['separator2' => $separator],
			$config['finish']
		);
	}
}