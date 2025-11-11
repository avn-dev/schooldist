<?php

namespace TsWizard\Handler\Setup\Steps\School;

use Tc\Service\Wizard;
use Tc\Service\Wizard\Structure\AbstractElement;
use Tc\Service\Wizard\Structure\Step;

class BlockSchool extends Wizard\Structure\Block
{
	public function getQueries(): array
	{
		$schoolIds = \Ext_Thebing_School::query()
			->pluck('id')
			->prepend(0);

		return [
			new Wizard\Structure\QueryParam('school_id', $schoolIds)
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