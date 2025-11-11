<?php

namespace Communication\Handler\Wizards\EmailAccounts\Conditions;

use Communication\Handler\Wizards\EmailAccounts\Steps\Email\StepList;
use Illuminate\Http\Request;
use Tc\Service\Wizard;
use Tc\Service\Wizard\Structure\AbstractElement;

class HasAccess
{
	public function __construct(private Request $request) {}
	public function __invoke(Wizard $wizard, AbstractElement $element)
	{
		if ($element->isDisabled() || !$wizard->isIndexStep($element)) {
			return;
		}

		$accountId = (int)$this->request->get('account_id', null);

		if ($accountId <= 0) {
			$right = 'new';
		} else {
			$right = 'edit';
		}

		if (!$wizard->getAccess()->hasRight([StepList::ACCESS_RIGHT, $right])) {
			$element->disable(__METHOD__);
		}

	}
}