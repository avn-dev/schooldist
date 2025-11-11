<?php

namespace Ts\Hook;

use Illuminate\Support\Arr;

class MailSpoolAppSend
{
	public function run(\Ext_TC_Communication_Message $message, &$success, &$errors): void
	{
		$travellerId = Arr::first($message->getAddresses('to'))->address;
		$inquiryId = Arr::first($message->relations, fn ($relation) => $relation['relation'] === \Ext_TS_Inquiry::class)['relation_id'];

		$inquiry = \Ext_TS_Inquiry::getInstance($inquiryId);
		$contact = \Ext_TS_Inquiry_Contact_Traveller::getInstance($travellerId);

		$messengerService = \TsStudentApp\Service\MessengerService::getInstance($contact, $inquiry);

		try {
			$success = $messengerService->sendMessageToDevices($inquiry->getSchool(), $message->subject, $message->content);

			if($success !== true) {
				$errors = $messengerService->getErrors();

				foreach($errors as &$error) {
					$error = sprintf(\Ext_TC_Communication::t('Die Nachricht konnte nicht an "%s" versendet werden. Die Verbindung zur App konnte nicht hergestellt werden.'), $aRecipient['name']).' ('.$error.')';
				}
			}

		} catch(\RuntimeException $e) {

			$errors = [\Ext_TC_Communication::t('Die Nachricht konnte nicht versendet werden. Bitte versuchen Sie es später erneut.')];

		}
	}
}