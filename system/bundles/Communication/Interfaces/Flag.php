<?php

namespace Communication\Interfaces;

use Communication\Interfaces\Model\HasCommunication;
use Tc\Service\LanguageAbstract;

interface Flag
{
	public static function getTitle(LanguageAbstract $l10n): string;

	public static function getRecipientKeys(): array;

	public function validate(bool $used, LanguageAbstract $l10n, HasCommunication $model, \Ext_TC_Communication_Message $message, bool $finalOutput, array $confirmedErrors): array;

	public function save(\Ext_TC_Communication_Message $message): void;

}