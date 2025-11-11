<?php

namespace Ts\Communication\Flag;

use Communication\Interfaces\Flag;
use Communication\Interfaces\Model\HasCommunication;
use Tc\Service\LanguageAbstract;

class PaymentReminder implements Flag
{
	public static function getTitle(LanguageAbstract $l10n): string
	{
		return $l10n->translate('Zahlungserinnerung - Kunde');
	}

	public static function getRecipientKeys(): array
	{
		return ['customer', 'agency'];
	}

	public function validate(bool $used, LanguageAbstract $l10n, HasCommunication $model, \Ext_TC_Communication_Message $message, bool $finalOutput, array $confirmedErrors): array
	{
		return [];
	}

	public function save(\Ext_TC_Communication_Message $message): void
	{
		if (!$message->exist()) {
			return;
		}

		$inquiries = $message->searchRelations(\Ext_TS_Inquiry::class);

		foreach ($inquiries as $inquiry) {
			/* @var \Ext_TS_Inquiry $inquiry */
			$reminders = $inquiry->payment_reminders;
			$reminders[] = ['log_id' => $message->id];
			$inquiry->payment_reminders = $reminders;
			$inquiry->save();

			\Ext_Gui2_Index_Stack::add('ts_inquiry', $inquiry->id, 0);
		}
	}
}