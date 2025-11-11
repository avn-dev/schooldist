<?php

namespace TsSalesForce\Service;

use TcSalesForce\ErrorHandler\ApiErrorHandler;
use TcSalesForce\Service\SalesForceApi;
use \System;
use \Ext_TS_Inquiry;

/**
 * Class Inquiry Service-Klasse um Buchungsinformationen über die SalesForce Api zu übermitteln.
 *
 * @property Ext_TS_Inquiry $oEntity
 * 
 * @package TsSalesForce\Service
 */
class Inquiry extends SalesForceApi {

	/**
	 * SalesForce Api Key für die Buchungsdaten
	 *
	 * @var string
	 */
	const IS_ACTIVE_KEY = 'ts_inquiry_sales_force_api';

	/**
	 * {@inheritdoc}
	 */
	public function transfer() {

		// API Login
		$bSuccess = $this->login();

		if (!$bSuccess) {
			throw new ApiErrorHandler('Your login informations are invalid!');
		}

		// Aktuelle Entity setzen um bei spezifische Fälle abzufangen
		$this->setEntity($this->oEntity);

		if(!$this->hasSalesForceId()) {
			// Objekt bei SalesForce erstellen
			$aResponseData = $this->create('Lead', $this->convertInquiryDataAsArray());

			// Wenn der aktuelle Prozess durch einen Fehler beendet wurde, dann darf die Id von SalesForce nicht
			// eingetragen werden (da eh keine vorhanden ist)
			if($this->bStopCurrentProcess === false) {
				$this->saveSalesForceId($aResponseData);
			}

		} else {
			// Objekt bei SalesForce updaten
			$aResponseData = $this->update('Lead', $this->oEntity->salesforce_id, $this->convertInquiryDataAsArray());
		}

		return $aResponseData;

	}

	/**
	 * Konvertiert das Inquiry-Objekt zu einem Array
	 * Anmerkung: Nur die Daten stehen im Array die zu SalesForce übermittelt werden sollen.
	 *
	 * @return array
	 */
	private function convertInquiryDataAsArray() {

		$oTraveller = $this->oEntity->getCustomer();

		return [
			'FirstName' => $oTraveller->firstname,
			'LastName' => $oTraveller->lastname,
			'Company' => 'Dummy',
		];

	}

	/**
	 * Prüft ob die SalesForce Api Buchungsdaten übermitteln soll, oder nicht.
	 *
	 * @return bool
	 */
	public static function isActive() {

		$bIsActive = parent::isActive();

		if($bIsActive) {
			$iActive = (int)\System::d(self::IS_ACTIVE_KEY);

			if ($iActive === 1) {
				return true;
			}

			return false;
		}

		return false;

	}

}