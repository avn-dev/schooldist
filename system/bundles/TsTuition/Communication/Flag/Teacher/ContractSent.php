<?php

namespace TsTuition\Communication\Flag\Teacher;

use Communication\Interfaces\Flag;
use Communication\Interfaces\Model\HasCommunication;
use Tc\Service\LanguageAbstract;

class ContractSent implements Flag
{
	public function __construct(
		private \Access_Backend $access
	){}

	public static function getTitle(LanguageAbstract $l10n): string
	{
		return $l10n->translate('Vertrag versendet');
	}

	public static function getRecipientKeys(): array
	{
		return ['contract_partner'];
	}

	public function validate(bool $used, LanguageAbstract $l10n, HasCommunication $model, \Ext_TC_Communication_Message $message, bool $finalOutput, array $confirmedErrors): array
	{
		return [];
	}

	public function save(\Ext_TC_Communication_Message $message): void
	{
		$contractVersions = $message->searchRelations(\Ext_Thebing_Contract_Version::class);

		foreach ($contractVersions as $contractVersion) {
			$contractVersion->sent = time();
			if ($this->access->checkValidAccess()) {
				$contractVersion->sent_by = (int)$this->access->getUser()->id;
			}

			$contractVersion->save(false);

			$contract = $contractVersion->getContract();
			$contract->last_sent_version_id = $contractVersion->id;
			$contract->save();
		}
	}
}