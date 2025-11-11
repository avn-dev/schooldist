<?php

namespace TsCompany\Communication\Flag;

use Communication\Interfaces\Flag;
use Communication\Interfaces\Model\HasCommunication;
use Tc\Service\LanguageAbstract;

class JobOpportunityAllocated implements Flag
{

	public static function getTitle(LanguageAbstract $l10n): string
	{
		return $l10n->translate('Arbeitsangebot zugewiesen');
	}

	public static function getRecipientKeys(): array
	{
		return ['company'];
	}

	public function validate(bool $used, LanguageAbstract $l10n, HasCommunication $model, \Ext_TC_Communication_Message $message, bool $finalOutput, array $confirmedErrors): array
	{
		return [];
	}

	public function save(\Ext_TC_Communication_Message $message): void
	{

	}
}