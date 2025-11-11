<?php

namespace TsWizard\Handler\Setup\Steps\TeachingUnit;

use Illuminate\Http\Request;
use Tc\Service\Wizard;
use Tc\Service\Wizard\Structure\AbstractElement;
use Tc\Service\Wizard\Structure\Step;
use TsWizard\Traits\SchoolElement;

class BlockTeachingUnitEntity extends Wizard\Structure\Block
{
	use SchoolElement;

	public function getQueries(): array
	{
		$school = $this->getSchool(app(Request::class));

		$entityIds = BlockTeachingUnits::entityQuery($school)
			->pluck('id')
			->prepend(0);

		return [
			new Wizard\Structure\QueryParam('teaching_unit_id', $entityIds)
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