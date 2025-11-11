<?php

namespace Communication\Handler\Wizards\EmailAccounts\Steps\Email;

use Tc\Service\Wizard;
use Tc\Service\Wizard\Structure\Step;

class BlockEmailAccounts extends Wizard\Structure\Block
{
	public function getFirstStep(): ?Step
	{
		if (\Factory::executeStatic(\Ext_TC_Communication_EmailAccount::class, 'query')->pluck('id')->isEmpty()) {
			// Wenn es noch keine E-Mail-Konten gibt direkt auf das Formular weiterleiten, um ein neues Konto anzulegen
			return $this->get('form')->getFirstStep();
		}

		return parent::getFirstStep();
	}
}