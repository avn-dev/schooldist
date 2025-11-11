<?php

namespace Communication\Applications;

use Communication\Facades\Communication;
use Communication\Helper\Collections\AttachmentsCollection;
use Communication\Helper\Collections\AddressContactsCollection;
use Communication\Interfaces\Application;
use Illuminate\Support\Collection;
use Tc\Service\LanguageAbstract;

class GlobalApplication implements Application
{
	public static function getTitle(LanguageAbstract $l10n, string $application): string
	{
		return $l10n->translate('Global');
	}

	public static function getRecipientKeys(string $application): array
	{
		return [];
	}

	public function getChannels(LanguageAbstract $l10n): array
	{
		return [
			'mail' => [],
			'app' => ['new_message_disabled' => true],
			'sms' => ['new_message_disabled' => true],
			'notice' => ['new_message_disabled' => true]
		];
	}

	public function getRecipients(LanguageAbstract $l10n, Collection $models, string $channel): AddressContactsCollection
	{
		return new AddressContactsCollection();
	}

	public static function getFlags(): array
	{
		return Communication::getAllFlags()->toArray();
	}

	public function getAttachments(LanguageAbstract $l10n, Collection $models, string $channel, string $language = null): AttachmentsCollection
	{
		return new AttachmentsCollection();
	}
}