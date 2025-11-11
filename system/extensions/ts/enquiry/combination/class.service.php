<?php

abstract class Ext_TS_Enquiry_Combination_Service extends Ext_TS_Service_Abstract {	

	/**
	 * @var bool
	 */
	protected $_bSkipValidation = false;

	/**
	 * @var Ext_TS_Inquiry_Journey_Service[]
	 */
	protected $_aInquiryServices = [];

	public function __construct($iDataID = 0, $sTable = null) {

		// Hier setzen, damit KindKlassen $_aJoinedObjects füllen können
		$this->_aJoinedObjects['combination'] = [
			'class' => 'Ext_TS_Enquiry_Combination',
			'key' => 'combination_id',
		];

		parent::__construct($iDataID, $sTable);

	}

	/**
	 * @return Ext_TS_Enquiry_Combination
	 */
	public function getCombination() {
		return $this->getJoinedObject('combination');
	}

	/**
	 * @return Ext_TS_Enquiry
	 */
	public function getEnquiry() {
		return $this->getCombination()->getEnquiry();
	}

	/**
	 * {@inheritdoc}
	 */
	public function getInquiry() {
		return $this->getEnquiry();
	}

	/**
	 * @return string 
	 */
	public function getFrom() {
		$sFrom = '';
		if(isset($this->_aData['from'])) {
			$sFrom = $this->from;
		}
		return $sFrom;
	}

	/**
	 * @return string 
	 */
	public function getUntil() {
		$sUntil = '';
		if(isset($this->_aData['until'])) {
			$sUntil = $this->until;
		}
		return $sUntil;
	}

	/**
	 * Prüft, ob ein Leistungszeitraum vorhanden ist.
	 *
	 * @return boolean
	 */
	public function hasTimePeriod() {
		
		if(
			$this->getFrom() != '' &&
			$this->getUntil() != ''
		) {
			return true;
		}

		return false;

	}

	/**
	 * {@inheritdoc}
	 */
	public function validate($bThrowExceptions = false) {
		
		$aErrors = parent::validate($bThrowExceptions);

		// Prüfen, ob nach Leistungszeitraum validiert werden soll
		if($this->_bSkipValidation === false) {

			if($aErrors === true) {

				$aErrors = [];

				// Prüfen, ob Leistungszeitraum vorhanden ist
				if($this->hasTimePeriod() === false) {
					$sMassage = $this->_getErrorMessage();
					$aErrors[$this->_sTableAlias.'.from'][] = $sMassage;
				}

				if(empty($aErrors)) {
					$aErrors = true;
				}

			}

		}

		// Das Enddatum darf niemals vor dem Startdatum liegen, auch wenn der Leistungszeitraum nicht validiert werden soll
		if($aErrors === true) {

			$aErrors = [];

			if(empty($aErrors)) {
				$aErrors = $this->validateDates();
			}

			if(empty($aErrors)) {
				$aErrors = true;
			}

		}

		return $aErrors;

	}

	/**
	 * @return bool|mixed[]
	 */
	private function validateDates() {

		$aError = [];

		if($this->hasTimePeriod()) {

			$oDateFrom = new DateTime($this->getFrom());
			$oDateUntil = new DateTime($this->getUntil());

			if($oDateFrom > $oDateUntil) {
				$aError[$this->_sTableAlias.'.until'][] = L10N::t('Das Von-Datum sollte vor dem Bis-Datum liegen.');
			}

		}

		return $aError;

	}

	/**
	 * Ableiten um spezielle Fehlermeldung anzuzeigen.
	 *
	 * @return string
	 */
	protected function _getErrorMessage() {
		return '';
	}

	/**
	 * Beschreibender Text, wenn der Kurs als Special auf der Rechnung erscheint.
	 *
	 * @return string
	 */
	public function getSpecialInfo($iSchoolId, $sDisplayLanguage) {
		$oService = $this->getJourneyService();
		$sName = $oService->getSpecialInfo($iSchoolId, $sDisplayLanguage);
		return $sName;
	}

	/**
	 * Setzt das neue Inquiry Service Objekt nach dem Umwandeln.
	 *
	 * @param Ext_TS_Inquiry_Journey_Service $oInquiryService 
	 */
	public function addInquiryService(Ext_TS_Inquiry_Journey_Service $oInquiryService) {
		$this->_aInquiryServices[] = $oInquiryService;
	}

	/**
	 * @return Ext_TS_Inquiry_Journey_Service[]
	 */
	public function getInquiryServices() {
		return $this->_aInquiryServices;
	}

	/**
	 * @param Ext_TS_Inquiry_Journey|null $oJourney
	 * @return Ext_TS_Inquiry_Journey_Service
	 */
	abstract public function getJourneyService(Ext_TS_Inquiry_Journey $oJourney = null);

}
