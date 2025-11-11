<?php

namespace TsWizard\Handler\Setup\Steps\Building;

use Illuminate\Http\Request;
use Tc\Service\Wizard;
use Tc\Service\Wizard\Structure\AbstractElement;
use Tc\Service\Wizard\Structure\Step;
use TsWizard\Traits\SchoolElement;

class BlockBuildingEntity extends Wizard\Structure\Block
{
	use SchoolElement;

	public function getQueries(): array
	{
		$school = $this->getSchool(app(Request::class));

		$entityIds = BlockBuildings::entityQuery($school)
			->pluck('id')
			->prepend(0);

		return [
			new Wizard\Structure\QueryParam('building_id', $entityIds)
		];
	}

	public function getNextStep(AbstractElement $after): ?Step
	{
		if (null === $element = $this->getNextElement($after)) {
			return $this->parent->getStep('list');
		}

		// Etagen
		if ($element instanceof Wizard\Structure\Block) {
			return $element->getFirstStep();
		}

		return $element;
	}
}