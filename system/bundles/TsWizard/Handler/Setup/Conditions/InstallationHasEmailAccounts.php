<?php

namespace TsWizard\Handler\Setup\Conditions;

use Tc\Service\Wizard;
use Tc\Service\Wizard\Structure\AbstractElement;

class InstallationHasEmailAccounts
{
	public function __invoke(Wizard $wizard, AbstractElement $element)
	{
		if (!$element->isDisabled() && \Ext_TC_Communication_EmailAccount::query()->first() === null) {
			$element->disable();
		}
	}
}