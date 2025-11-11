<?php

namespace TsWizard\Handler\Setup\Steps\Building\Floor;

use Illuminate\Http\Request;
use Tc\Service\Wizard;
use Tc\Service\Wizard\Structure\AbstractElement;
use Tc\Service\Wizard\Structure\Step;
use TsWizard\Traits\BuildingElement;

class BlockFloorEntity extends Wizard\Structure\Block
{
	use BuildingElement;

	public function getQueries(): array
	{
		$building = $this->getBuilding(app(Request::class));

		$entityIds = BlockFloors::entityQuery($building)
			->pluck('id')
			->prepend(0);

		return [
			new Wizard\Structure\QueryParam('floor_id', $entityIds)
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