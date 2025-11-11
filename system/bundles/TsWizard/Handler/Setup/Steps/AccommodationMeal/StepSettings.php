<?php

namespace TsWizard\Handler\Setup\Steps\AccommodationMeal;

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

		if (0 < $entityId = $request->get('meal_id', 0)) {
			$entity = BlockAccommodationMeals::entityQuery($school)->findOrFail($entityId);
			$title = $entity->getName();
		} else {
			$entity = new \Ext_Thebing_Accommodation_Meal();
			$entity->schools = [$school->id];
			$entity->active = 1;
			$entity->position = BlockAccommodationMeals::entityQuery($school)->max('position') + 1;
			$title = $wizard->translate('Neue Verpflegung');
		}

		return (new Wizard\Structure\Form($entity, 'meal_id', $title))
			->addI18N('name', $wizard->translate('Name'), Wizard\Structure\Form::FIELD_INPUT, [
				'rules' => 'required'
			])
			->addI18N('short', $wizard->translate('Kürzel'), Wizard\Structure\Form::FIELD_INPUT, [
				'rules' => 'required'
			])
			->add('meal_plan_breakfast', $wizard->translate('Frühstück'), Wizard\Structure\Form::FIELD_CHECKBOX)
			->add('meal_plan_lunch', $wizard->translate('Mittagessen'), Wizard\Structure\Form::FIELD_CHECKBOX)
			->add('meal_plan_dinner', $wizard->translate('Abendessen'), Wizard\Structure\Form::FIELD_CHECKBOX);
	}
}