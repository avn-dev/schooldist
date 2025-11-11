<?php

namespace TsWizard\Handler\Setup\Conditions\School;

use Tc\Service\Wizard;
use Tc\Service\Wizard\Structure\AbstractElement;
use TsWizard\Handler\Setup\Steps\AccommodationCategory\BlockAccommodationCategories;

class HasAccommodationCategories
{
	public function __invoke(Wizard $wizard, AbstractElement $element)
	{
		$school = \Ext_Thebing_School::getInstance($element->getQueryParameter('school_id', 0));
		if (!$element->isDisabled() && BlockAccommodationCategories::entityQuery($school)->first() === null) {
			$element->disable();
		}
	}
}