<?php

namespace TsWizard\Handler\Setup\Steps\Building\Floor;

use Illuminate\Http\Request;
use Tc\Service\Wizard;
use Tc\Service\Wizard\Structure\Step;
use TsWizard\Traits\BuildingElement;

class BlockFloors extends Wizard\Structure\Block
{
	use BuildingElement;

	public function getFirstStep(): ?Step
	{
		$building = $this->getBuilding(app(Request::class));

		if (self::entityQuery($building)->pluck('id')->isEmpty()) {
			// Wenn es noch keine Etagen gibt direkt auf das Formular weiterleiten, um ein Konto anzulegen
			return $this->get('form')->getFirstStep();
		}

		return parent::getFirstStep();
	}

	public static function entityQuery(\Ext_Thebing_Tuition_Buildings $building)
	{
		return \Ext_Thebing_Tuition_Floors::query()
			->where('building_id', $building->id);
	}

}