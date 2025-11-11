<?php

namespace TsWizard\Handler\Setup\Conditions\School;

use Tc\Service\Wizard;
use TsWizard\Handler\Setup\Steps\AccommodationRoomType\BlockAccommodationRoomTypes;
use Tc\Service\Wizard\Structure\AbstractElement;

class HasAccommodationRoomTypes
{
	public function __invoke(Wizard $wizard, AbstractElement $element)
	{
		$school = \Ext_Thebing_School::getInstance($element->getQueryParameter('school_id', 0));
		if (!$element->isDisabled() && BlockAccommodationRoomTypes::entityQuery($school)->first() === null) {
			$element->disable();
		}
	}
}