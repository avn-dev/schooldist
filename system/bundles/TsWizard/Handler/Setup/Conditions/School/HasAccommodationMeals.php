<?php

namespace TsWizard\Handler\Setup\Conditions\School;

use Tc\Service\Wizard;
use TsWizard\Handler\Setup\Steps\AccommodationMeal\BlockAccommodationMeals;
use Tc\Service\Wizard\Structure\AbstractElement;

class HasAccommodationMeals
{
	public function __invoke(Wizard $wizard, AbstractElement $element)
	{
		$school = \Ext_Thebing_School::getInstance($element->getQueryParameter('school_id', 0));
		if (!$element->isDisabled() && BlockAccommodationMeals::entityQuery($school)->first() === null) {
			$element->disable();
		}
	}
}