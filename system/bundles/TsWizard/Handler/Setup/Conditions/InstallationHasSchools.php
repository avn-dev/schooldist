<?php

namespace TsWizard\Handler\Setup\Conditions;

use Tc\Service\Wizard;
use Tc\Service\Wizard\Structure\AbstractElement;
use Tc\Service\Wizard\Structure\Separator;

class InstallationHasSchools
{
	public function __invoke(Wizard $wizard, AbstractElement $element)
	{
		if (!$element->isDisabled() && \Ext_Thebing_School::query()->first() === null) {
			$element->disable();

			if ($element instanceof Separator) {
				$element->hide();
			}
		}
	}
}