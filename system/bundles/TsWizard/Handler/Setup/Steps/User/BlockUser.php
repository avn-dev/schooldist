<?php

namespace TsWizard\Handler\Setup\Steps\User;

use Tc\Service\Wizard;
use Tc\Service\Wizard\Structure\AbstractElement;
use Tc\Service\Wizard\Structure\Step;

class BlockUser extends Wizard\Structure\Block
{
	public function getQueries(): array
	{
		$userIds = \Ext_Thebing_User::query()
			->pluck('id')
			->prepend(0);

		return [
			new Wizard\Structure\QueryParam('user_id', $userIds)
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