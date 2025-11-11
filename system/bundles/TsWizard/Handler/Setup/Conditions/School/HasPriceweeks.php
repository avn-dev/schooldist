<?php

namespace TsWizard\Handler\Setup\Conditions\School;

use Tc\Service\Wizard;
use Tc\Service\Wizard\Structure\AbstractElement;
use TsWizard\Handler\Setup\Steps\PriceWeek\BlockPriceWeeks;

class HasPriceweeks
{
	public function __invoke(Wizard $wizard, AbstractElement $element)
	{
		if (!$element->isDisabled()) {
			$school = \Ext_Thebing_School::getInstance($element->getQueryParameter('school_id', 0));
			if (BlockPriceWeeks::entityQuery($school)->first() === null) {
				$element->disable();
			}
		}
	}
}