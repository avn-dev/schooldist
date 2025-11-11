<?php

namespace Communication\Handler\Wizards\EmailAccounts\Conditions;

use Illuminate\Http\Request;
use Tc\Service\Wizard;
use Tc\Service\Wizard\Structure\AbstractElement;

class HasOAuth2
{
	public function __construct(private Request $request) {}
	public function __invoke(Wizard $wizard, AbstractElement $element)
	{
		if ($element->isDisabled()) {
			return;
		}

		$accountId = (int)$this->request->get('account_id', null);
		$account = \Factory::executeStatic(\Ext_TC_Communication_EmailAccount::class, 'query')->findOrFail($accountId);

		$type = $parameters[0] ?? 'smtp';

		if ($account->{$type.'_auth'} !== 'oauth2') {
			$element->disable(__METHOD__);
		}

	}
}