<?php

namespace TsWizard\Handler\Setup\Steps\Classroom;

use Illuminate\Http\Request;
use Tc\Service\Wizard;
use Tc\Service\Wizard\Structure\AbstractElement;
use Tc\Service\Wizard\Structure\Step;
use TsWizard\Traits\SchoolElement;

class BlockClassroomEntity extends Wizard\Structure\Block
{
	use SchoolElement;

	public function getQueries(): array
	{
		$school = $this->getSchool(app(Request::class));

		$entityIds = BlockClassrooms::entityQuery($school)
			->pluck('id')
			->prepend(0);

		return [
			new Wizard\Structure\QueryParam('classroom_id', $entityIds)
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