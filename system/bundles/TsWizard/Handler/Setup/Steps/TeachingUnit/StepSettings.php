<?php

namespace TsWizard\Handler\Setup\Steps\TeachingUnit;

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

		if (0 < $entityId = $request->get('teaching_unit_id', 0)) {
			$category = BlockTeachingUnits::entityQuery($school)->findOrFail($entityId);
			$title = $category->title;
		} else {
			$category = $this->newTeachingUnit($school);
			$title = $wizard->translate('Neue Preiswoche');
		}

		return (new Wizard\Structure\Form($category, 'teaching_unit_id', $title))
			->add('title', $wizard->translate('Bezeichnung'), Wizard\Structure\Form::FIELD_INPUT, [
				'rules' => 'required'
			])
			->add('start_unit', $wizard->translate('Startlektion'), Wizard\Structure\Form::FIELD_INPUT, [
				'rules' => 'required|int|gt:0'
			])
			->add('unit_count', $wizard->translate('Anzahl Lektionen'), Wizard\Structure\Form::FIELD_INPUT, [
				'rules' => 'required|int|gt:0'
			])
			->add('extra', $wizard->translate('Zusatzlektion'), Wizard\Structure\Form::FIELD_CHECKBOX, [
			])
		;
	}

	protected function newTeachingUnit(\Ext_Thebing_School $school): \Ext_Thebing_School_TeachingUnit
	{
		$entity = new \Ext_Thebing_School_TeachingUnit();
		$entity->active = 1;
		$entity->schools = [$school->id];
		$entity->position = BlockTeachingUnits::entityQuery($school)->max('position') + 1;
		return $entity;
	}

}