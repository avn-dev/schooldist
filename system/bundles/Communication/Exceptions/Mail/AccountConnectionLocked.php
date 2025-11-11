<?php

namespace Communication\Exceptions\Mail;

use Core\Interfaces\HumanReadable;
use Tc\Service\LanguageAbstract;

class AccountConnectionLocked extends \RuntimeException implements HumanReadable
{
	public function __construct(
		private readonly \Ext_TC_Communication_EmailAccount $account,
		$code = 0,
		\Exception $previous = null
	) {
		parent::__construct('Account connections limit exceeded, please try again in a few seconds', $code, $previous);
	}

	public function getAccount(): \Ext_TC_Communication_EmailAccount
	{
		return $this->account;
	}

	public function getHumanReadableText($l10n): string
	{
		if ($l10n instanceof LanguageAbstract) {
			return sprintf(
				$l10n->translate('Für das E-Mail-Konto "%s" wurde die maximale Anzahl an Kontoverbindungen überschritten. Bitte versuchen Sie es in einigen Sekunden erneut.'),
				$this->account->email
			);
		}

		return $this->message;
	}
}