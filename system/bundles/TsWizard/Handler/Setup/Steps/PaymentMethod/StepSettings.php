<?php

namespace TsWizard\Handler\Setup\Steps\PaymentMethod;

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

		if (0 < $entityId = $request->get('payment_method_id', 0)) {
			$entity = BlockPaymentMethods::entityQuery($school)->findOrFail($entityId);
			$title = $entity->getName();
		} else {
			$entity = $this->newPaymentMethod($school);
			$title = $wizard->translate('Neue Zahlmethode');
		}

		return (new Wizard\Structure\Form($entity, 'payment_method_id', $title))
			->add('name', $wizard->translate('Name'), Wizard\Structure\Form::FIELD_INPUT, [
				'rules' => 'required'
			]);
	}

	protected function newPaymentMethod(\Ext_Thebing_School $school): \Ext_Thebing_Admin_Payment
	{
		$paymentMethod = new \Ext_Thebing_Admin_Payment();
		$paymentMethod->schools = [$school->id];
		$paymentMethod->position = BlockPaymentMethods::entityQuery($school)->max('position') + 1;
		return $paymentMethod;
	}

}