<?php

namespace TsSalesForce\Service;

use TcSalesForce\ErrorHandler\ApiErrorHandler;
use TcSalesForce\Service\SalesForceApi;
use \System;
use \Ext_Thebing_Agency;

/**
 * Class Agency Service-Klasse um Agenturinformationen über die SalesForce Api zu übermitteln.
 *
 * @package TsSalesForce\Service
 */
class Agency extends SalesForceApi {

	/**
	 * SalesForce Api Key für die Agenturen
	 *
	 * @var string
	 */
	const IS_ACTIVE_KEY = 'ts_agency_sales_force_api';

	/**
	 * {@inheritdoc}
	 */
	public function transfer() {

		// API Login
		$bSuccess = $this->login();
		
		if(!$bSuccess) {
			throw new ApiErrorHandler('Your login informations are invalid!');
		}

		// Aktuelle Entity setzen um bei spezifische Fälle abzufangen
		$this->setEntity($this->oEntity);

		if(!$this->hasSalesForceId()) {

			// Objekt bei SalesForce erstellen
			$aResponseData = $this->create('Lead', $this->convertAgencyToArray());

			// Wenn der aktuelle Prozess durch einen Fehler beendet wurde, dann darf die Id von SalesForce nicht
			// eingetragen werden (da eh keine vorhanden ist)
			if($this->bStopCurrentProcess === false) {
				$this->saveSalesForceId($aResponseData);
			}

		} else {
			// Objekt bei SalesForce updaten
			$aResponseData = $this->update('Lead', $this->oEntity->salesforce_id, $this->convertAgencyToArray());
		}

		return $aResponseData;

	}

	/**
	 * Konvertiert das Objekt in ein Array um und nimmt nur bestimmte Daten in das
	 * Array auf.
	 *
	 * @return array
	 */
	private function convertAgencyToArray() {

		return [
			'FirstName' => $this->oEntity->ext_2,
			'LastName' => 'Dummy',
			'Company' => $this->oEntity->ext_1,
		];

	}

	/**
	 * Prüft ob die SalesForce Api Agenturdaten übermitteln soll, oder nicht.
	 *
	 * @return bool
	 */
	public static function isActive() {

		$bIsActive = parent::isActive();

		if($bIsActive) {
			$iActive = (int)System::d(self::IS_ACTIVE_KEY);

			if($iActive === 1) {
				return true;
			}

			return false;
		}

		return false;

	}

}