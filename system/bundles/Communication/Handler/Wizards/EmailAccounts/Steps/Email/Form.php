<?php

namespace Communication\Handler\Wizards\EmailAccounts\Steps\Email;

use Illuminate\Support\MessageBag;
use Tc\Service\Wizard;
use Tc\Service\Wizard\Structure\Form as BasicForm;

class Form extends BasicForm
{
	protected function toMessageBag(Wizard $wizard, array $errorData): MessageBag
	{
		$messages = [];
		if (isset($errorData['SMTP_FAILED'])) {
			$messages['SMTP_FAILED'] = $wizard->translate('Sie SMTP-Verbindung konnte nicht hergestellt werden.');
			unset($errorData['SMTP_FAILED']);
		}

		if (isset($errorData['IMAP_FAILED'])) {
			$messages['IMAP_FAILED'] = $wizard->translate('Sie IMAP-Verbindung konnte nicht hergestellt werden.');
			unset($errorData['IMAP_FAILED']);
		}

		$messageBag = parent::toMessageBag($wizard, $errorData);
		foreach ($messages as $field => $message) {
			$messageBag->add($field, $message);
		}

		return $messageBag;
	}

	protected function getErrorKeyMessages(Wizard $wizard): array
	{
		return [
			'UNKNOWN_OAUTH2_PROVIDER' => $wizard->translate('Für den eingetragenen Mailserver konnte kein OAuth2-Anbieter gefunden werden. Bitte kontaktieren Sie den Support.')
		];
	}

}