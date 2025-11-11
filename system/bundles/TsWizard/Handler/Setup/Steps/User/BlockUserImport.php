<?php

namespace TsWizard\Handler\Setup\Steps\User;

use Tc\Service\Wizard;
use Tc\Service\Wizard\Structure\AbstractElement;
use Tc\Service\Wizard\Structure\Step;

class BlockUserImport extends Wizard\Structure\Block
{
	public function getNextStep(AbstractElement $after): ?Step
	{
		if (null === $element = $this->getNextElement($after)) {
			return $this->parent->getStep('list');
		}

		return $element;
	}
}