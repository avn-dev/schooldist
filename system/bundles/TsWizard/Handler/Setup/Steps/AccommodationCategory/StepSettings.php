<?php

namespace TsWizard\Handler\Setup\Steps\AccommodationCategory;

use Tc\Service\Wizard;
use Tc\Service\Wizard\Structure\Step;
use Illuminate\Http\Request;
use Tc\Traits\Wizard\FormStep;
use TsAccommodation\Entity\Provider\SchoolSetting;
use TsWizard\Traits\SchoolElement;

class StepSettings extends Step
{
	use SchoolElement, FormStep;

	public function getForm(Wizard $wizard, Request $request): Wizard\Structure\Form
	{
		$school = $this->getSchool($request);

		if (0 < $entityId = $request->get('category_id', 0)) {
			$entity = BlockAccommodationCategories::entityQuery($school)->findOrFail($entityId);
			$title = $entity->getName();
		} else {
			$entity = new \Ext_Thebing_Accommodation_Category();
			$entity->active = 1;
			$entity->position = BlockAccommodationCategories::entityQuery($school)->max('position') + 1;
			$title = $wizard->translate('Neue Unterkunft');
		}

		$priceOptions = \Ext_Thebing_Accommodation_Category_Gui2::getPriceOptions();
		unset($priceOptions[\Ext_Thebing_Accommodation_Amount::PRICE_PER_NIGHT_WEEKS]);

		return (new Wizard\Structure\Form($entity, 'category_id', $title))
			->addI18N('name', $wizard->translate('Name'), Wizard\Structure\Form::FIELD_INPUT, [
				'rules' => 'required'
			])
			->addI18N('short', $wizard->translate('Kürzel'), Wizard\Structure\Form::FIELD_INPUT, [
				'rules' => 'required'
			])
			->add('type_id', $wizard->translate('Kategorietyp'), Wizard\Structure\Form::FIELD_SELECT, [
				'options' => \Ext_Thebing_Accommodation_Category::getTypeOptions($wizard->getLanguageObject()),
				'rules' => 'required'
			])
			->add('price_structure', $wizard->translate('Preis'), Wizard\Structure\Form::FIELD_SELECT, [
				'options' => $priceOptions,
				'value' => function (\Ext_Thebing_Accommodation_Category $category) use ($school) {
					return $category->getSetting($school)?->price_night;
				},
				'save' => function (\Ext_Thebing_Accommodation_Category $category, $request) use ($school) {
					$value = (int)$request->input('price_structure');
					$setting = $category->getSetting($school);

					if (!$setting) {
						$setting = new SchoolSetting();
						$setting->schools = [$school->id];
						if ($value === \Ext_Thebing_Accommodation_Amount::PRICE_PER_WEEK) {
							$setting->weeks = [BlockAccommodationCategories::getOrCreatePriceWeek($school)->id];
						}
						$category->setJoinedObjectChild('school_settings', $setting);
					}
					$setting->price_night = $value;
				},
				'rules' => 'required'
			]);
	}
}