<?php

namespace TsWizard\Handler\Setup\Conditions\School;

use Tc\Service\Wizard;
use Tc\Service\Wizard\Structure\AbstractElement;
use TsWizard\Handler\Setup\Steps\Building\BlockBuildings;
use TsWizard\Handler\Setup\Steps\TeachingUnit\BlockTeachingUnits;

class HasBuildings
{
	public function __invoke(Wizard $wizard, AbstractElement $element)
	{
		if (!$element->isDisabled()) {
			$school = \Ext_Thebing_School::getInstance($element->getQueryParameter('school_id', 0));
			$buildings = BlockBuildings::entityQuery($school)->pluck('id');
			if ($buildings->isEmpty()) {
				$element->disable();
			} else {
				$floors = \Ext_Thebing_Tuition_Floors::query()->whereIn('building_id', $buildings)->pluck('id');
				if ($floors->isEmpty()) {
					$element->disable();
				}
			}
		}
	}
}