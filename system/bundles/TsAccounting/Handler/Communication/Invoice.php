<?php

namespace TsAccounting\Handler\Communication;

/**
 * @deprecated
 */
class Invoice extends \Ext_Thebing_Communication {

	protected $_sObject = \Ext_Thebing_Inquiry_Document::class;

	protected $_aDialogTabOptions = [
		'show_email' => true,
		'show_app' => true,
		'show_sms' => false,
		'show_notices' => false,
		'show_history' => true,
		'show_placeholders' => false
	];

	public function addStaticRecipients(\Ext_TC_Communication_Tab_TabArea $oTabArea, &$recipients) {

		if($oTabArea->getParentTab()->getType() !== 'app') {
			return;
		}

		$aSelectedIds = $this->getSelectedIds();
		$sObjectClass = $this->getObjectClassName();

		foreach($aSelectedIds as $iSelectedId) {

			$oObject = \Ext_TC_Factory::getInstance($sObjectClass, $iSelectedId);
			/* @var $oObject \Ext_Thebing_Inquiry_Document */

			$oInquiry = $oObject->getInquiry();
			$oContact = $oInquiry->getTraveller();

			if(
				$oContact instanceof \Ext_TS_Inquiry_Contact_Abstract &&
				$oContact->hasStudentApp()
			) {
				$recipients['to'][] = array(
					'name' => $oContact->getName(),
					'address' => $oContact->id,
					'object_id' => $oContact->id,
					'object' => get_class($oContact),
					'selected_id' => $oObject->id
				);
			}

		}
	}

	protected function addApplicationRelations($email, array &$relations) {

		/* @var \Ext_Thebing_Inquiry_Document $object */
		$object = $email['selected_object'];

		if (!$object) {
			return;
		}

		$inquiry = $object->getInquiry();

		$relations[] = ['relation' => $inquiry::class, 'relation_id' => $inquiry->id];

		if ($email['object'] === \Ext_Thebing_Agency_Contact::class) {
			$relations[] = ['relation' => $email['object'], 'relation_id' => $email['object_id']];
			$object = \Ext_Thebing_Agency_Contact::getInstance($email['object_id']);
			$agency = $object->getParentObject();
			if ($agency) {
				$relations[] = ['relation' => $agency::class, 'relation_id' => $agency->id];
			}
		} else if ($email['object'] === \TsSponsoring\Entity\Sponsor\Contact::class) {
			$relations[] = ['relation' => $email['object'], 'relation_id' => $email['object_id']];
			$object = \TsSponsoring\Entity\Sponsor\Contact::getInstance($email['object_id']);
			$sponsor = $object->getSponsor();
			if ($sponsor) {
				$relations[] = ['relation' => $sponsor::class, 'relation_id' => $sponsor->id];
			}
		}

	}

	/**
	 * @todo Jede Versandart braucht seinen eigenen Service, der dann auch das Handling der Fehlermeldungen übernimmt
	 * @param array $aEmail s.o.
	 * @return bool|array
	 */
	protected function _sendApp($aEmail) {

		$recipients = $aEmail['recipients'];

		foreach($recipients as $recipientsReal) {
			foreach($recipientsReal as $recipient) {

				$inquiry = \Ext_TS_Inquiry::getInstance($recipient['selected_id']);
				$contact = \Factory::getInstance($recipient['object'], $recipient['object_id']);

				$messengerService = \TsStudentApp\Service\MessengerService::getInstance($contact, $inquiry);

				try {
					$success = $messengerService->sendMessageToDevices($inquiry->getSchool(), $aEmail['subject'], $aEmail['content']);

					if($success !== true) {
						$errors = $messengerService->getErrors();

						foreach($errors as &$error) {
							$error = sprintf(self::t('Die Nachricht konnte nicht an "%s" versendet werden. Die Verbindung zur App konnte nicht hergestellt werden.'), $recipient['name']).' ('.$error.')';
						}
						return $errors;
					}

				} catch(\RuntimeException $e) {

					return [self::t('Die Nachricht konnte nicht versendet werden. Bitte versuchen Sie es später erneut.')];

				}

			}
		}

		return true;

	}

}
