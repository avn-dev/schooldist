<?php

namespace TsWizard\Traits;

use Illuminate\Http\Request;

trait BuildingElement
{
	use SchoolElement;

	protected function getBuilding(Request $request): \Ext_Thebing_Tuition_Buildings
	{
		$buildingId = (int)$request->get('building_id', 0);

		if ($buildingId === 0) {
			return new \Ext_Thebing_Tuition_Buildings();
		}

		return \Ext_Thebing_Tuition_Buildings::getInstance($buildingId);
	}
}