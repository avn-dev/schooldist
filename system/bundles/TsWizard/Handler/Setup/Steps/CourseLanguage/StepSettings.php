<?php

namespace TsWizard\Handler\Setup\Steps\CourseLanguage;

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

		if (0 < $entityId = $request->get('language_id', 0)) {
			$language = BlockLanguages::entityQuery($school)->findOrFail($entityId);
			$title = $language->getName();
		} else {
			$language = new \Ext_Thebing_Tuition_LevelGroup();
			$language->school_id = $school->id;
			$language->position = BlockLanguages::entityQuery($school)->max('position') + 1;
			$title = $wizard->translate('Neue Kurssprache');
		}

		return (new Wizard\Structure\Form($language, 'language_id', $title))
			->addI18N('name', $wizard->translate('Name'), Wizard\Structure\Form::FIELD_INPUT, [
				'languages' => $school->getLanguages(),
				'rules' => 'required'
			]);
	}
}