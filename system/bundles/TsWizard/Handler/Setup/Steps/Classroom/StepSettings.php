<?php

namespace TsWizard\Handler\Setup\Steps\Classroom;

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

		if (0 < $entityId = $request->get('classroom_id', 0)) {
			$classroom = BlockClassrooms::entityQuery($school)->findOrFail($entityId);
			$title = $classroom->getName();
		} else {
			$classroom = new \Ext_Thebing_Tuition_Classroom();
			$classroom->idSchool = $school->id;
			$classroom->idClient = \Ext_Thebing_Client::getClientId();
			$classroom->position = BlockClassrooms::entityQuery($school)->max('position') + 1;
			$title = $wizard->translate('Neuer Klassenraum');
		}

		return (new Wizard\Structure\Form($classroom, 'classroom_id', $title))
			->add('name', $wizard->translate('Name'), Wizard\Structure\Form::FIELD_INPUT, [
				'rules' => 'required'
			])
			->add('max_students', $wizard->translate('Max. Anzahl Schüler'), Wizard\Structure\Form::FIELD_INPUT, [
				'rules' => 'required|int|gt:0'
			])
			->add('floor_id', $wizard->translate('Gebäude, Etage'), Wizard\Structure\Form::FIELD_SELECT, [
				'options' => (new \Ext_Thebing_Tuition_Floors())->getListWithBuildings(),
				'rules' => 'required'
			]);
	}
}