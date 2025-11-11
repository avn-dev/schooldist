<?php

namespace Office\Helper;

class VatHelper {

	protected $_aLastErrors;
	protected $_mLastResponse;
	
	/**
	 * @var \DragonBe\Vies\Vies 
	 */
	protected $oVies;

	protected $oLog;


	public function __construct() {

		$this->oVies = new \DragonBe\Vies\Vies();
		$this->oLog = \Log::getLogger('office_vat_check');
		
	}
		
	public function check($sCountryCode, $iNumber, $sCompany, $sCity, $sZip, $sStreet) {
		
		$this->_aLastErrors = null;
		$this->_mLastResponse = null;
		
		$bReturn = false;
		
		$sVatId = \Ext_Office_Config::get('vat_id');

		$bIsAlive = $this->oVies->getHeartBeat()->isAlive();

		if($bIsAlive !== true) {
			$this->oLog->error('Service unavailable');
			$this->_aLastErrors[] = 'SERVICE_UNAVAILABLE';
			return false;
		}
		
		$sVatCountryCode = substr($sVatId, 0, 2);
		$sVatNumber = substr($sVatId, 2);
		
		$this->oLog->addInfo('Check', [$sCountryCode, $iNumber, $sCompany, $sCity, $sZip, $sStreet]);
		
		try {

			$oVatResult = $this->oVies->validateVat(
				$sCountryCode,                 // Trader country code 
				$iNumber,         // Trader VAT ID
				$sVatCountryCode,                 // Requester country code 
				$sVatNumber,          // Requester VAT ID
				$sCompany,             // Trader name
				'',                 // Trader company type
				$sStreet, // Trader street address
				$sZip,               // Trader postcode
				$sCity         // Trader city
			);

			$this->oLog->addInfo('Result', [print_r($oVatResult, 1)]);

			$this->_mLastResponse = $oVatResult->toArray();

			if($oVatResult->isValid() === true) {
				
				$this->_aLastErrors[] = 'Die USt.-ID-Nr. ist valide.';
				$this->_aLastErrors[] = 'Bitte prüfen Sie die Angaben:';
				$this->_aLastErrors[] = 'Name: '.$oVatResult->getName();
				$this->_aLastErrors[] = 'Adresse: '.$oVatResult->getAddress();

				return true;
			} else {
				
				$this->_aLastErrors[] = 'Die USt.-ID-Nr. ist nicht valide.';
				
			}

		} catch (\Exception $ex) {

			$this->oLog->addInfo('Exception', [$ex]);
			
			$this->_aLastErrors[] = $ex->getMessage();
			
			return false;
			
		}

		return false;
	}
	
	public function getLastResponse() {
		
		return $this->_mLastResponse;
		
	}
	
	public function getLastErrors() {
		
		return $this->_aLastErrors;
		
	}

	
}
