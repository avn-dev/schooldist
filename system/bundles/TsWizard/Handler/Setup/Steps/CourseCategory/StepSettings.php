<?php

namespace TsWizard\Handler\Setup\Steps\CourseCategory;

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

		if (0 < $entityId = $request->get('category_id', 0)) {
			$category = BlockCategories::entityQuery($school)->findOrFail($entityId);
			$title = $category->getName();
		} else {
			$category = $this->newCategory($school);
			$title = $wizard->translate('Neue Kurskategorie');
		}

		return (new Wizard\Structure\Form($category, 'category_id', $title))
			->addI18N('name', $wizard->translate('Name'), Wizard\Structure\Form::FIELD_INPUT, [
				'languages' => $school->getLanguages(),
				'rules' => 'required'
			]);
	}

	protected function newCategory(\Ext_Thebing_School $school): \Ext_Thebing_Tuition_Course_Category
	{
		$category = new \Ext_Thebing_Tuition_Course_Category();
		$category->schools = [$school->id];
		$template = $category->planification_template;
		$category->planification_template = $template;
		$category->position = BlockCategories::entityQuery($school)->max('position') + 1;
		return $category;
	}

}