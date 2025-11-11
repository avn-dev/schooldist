<?php

namespace Communication\Interfaces;

use Communication\Helper\Collections\AttachmentsCollection;
use Communication\Helper\Collections\AddressContactsCollection;
use Illuminate\Support\Collection;
use Tc\Service\LanguageAbstract;

interface Application
{
	public static function getTitle(LanguageAbstract $l10n, string $application): string;

	public static function getRecipientKeys(string $application): array;

	public static function getFlags(): array;

	public function getChannels(LanguageAbstract $l10n): array;

	public function getRecipients(LanguageAbstract $l10n, Collection $models, string $channel): AddressContactsCollection;

	public function getAttachments(LanguageAbstract $l10n, Collection $models, string $channel, string $language = null): AttachmentsCollection;

}