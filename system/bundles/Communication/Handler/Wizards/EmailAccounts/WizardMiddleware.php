<?php

namespace Communication\Handler\Wizards\EmailAccounts;

use Illuminate\Http\Request;
use Tc\Middleware\AbstractWizardMiddleware;
use Tc\Service\Language\Backend;
use Tc\Service\Wizard;

class WizardMiddleware extends AbstractWizardMiddleware
{
	protected function init(Request $request): Wizard
	{
		$l10n = (new Backend(\System::getInterfaceLanguage()))
			->setContext('Fidelo » Wizard » E-Mail-Accounts');

		$structure = function ($wizard) {
			$tree = $this->buildStructureArray();
			return Wizard\Structure::fromArray($wizard, $tree);
		};

		$access = \Access_Backend::getInstance();

		return (new Wizard('email_accounts', 'Communication.email_accounts.wizard.', $l10n, new LogStorage(), $structure))
			->heading($l10n->translate('E-Mail-Konten'))
			->user($access->getUser(), $access)
			->disable('stop_and_continue')
			->disable('progress_icons')
			->index('list.list')
		;
	}

	protected function buildStructureArray(): array
	{
		$config = require __DIR__. '/../../../Resources/config/wizards/email_accounts.php';
		return ['list' => $config];
	}
}