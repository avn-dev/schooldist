<?php

namespace TsWizard\Handler\Setup\Steps\PriceWeek;

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

		if (0 < $entityId = $request->get('week_id', 0)) {
			$category = BlockPriceWeeks::entityQuery($school)->findOrFail($entityId);
			$title = $category->title;
		} else {
			$category = $this->newPriceWeek($school);
			$title = $wizard->translate('Neue Preiswoche');
		}

		return (new Wizard\Structure\Form($category, 'week_id', $title))
			->add('title', $wizard->translate('Bezeichnung'), Wizard\Structure\Form::FIELD_INPUT, [
				'rules' => 'required'
			])
			->add('start_week', $wizard->translate('Startwoche'), Wizard\Structure\Form::FIELD_INPUT, [
				'rules' => 'required|int|gt:0'
			])
			->add('week_count', $wizard->translate('Wochenanzahl'), Wizard\Structure\Form::FIELD_INPUT, [
				'rules' => 'int|gt:0|nullable'
			])
			->add('extra', $wizard->translate('Extrawoche'), Wizard\Structure\Form::FIELD_CHECKBOX, [
			])
		;
	}

	protected function newPriceWeek(\Ext_Thebing_School $school): \Ext_Thebing_School_Week
	{
		$entity = new \Ext_Thebing_School_Week();
		$entity->active = 1;
		$entity->schools = [$school->id];
		$entity->position = BlockPriceWeeks::entityQuery($school)->max('position') + 1;
		return $entity;
	}

}