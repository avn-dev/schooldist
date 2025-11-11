<?php

namespace Communication\Handler\Wizards\EmailAccounts\Steps\Email;

use Tc\Service\Wizard;
use Tc\Service\Wizard\Structure\AbstractElement;
use Tc\Service\Wizard\Structure\Step;

class BlockEmailAccountEntity extends Wizard\Structure\Block
{
	public function getQueries(): array
	{
		$entityIds = \Factory::executeStatic(\Ext_TC_Communication_EmailAccount::class, 'query')
			->pluck('id');

		// TODO $wizard->getAccess();
		if (\Access_Backend::getInstance()->hasRight([StepList::ACCESS_RIGHT, 'new'])) {
			$entityIds->prepend(0);
		}

		return [
			new Wizard\Structure\QueryParam('account_id', $entityIds)
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