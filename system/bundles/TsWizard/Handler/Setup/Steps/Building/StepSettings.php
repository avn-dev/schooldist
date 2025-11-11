<?php

namespace TsWizard\Handler\Setup\Steps\Building;

use Tc\Service\Wizard;
use Tc\Service\Wizard\Structure\Step;
use Illuminate\Http\Request;
use Tc\Traits\Wizard\FormStep;
use TsWizard\Traits\SchoolElement;

class StepSettings extends Step
{
	use SchoolElement, FormStep;

	public function getForm(Wizard $wizard, Request $request): Wizard\Structure\Form
	{
		$school = $this->getSchool($request);

		if (0 < $entityId = $request->get('building_id', 0)) {
			$building = BlockBuildings::entityQuery($school)->findOrFail($entityId);
			$title = $building->getName();
		} else {
			$building = new \Ext_Thebing_Tuition_Buildings();
			$building->school_id = $school->id;
			$title = $wizard->translate('Neues Gebäude');
		}

		return (new Wizard\Structure\Form($building, 'building_id', $title))
			->add('title', $wizard->translate('Bezeichnung'), Wizard\Structure\Form::FIELD_INPUT, [
				'rules' => 'required'
			]);
	}
}