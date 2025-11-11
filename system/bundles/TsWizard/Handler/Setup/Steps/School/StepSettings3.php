<?php

namespace TsWizard\Handler\Setup\Steps\School;

use Illuminate\Support\Arr;
use Tc\Service\Wizard;
use Illuminate\Http\Request;
use Tc\Traits\Wizard\FormStep;
use TsWizard\Traits\SchoolElement;

class StepSettings3 extends Wizard\Structure\Step
{
	use SchoolElement, FormStep;

	public function getForm(Wizard $wizard, Request $request): Wizard\Structure\Form
	{
		$school = $this->getSchool($request);
		$title = $school->ext_1;

		return (new Wizard\Structure\Form($school, 'school_id', $title))
			->add('aCurrencies', $wizard->translate('Währungen'), Wizard\Structure\Form::FIELD_SELECT, [
				'options' => \Ext_Thebing_Data_Currency::getCurrencyList(),
				'multiple' => true,
				'rules' => 'required'
			])
		;
	}

	public function prepareEntity(Wizard $wizard, Request $request, \WDBasic $entity): void
	{
		$firstCurrency = Arr::first($entity->aCurrencies);

		$setCurrency = function($field, $currency) use ($entity) {
			if (empty($entity->{$field})) {
				$entity->{$field} = $currency;
			}
		};

		$setCurrency('currency', $firstCurrency);
		$setCurrency('currency_teacher', $firstCurrency);
		$setCurrency('currency_accommodation', $firstCurrency);
		$setCurrency('currency_transfer', $firstCurrency);

	}

}