<?php

namespace Office\Service;

include_once(\Util::getDocumentRoot()."system/extensions/office/office.inc.php");
include_once(\Util::getDocumentRoot()."system/extensions/office/office.dao.inc.php");

class Reminder {
	
	public function execute() {
		
		$objOffice = new \classExtension_Office;
        $aConfigData = $objOffice->getConfigData();
        $objOfficeDao = new \classExtensionDao_Office($aConfigData);
		
		$aReport = array();
		
		$oLog = \Log::getLogger('office');
		
		/**
		 * send payment reminder
		 */
		$aInvoices = $objOfficeDao->getReceivables();

		$paymentReminderLevel = [
			'paymentreminder' => [
				'text' => 'paymentreminder',
				'timestamp' => 'reminder_date',
				'level' => 1,
				'invoices' => []
			],
			'paymentreminder2' => [
				'text' => 'paymentreminder2',
				'timestamp' => 'reminder_date2',
				'level' => 2,
				'invoices' => []
			]
		];
		
		foreach((array)$aInvoices as $aInvoice) {
			
			if(
				!empty($aInvoice['reminder_date']) &&
				$aInvoice['reminder_date'] <= time() &&
				$aInvoice['dunning_level'] == 0 &&
				$aInvoice['due'] = 1 &&
				$aInvoice['receivable'] > 0
			) {
				$paymentReminderLevel['paymentreminder']['invoices'][] = $aInvoice;
			} elseif(
				!empty($aInvoice['reminder_date2']) &&
				$aInvoice['reminder_date2'] <= time() &&
				$aInvoice['dunning_level'] == 1 &&
				$aInvoice['due'] = 1 &&
				$aInvoice['receivable'] > 0
			) {
				$paymentReminderLevel['paymentreminder2']['invoices'][] = $aInvoice;
			}
			
		}

		$oLog->addInfo('Reminder data', $paymentReminderLevel);
		
		
//		$oEmail = new \Office\Service\Email;
//		$oEmail->setSubject('Payment reminder report');
//		$oEmail->setText(print_r($paymentReminderLevel,1));
//		$oEmail->send(['m.koopmann@fidelo.com', 'accounting@fidelo.com']);
//		
//		return;
		
		foreach($paymentReminderLevel as $levelData) {
			
			foreach($levelData['invoices'] as $aInvoice) {

				$oDocument = new \Ext_Office_Document($aInvoice['id']);

				$oDocument->getFilePath();

				$aMail = $oDocument->prepareEmail($levelData['text']);

				$bSuccess = $oDocument->sendFile($aMail);

				if($bSuccess) {

					// set dunning level
					$oDocument->dunning_level = $levelData['level'];
					$oDocument->save();

					$aLog = array(
						'id'			=> 0,
						'customer_id'	=> (int)$oDocument->customer_id,
						'contact_id'	=> (int)$oDocument->contact_person_id,
						'editor_id'		=> 0,
						'document_id'	=> $oDocument->id,
						'topic'			=> $oDocument->type,
						'state'			=> $oDocument->state,
						'subject' 		=> 'Zahlungserinnerung versendet'
					);
					$objOfficeDao->manageProtocols($aLog);

					$aReport['dunnings']++;

				}

			}
			
		}

		__pout($aReport);

		$oLog->addInfo('Reminder report', $aReport);
		
	}

}
