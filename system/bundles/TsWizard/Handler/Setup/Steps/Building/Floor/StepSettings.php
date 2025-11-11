<?php

namespace TsWizard\Handler\Setup\Steps\Building\Floor;

use TsWizard\Traits\BuildingElement;
use Tc\Service\Wizard;
use Tc\Service\Wizard\Structure\Step;
use Tc\Traits\Wizard\FormStep;
use Illuminate\Http\Request;

class StepSettings extends Step
{
	use BuildingElement, FormStep;

	public function getForm(Wizard $wizard, Request $request): Wizard\Structure\Form
	{
		$building = $this->getBuilding($request);

		if (0 < $entityId = $request->get('floor_id', 0)) {
			$entity = BlockFloors::entityQuery($building)->findOrFail($entityId);
			$title = $building->title.' &raquo; '.$entity->title;
		} else {
			$entity = new \Ext_Thebing_Tuition_Floors();
			$entity->building_id = $building->id;
			$title = $building->title.' &raquo; '.$wizard->translate('Neue Etage');
		}

		return (new Wizard\Structure\Form($entity, 'floor_id', $title))
			->add('title', $wizard->translate('Bezeichnung'), Wizard\Structure\Form::FIELD_INPUT, [
				'rules' => 'required'
			]);
	}
}