<?php

/**
 * @property integer $insurance_id
 * @property integer $weeks
 * @property mixed $from
 * @property mixed $until
 */
class Ext_TS_Enquiry_Combination_Insurance extends Ext_TS_Enquiry_Combination_Service implements Ext_TS_Service_Interface_Insurance {

	use Ts\Traits\LineItems\Insurance;
	
	protected $_sTable = 'ts_enquiries_combinations_insurances';

	protected $_sTableAlias = 'ts_eci';

	protected $sInfoTemplateType = 'insurance';
	
	protected $_aJoinedObjects = array(
		'insurance' => array(
			'class' => 'Ext_Thebing_Insurance',
			'key'	=> 'insurance_id'	
		)
	);

	protected $_aFormat = array(
		'insurance_id' => array(
			'validate' => 'INT_POSITIVE'
		),
		'from' => array(
			'validate' => 'DATE'
		),
		'until' => array(
			'validate' => 'DATE'
		),
		'weeks' => array(
			'validate' => 'INT'
		)
	);

	/**
	 * @return Ext_Thebing_Insurance
	 */
	public function getInsurance() {
		return $this->getJoinedObject('insurance');
	}

	/**
	 *
	 * @param int $iSchoolId
	 * @param string $sLanguage
	 * @param array $aData
	 * @return string 
	 */
	public function getInfo($iSchoolId, $sLanguage, $aData=null)
	{
		$oJourneyInsurance = $this->getJourneyService();
		$oJourneyInsurance->setInquiry($this->getEnquiry());

		// Hier muss $aData mit $this->id gesucht werden, da das in der Ext_TS_Inquiry_Journey_Insurance ansonsten super funktioniert
		if($aData === null) {
			$aInsurances = $this->getEnquiry()->getInsurancesWithPriceData($sLanguage);
			foreach($aInsurances as $aInsuranceData) {
				if($this->id == $aInsuranceData['id']) {
					$aData = $aInsuranceData;
					break;
				}
			}

			if($aData === null) {
				throw new RuntimeException('Couldn\'t find matching insurance');
			}
		}

		$sInfo = $oJourneyInsurance->getInfo($iSchoolId, $sLanguage, $aData);

		return $sInfo;
	}

	/**
	 * @param Ext_TS_Inquiry_Journey $oJourney
	 * @return Ext_TS_Inquiry_Journey_Insurance
	 */
	public function getJourneyService(Ext_TS_Inquiry_Journey $oJourney = null) {

		if($oJourney instanceof Ext_TS_Inquiry_Journey) {
			$oInsurance = $oJourney->getJoinedObjectChild('insurances');
		} else {
			// Alten Müll am Funktionieren erhalten
			$oInsurance = new Ext_TS_Inquiry_Journey_Insurance();
		}

		$oInsurance->insurance_id			= $this->insurance_id;
		$oInsurance->from					= $this->from;
		$oInsurance->until					= $this->until;
		$oInsurance->visible				= 1;	
		
		return $oInsurance;
	}
	
	/**
	 * see parent
	 * @param boolean $bThrowExceptions
	 * @return boolean 
	 */
	public function validate($bThrowExceptions = false) {
		
		$oInsurance = $this->getInsurance();
		
		// bei Wochenversicherung muss nicht auf Leistungszeitraum geprüft werden
		if($oInsurance->isWeekInsurance()) {
			$this->_bSkipValidation = true;
		}
		
		$aErrors = parent::validate($bThrowExceptions);
		
		return $aErrors;
	}

	/**
	 * Gibt die Fehlermeldung für Versicherungen zurück, wenn kein Leistungszeitraum gebucht wurde
	 * @return string
	 */
	protected function _getErrorMessage() {
		$oInsurance = $this->getInstance();
		$sMessage = sprintf(Ext_Thebing_L10N::t('Sie haben für die Versicherung "%s" keinen Leistungszeitraum angegeben'), $oInsurance->getName());
		return $sMessage;
	}		
	
	public function getInsuranceName($sLang = 'en')
	{
		$oInsurance = $this->getInsurance();

		return $oInsurance->getName($sLang);
	}

	/**
	 * @return Ext_Thebing_Insurances_Provider
	 */
	public function getInsuranceProvider() {
		return $this->getInsurance()->getProvider();
	}

	/**
	 * @deprecated
	 * @return null|int Timestamp
	 */
	public function getInsuranceStart() {

		$iFrom = null;

		if(WDDate::isDate($this->from, WDDate::DB_DATE)) {
			$oDate = new WDDate($this->from, WDDate::DB_DATE);
			$iFrom = $oDate->get(WDDate::TIMESTAMP);
		}

		return $iFrom;

	}

	/**
	 * @deprecated
	 * @return null|int Timestamp
	 */
	public function getInsuranceEnd() {

		$iUntil = null;

		if(WDDate::isDate($this->until, WDDate::DB_DATE)) {
			$oDate = new WDDate($this->until, WDDate::DB_DATE);
			$iUntil = $oDate->get(WDDate::TIMESTAMP);
		}

		return $iUntil;

	}

}
