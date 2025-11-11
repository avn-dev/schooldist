<?php

/**
 * @property integer $transfer_type
 * @property string $start
 * @property string $end
 * @property mixed $transfer_date
 * @property mixed $transfer_time
 * @property string $airline
 * @property string $flightnumber
 * @property mixed $pickup
 * @property string $comment
 */
class Ext_TS_Enquiry_Combination_Transfer extends Ext_TS_Enquiry_Combination_Service implements Ext_TS_Service_Interface_Transfer {

	protected $_sTable = 'ts_enquiries_combinations_transfers';

	protected $_sTableAlias = 'ts_ect';
	
	protected $sInfoTemplateType = 'transfer';

	protected $_aFormat = array(
		'combination_id' => array(
			'validate' => 'INT_POSITIVE'
		),
		'transfer_type' => array(
			'validate' => 'INT'
		),
		'start' => array(
			'validate' => 'INT'
		),
		'end' => array(
			'validate' => 'INT'
		),
		'transfer_date' => array(
			'validate' => 'DATE'
		),
		'start_additional' => array(
			'validate' => 'INT'
		),
		'end_additional' => array(
			'validate' => 'INT'
		),
		'pickup' => array(
			'validate' => 'TIME'
		)
	);

	/**
	 * {@inheritdoc}
	 */
	public function __set($sName, $mValue) {

		switch($sName) {

			case 'start':
			case 'end':
				// Hier muss die ID gesplittet werden da es mehrere Anreise/Abreise Ziehle gibt (Schule/Unterk./dynamische)
				$aTemp = explode('_', $mValue);
				parent::__set($sName, (int)$aTemp[1]);
				$sTypeName = $sName.'_type';
				parent::__set($sTypeName, $aTemp[0]);
				return;

		}

		parent::__set($sName, $mValue);

	}

	/**
	 * {@inheritdoc}
	 */
	public function __get($sName){

		Ext_Gui2_Index_Registry::set($this);

		switch($sName) {

			case 'start':
			case 'end':
				$sTypeName = $sName.'_type';
				$sValue = parent::__get($sTypeName);
				if(
					!is_numeric($sValue) &&
					!empty($sValue)
				) {
					$sValue .= '_'.parent::__get($sName);
				}
				return $sValue;

		}

		return parent::__get($sName);

	}

	/**
	 * @param mixed $oCalendarFormat
	 * @param string $sView
	 * @param string $sLang
	 * @return string
	 */
	public function getName($oCalendarFormat = null, $sView = 1, $mLang = '') {
		$oJourneyTransfer = $this->getJourneyService();
		$oJourneyTransfer->setInquiry($this->getEnquiry());
		return $oJourneyTransfer->getName($oCalendarFormat, $sView, $mLang);
	}

	/**
	 * {@inheritdoc}
	 */
	public function getTransferMode() {
		return $this->transfer_type;
	}

	/**
	 * @param Ext_TS_Inquiry_Journey $oJourney
	 * @return Ext_TS_Inquiry_Journey_Transfer|WDBasic
	 */
	public function getJourneyService(Ext_TS_Inquiry_Journey $oJourney = null) {

		if($oJourney instanceof Ext_TS_Inquiry_Journey) {
			$oTransfer = $oJourney->getJoinedObjectChild('transfers');
		} else {
			// Alten Müll am Funktionieren erhalten
			$oTransfer = new Ext_TS_Inquiry_Journey_Transfer();
		}

		$mStart = $this->start;
		$mStart = explode('_', $mStart);
		$mStart = end($mStart);

		$mEnd = $this->end;
		$mEnd = explode('_', $mEnd);
		$mEnd = end($mEnd);

		$oTransfer->transfer_type = $this->transfer_type;
		$oTransfer->start = $mStart;
		$oTransfer->end = $mEnd;
		$oTransfer->start_type = $this->start_type;
		$oTransfer->end_type = $this->end_type;
		$oTransfer->transfer_date = $this->transfer_date;
		$oTransfer->transfer_time = $this->transfer_time;
		$oTransfer->start_additional = $this->start_additional;
		$oTransfer->end_additional = $this->end_additional;
		$oTransfer->airline = $this->airline;
		$oTransfer->flightnumber = $this->flightnumber;
		$oTransfer->pickup = $this->pickup;
		$oTransfer->comment = $this->comment;

		return $oTransfer;

	}

	/**
	 * {@inheritdoc}
	 */
	public function validate($bThrowExceptions = false) {
		$this->_bSkipValidation = true;
		return parent::validate($bThrowExceptions);
	}

	/**
	 * Erzeugt ein Gruppen Transfer Objekt aus den Transfer Kombinationsdaten.
	 *
	 * @return Ext_Thebing_Inquiry_Group_Transfer
	 */
	public function getGroupService() {
		$oTransfer = new Ext_Thebing_Inquiry_Group_Transfer();
		$this->setServiceData($oTransfer);
		return $oTransfer;
	}

	/**
	 * Setzt die Daten für ein Service Objekt (Journey oder Group).
	 *
	 * @param Ext_TS_Inquiry_Journey_Transfer|Ext_Thebing_Inquiry_Group_Transfer $oTransfer
	 */
	private function setServiceData($oTransfer){

		$oTransfer->transfer_type = $this->transfer_type;

		$mStart = $this->start;
		$mStart = explode('_', $mStart);

		$mEnd = $this->end;
		$mEnd = explode('_', $mEnd);

		$oTransfer->start = end($mStart);
		$oTransfer->end = end($mEnd);
		$oTransfer->start_type = reset($mStart);
		$oTransfer->end_type = reset($mEnd);
		$oTransfer->transfer_date = $this->transfer_date;
		$oTransfer->transfer_time = $this->transfer_time;
		$oTransfer->comment = $this->comment;
		$oTransfer->start_additional = $this->start_additional;
		$oTransfer->end_additional = $this->end_additional;
		$oTransfer->airline = $this->airline;
		$oTransfer->flightnumber = $this->flightnumber;
		$oTransfer->pickup = $this->pickup;
		$oTransfer->booked = 1;

		if($oTransfer instanceof Ext_TS_Inquiry_Journey_Transfer) {
			$oTransfer->setInquiry($this->getEnquiry());
		}

	}

	/**
	 * {@inheritdoc}
	 */
	public function getStartLocation($oLanguage=null) {
		$oJourneyTransfer = $this->createJourneyTransferWithData();
		return $oJourneyTransfer->getStartLocation($oLanguage);
	}

	/**
	 * {@inheritdoc}
	 */
	public function getEndLocation($oLanguage=null) {
		$oJourneyTransfer = $this->createJourneyTransferWithData();
		return $oJourneyTransfer->getEndLocation($oLanguage);
	}

	/**
	 * {@inheritdoc}
	 */
	public function getLocationName($sType = 'start', $bTerminals = false, $sLang = '') {
		$oJourneyTransfer = $this->createJourneyTransferWithData();
		return $oJourneyTransfer->getLocationName($sType, $bTerminals, $sLang);
	}

	/**
	 * {@inheritdoc}
	 */
	public function getTerminalName($sType = 'start', $sLanguage = '') {
		$oJourneyTransfer = $this->createJourneyTransferWithData();
		return $oJourneyTransfer->getTerminalName($sType, $sLanguage);
	}

	/**
	 * Journey Transfer erstellen anhand des Kombinations-Transfers.
	 *
	 * @return Ext_TS_Inquiry_Journey_Transfer
	 */
	private function createJourneyTransferWithData() {
		$oJourneyTransfer = new Ext_TS_Inquiry_Journey_Transfer();
		$this->setServiceData($oJourneyTransfer);
		return $oJourneyTransfer;
	}

	protected function assignLineItemDescriptionVariables(\Core\Service\Templating $oSmarty, \Tc\Service\Language\Frontend $oLanguage) {
		
	}

}
