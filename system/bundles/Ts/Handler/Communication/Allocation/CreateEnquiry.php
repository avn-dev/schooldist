<?php

namespace Ts\Handler\Communication\Allocation;

use Communication\Interfaces\MessageAllocationAction;
use Illuminate\Http\Request;
use Tc\Traits\Communication\Allocation\WithDialog;

/**
 * @deprecated
 */
class CreateEnquiry implements MessageAllocationAction {
	use WithDialog;

	public function isValid(\Ext_TC_Communication_Message $message): bool {

		// Nur eingehende Nachrichten können zu Anfragen umgewandelt werden
		if ($message->direction === 'in') {
			$relationsEntities = array_column($message->relations, 'relation');

			// E-Mail darf noch mit keiner Buchung/Anfrage verknüpft sein
			if (!in_array(\Ext_TS_Inquiry::class, $relationsEntities)) {
				return true;
			}
		}

		return false;
	}

	public function prepareDialog(\Ext_Gui2 $gui2, \Ext_Gui2_Dialog $dialog, \Ext_TC_Communication_Message $message): void {

		$dialog->height = 700;

		$addresses = $message->getAddresses('from');
		$address = reset($addresses);

		// Vorname und Nachname erahnen
		$firstname = $lastname = $address->name;
		if (strpos($address->name, ',') !== false) {
			// Template: Nachname, Vorname
			[$lastname, $firstname] = explode(',', $address->name);
		} else if (count($nameData = explode(' ', $address->name)) === 2) {
			// Template: Vorname Nachname
			[$firstname, $lastname] = $nameData;
		}

		$dialog->setElement($dialog->createRow($gui2->t('Vorname'), 'input', [
			'db_column' => 'firstname',
			'default_value' => trim($firstname),
			'required' => true,
			'no_save_data' => true
		]));

		$dialog->setElement($dialog->createRow($gui2->t('Nachname'), 'input', [
			'db_column' => 'lastname',
			'default_value' => trim($lastname),
			'required' => true,
			'no_save_data' => true
		]));

		$dialog->setElement($dialog->createRow($gui2->t('E-Mail'), 'input', [
			'db_column' => 'email',
			'default_value' => $address->address,
			'required' => true,
			'no_save_data' => true
		]));

	}

	public function save(\Ext_Gui2 $gui2, \Ext_TC_Communication_Message $message, Request $request): bool|array {

		$school = \Ext_Thebing_School::getSchoolFromSession();

		$saveData = $request->input('save', []);

		$inquiry = new \Ext_TS_Inquiry();
		$inquiry->type = \Ext_TS_Inquiry::TYPE_ENQUIRY;
		$inquiry->created = time();
		$inquiry->payment_method = 1;
		$inquiry->currency_id = $school->getCurrency();

		// Journey mit school_id generieren - muss da sein
		$journey = $inquiry->getJourney();
		$journey->school_id = $school->id;
		$journey->productline_id = $school->getProductLineId();
		$journey->type = \Ext_TS_Inquiry_Journey::TYPE_DUMMY;

		$customer = $inquiry->getCustomer();
		$customer->firstname = $saveData['firstname'];
		$customer->lastname = $saveData['lastname'];
		$customer->corresponding_language = $school->getLanguage();

		$email = $customer->getFirstEmailAddress(true);
		$email->email = $saveData['email'];
		$email->master = 1;

		if (is_array($errors = $inquiry->validate())) {
			$messages = [];
			foreach ($errors as $fieldKey => $fieldErrors) {
				foreach ($fieldErrors as $error) {
					$messages[] = $gui2->getDataObject()->getErrorMessage($error, $fieldKey);
				}
			}
			return $messages;
		}

		$numberHelper = new \Ext_Thebing_Customer_CustomerNumber($inquiry);
		$numberErrors = $numberHelper->saveCustomerNumber(true, false);

		if (!empty($numberErrors)) {
			return $numberErrors;
		}

		$inquiry->save();

		// Nachricht mit der Abfrage verknüpfen

		$relations = $message->relations;
		$relations[] = [
			'relation' => \Ext_TS_Inquiry::class,
			'relation_id' => $inquiry->getId()
		];
		$message->relations = $relations;
		$message->save();

		\Ext_Gui2_Index_Registry::insertRegistryTask($inquiry);

		return true;
	}

}
