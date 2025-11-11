<?php

namespace TsWizard\Handler\Setup\Steps\AccommodationRoomType;

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

		if (0 < $entityId = $request->get('roomtype_id', 0)) {
			$entity = BlockAccommodationRoomTypes::entityQuery($school)->findOrFail($entityId);
			$title = $entity->getName();
		} else {
			$entity = new \Ext_Thebing_Accommodation_Roomtype();
			$entity->schools = [$school->id];
			$entity->active = 1;
			$entity->position = BlockAccommodationRoomTypes::entityQuery($school)->max('position') + 1;
			$title = $wizard->translate('Neue Raumart');
		}

		return (new Wizard\Structure\Form($entity, 'roomtype_id', $title))
			->addI18N('name', $wizard->translate('Name'), Wizard\Structure\Form::FIELD_INPUT, [
				'rules' => 'required'
			])
			->addI18N('short', $wizard->translate('Kürzel'), Wizard\Structure\Form::FIELD_INPUT, [
				'rules' => 'required'
			])
			->add('type', $wizard->translate('Zimmer Typ'), Wizard\Structure\Form::FIELD_SELECT, [
				'options' => \Ext_Thebing_Accommodation_Roomtype::getTypeOptions($wizard->getLanguageObject()),
				'rules' => 'required'
			]);

	}
}