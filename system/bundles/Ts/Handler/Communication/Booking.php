<?php

namespace Ts\Handler\Communication;

/**
 * @deprecated
 */
class Booking extends \Ext_Thebing_Communication {

	protected $_sObject = 'Ext_TS_Inquiry';

	protected $_aDialogTabOptions = array(
		'show_email' => false,
		'show_app' => true,
		'show_sms' => true,
		'show_notices' => true,
		'show_history' => true,
		'show_placeholders' => false
	);
	
	/**
	 * {@inheritdoc}
	 */
	public function addStaticRecipients(\Ext_TC_Communication_Tab_TabArea $oTabArea, &$recipients) {
		
		if($oTabArea->getParentTab()->getType() !== 'app') {
			return;
		}
		
		$aSelectedIds = $this->getSelectedIds();
		$sObjectClass = $this->getObjectClassName();

		foreach($aSelectedIds as $iSelectedId) {

			$oObject = \Ext_TC_Factory::getInstance($sObjectClass, $iSelectedId);
			/* @var $oObject \Ext_TS_Inquiry */

			$oContact = $oObject->getTraveller();

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
	
	/**
	 * @todo Jede Versandart braucht seinen eigenen Service, der dann auch das Handling der Fehlermeldungen übernimmt
	 * @param array $aEmail s.o.
	 * @return bool|array
	 */
	protected function _sendApp($aEmail) {

		$aRecipients = $aEmail['recipients'];

		foreach($aRecipients as $sKey => $aRecipientsReal) {
			foreach($aRecipientsReal as $aRecipient) {

				$inquiry = \Ext_TS_Inquiry::getInstance($aRecipient['selected_id']);
				$contact = \Factory::getInstance($aRecipient['object'], $aRecipient['object_id']);

				$messengerService = \TsStudentApp\Service\MessengerService::getInstance($contact, $inquiry);
				
				try {
					$success = $messengerService->sendMessageToDevices($inquiry->getSchool(), $aEmail['subject'], $aEmail['content']);

					if($success !== true) {
						$errors = $messengerService->getErrors();

						foreach($errors as &$error) {
							$error = sprintf(self::t('Die Nachricht konnte nicht an "%s" versendet werden. Die Verbindung zur App konnte nicht hergestellt werden.'), $aRecipient['name']).' ('.$error.')';
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
