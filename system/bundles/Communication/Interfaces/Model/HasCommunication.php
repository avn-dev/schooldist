<?php

namespace Communication\Interfaces\Model;

use Communication\Helper\Collections\AddressContactsCollection;
use Illuminate\Support\Collection;
use Tc\Service\LanguageAbstract;

interface HasCommunication
{

	//public static function getCommunicationChannels(LanguageAbstract $l10n): array;

	public function getCommunicationDefaultApplication(): string;

	public function getCommunicationLabel(LanguageAbstract $l10n): string;

	//public function getCommunicationContacts(string $channel, LanguageAbstract $l10n): AddressCollection;

	//public function getCommunicationAttachments(string $channel, LanguageAbstract $l10n): Collection;

	public function getCommunicationSubObject(): CommunicationSubObject;
}