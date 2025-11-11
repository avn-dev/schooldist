<?php

namespace TsWizard\Handler\Setup\Conditions;

use Illuminate\Http\Request;
use Tc\Service\Wizard;
use Tc\Service\Wizard\Structure\AbstractElement;

class ExistingEmailAccount
{
	public function __construct(Wizard $wizard, private Request $request) {}

	public function __invoke(AbstractElement $element)
	{
		if (null === $accountId = $this->request->get('account_id', null)) {
			$element->disable();
		}

		if ((int)$accountId === 0 || \Ext_TC_Communication_EmailAccount::query()->find($accountId) === null) {
			$element->disable();
		}
	}
}