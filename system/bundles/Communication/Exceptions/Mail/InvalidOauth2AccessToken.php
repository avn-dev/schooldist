<?php

namespace Communication\Exceptions\Mail;

use Core\Interfaces\HumanReadable;
use Tc\Service\LanguageAbstract;

class InvalidOauth2AccessToken extends \RuntimeException implements HumanReadable
{
	public function __construct(
		private readonly \Ext_TC_Communication_EmailAccount $account,
		$code = 0,
		\Exception $previous = null
	) {
		parent::__construct(sprintf('Invalid or missing oauth2 access token [%d]', $account->id), $code, $previous);
	}

	public function getAccount(): \Ext_TC_Communication_EmailAccount
	{
		return $this->account;
	}

	public function getHumanReadableText($l10n): string
	{
		if ($l10n instanceof LanguageAbstract) {
			return sprintf(
				$l10n->translate('Der OAuth2-Authentifizierung für das E-Mail-Konto "%s" ist nicht verfügbar, bitte führen Sie diese aus.'),
				$this->account->email
			);
		}

		return $this->message;
	}
}