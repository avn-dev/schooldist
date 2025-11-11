<?php

namespace TsWizard\Handler\Setup\Steps\PaymentMethod;

use Illuminate\Http\Request;
use Tc\Service\Wizard;
use Tc\Service\Wizard\Structure\AbstractElement;
use Tc\Service\Wizard\Structure\Step;
use TsWizard\Traits\SchoolElement;

class BlockPaymentMethodEntity extends Wizard\Structure\Block
{
	use SchoolElement;

	public function getQueries(): array
	{
		$school = $this->getSchool(app(Request::class));

		$entityIds = BlockPaymentMethods::entityQuery($school)
			->pluck('id')
			->prepend(0);

		return [
			new Wizard\Structure\QueryParam('payment_method_id', $entityIds)
		];
	}

	public function getNextStep(AbstractElement $after): ?Step
	{
		if (null === $element = $this->getNextElement($after)) {
			return $this->parent->getStep('list');
		}

		return $element;
	}
}