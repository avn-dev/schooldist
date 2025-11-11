<?php

namespace Communication\Handler\Wizards\EmailAccounts\Conditions;

use Illuminate\Http\Request;
use Tc\Service\Wizard;
use Tc\Service\Wizard\Structure\AbstractElement;

class ExistingEmailAccount
{
	public function __construct(private Request $request) {}

	public function __invoke(Wizard $wizard, AbstractElement $element)
	{
		if (null === $accountId = $this->request->get('account_id', null)) {
			$element->disable(__METHOD__);
		}

		if ((int)$accountId === 0 || \Factory::executeStatic(\Ext_TC_Communication_EmailAccount::class, 'query')->find($accountId) === null) {
			$element->disable(__METHOD__);
		}
	}
}