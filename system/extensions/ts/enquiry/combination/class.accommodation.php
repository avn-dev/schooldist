<?php

/**
 * @property integer $accommodation_id
 * @property integer $roomtype_id
 * @property integer $meal_id
 * @property ? $from
 * @property ? $until
 * @property integer $weeks
 * @property ? $arrival_time
 * @property ? $departure_time
 * @property string $comment
 */
class Ext_TS_Enquiry_Combination_Accommodation extends Ext_TS_Enquiry_Combination_Service implements Ext_TS_Service_Interface_Accommodation {

	use \Ts\Traits\LineItems\Accommodation;
	
	protected $_sTable = 'ts_enquiries_combinations_accommodations';

	protected $_sTableAlias = 'ts_eca';

	protected $sInfoTemplateType = 'accommodation';
	
	/**
	 * @var null|Ext_TS_Service_Accommodation_Helper_Extranights
	 */
	protected $_oExtraNightHelper = null;

	protected $_aJoinedObjects = array(
		'category' => array(
			'class' => 'Ext_Thebing_Accommodation_Category',
			'key'	=> 'accommodation_id'
		),
		'roomtype' => array(
			'class' => 'Ext_Thebing_Accommodation_Roomtype',
			'key'	=> 'roomtype_id'
		),
		'meal' => array(
			'class' => 'Ext_Thebing_Accommodation_Meal',
			'key'	=> 'meal_id'
		),
	);

	protected $_aFormat = array(
		'accommodation_id' => array(
			'validate' => 'INT_POSITIVE',
			'required' => true
		),
		'roomtype_id' => array(
			'validate' => 'INT_POSITIVE',
			'required' => true
		),
		'meal_id' => array(
			'validate' => 'INT_POSITIVE',
			'required' => true
		),
		'weeks' => array(
			'validate' => 'INT_POSITIVE',
			'required' => true
		),
		'from' => array(
			'validate' => 'DATE',
			'required' => true
		),
		'until' => array(
			'validate' => 'DATE',
			'required' => true
		),
		'arrival_time' => array(
			'validate' => 'TIME'
		),
		'departure_time' => array(
			'validate' => 'TIME'
		)
	);

		
	public function __construct($iDataID = 0, $sTable = null)
	{
		parent::__construct($iDataID, $sTable);
		
		$this->_aJoinedObjects['category'] = array(
			'class' => 'Ext_Thebing_Accommodation_Category',
			'key'	=> 'accommodation_id'
		);
		
		$this->_aJoinedObjects['roomtype'] = array(
			'class' => 'Ext_Thebing_Accommodation_Roomtype',
			'key'	=> 'roomtype_id'
		);
		
		$this->_aJoinedObjects['meal'] = array(
			'class' => 'Ext_Thebing_Accommodation_Meal',
			'key'	=> 'meal_id'
		);
	}
	
	/**
	 *
	 * @param int $iSchoolId
	 * @param string $sDisplayFrontendLanguage
	 * @param bool $bShort
	 * @return string 
	 */
	public function getInfo($iSchoolId = false, $sDisplayFrontendLanguage = false, $bShort = false) {
		
		if($this->_oExtraNightHelper !== null) {
			// Hier kommen keine Extranächte/Extrawochen rein, da die Methoden dafür abgeleitet wurden
			$sFrom = $this->_oExtraNightHelper->getRealFrom('accommodation');
			$sUntil = $this->_oExtraNightHelper->getRealUntil('accommodation');
		} else {
			$sFrom = $this->from;
			$sUntil = $this->until;
		}
		
		$aParams = array(
			'school_id'			=> $iSchoolId,
			'language'			=> $sDisplayFrontendLanguage,
			'from'				=> $sFrom,
			'until'				=> $sUntil,
			'accommodation_id'	=> $this->accommodation_id,
			'roomtype_id'		=> $this->roomtype_id,
			'meal_id'			=> $this->meal_id,
			'weeks'				=> $this->weeks,
			'format'			=> false,
			'inquiry'			=> $this->getEnquiry()
		); 

		$sName = Ext_TS_Inquiry_Journey_Accommodation::getOutputInfo($aParams, $bShort);

		return $sName;
	}

	/**
	 * @return Ext_Thebing_Accommodation_Category 
	 */
	public function getCategory() {
		return $this->getJoinedObject('category');
	}

	/**
	 * @return Ext_Thebing_Accommodation_Roomtype
	 */
	public function getRoomType() {
		return $this->getJoinedObject('roomtype');
	}

	/**
	 * @return Ext_Thebing_Accommodation_Meal
	 */
	public function getMeal() {
		return $this->getJoinedObject('meal');
	}

	/**
	 * @return Ext_Thebing_School_Additionalcost[]
	 */
	public function getAdditionalCosts() {
		return $this->getJourneyService()->getAdditionalCosts();
	}

	/**
	 *
	 * @param int $iAdditionalCostId
	 * @param int $iWeeks
	 * @param int $iAccommodationCount
	 * @param Tc\Service\LanguageAbstract $oLanguage
	 * @return string 
	 */
	public function getAdditionalCostInfo($iAdditionalCostId, $iWeeks, $iAccommodationCount, Tc\Service\LanguageAbstract $oLanguage)
	{
		$oJourneyAccommodation = $this->getJourneyService();
		
		$oJourneyAccommodation->setInquiry($this->getEnquiry());
		
		$sInfo = $oJourneyAccommodation->getAdditionalCostInfo($iAdditionalCostId, $iWeeks, $iAccommodationCount, $oLanguage);
		
		return $sInfo;
	}
	
	/**
	 * Liefert die Extranacht Beschreibung
	 * @param int $iExtraNightsCurrent
	 * @param string $sDisplayLanguage
	 * @param string $sPeriod
	 * @return string 
	 */
	public function getExtraNightInfo($iExtraNightsCurrent, Tc\Service\LanguageAbstract $oLanguage, $sPeriod = '') {

		$oJourneyAccommodation = $this->getJourneyService();
		$oJourneyAccommodation->setExtranightHelper($this->_oExtraNightHelper);
		
		$sName = $oJourneyAccommodation->getExtraNightInfo($iExtraNightsCurrent, $oLanguage, $sPeriod);
		
		return $sName;
	}

	/**
	 * Liefert die Beschreibung zur Extrawoche
	 * @param $iExtraWeeks
	 * @param $sDisplayLanguage
	 * @param string $sPeriod
	 * @return mixed
	 */
	public function getExtraWeekInfo($iExtraWeeks, Tc\Service\LanguageAbstract $oLanguage, $sPeriod = '') {

		$oJourneyAccommodation = $this->getJourneyService();
		$oJourneyAccommodation->setExtranightHelper($this->_oExtraNightHelper);

		$sName = $oJourneyAccommodation->getExtraWeekInfo($iExtraWeeks, $oLanguage, $sPeriod);

		return $sName;
	}

	/**
	 * Erzeugt ein Gruppen Course Obj aus den Kurs Kombinationsdaten
	 *
	 * @return Ext_Thebing_Inquiry_Group_Accommodation
	 */
	public function getGroupService() {

		$oAccommodation = new Ext_Thebing_Inquiry_Group_Accommodation();

		$this->_setServiceData($oAccommodation);

		return $oAccommodation;

	}

	/**
	 * @param Ext_TS_Inquiry_Journey $oJourney
	 * @return Ext_TS_Inquiry_Journey_Accommodation
	 */
	public function getJourneyService(Ext_TS_Inquiry_Journey $oJourney = null) {

		if($oJourney instanceof Ext_TS_Inquiry_Journey) {
			$oAccommodation = $oJourney->getJoinedObjectChild('accommodations');
		} else {
			// Alten Müll am Funktionieren erhalten
			$oAccommodation = new Ext_TS_Inquiry_Journey_Accommodation();
		}

		$this->_setServiceData($oAccommodation);

		return $oAccommodation;

	}

	/**
	 * Setzt die Daten für ein Service Objekt (Journey oder Group).
	 *
	 * @param Ext_Thebing_Inquiry_Group_Accommodation|Ext_TS_Inquiry_Journey_Accommodation $oAccommodation
	 */
	private function _setServiceData($oAccommodation){

		$aData = $oAccommodation->getArray();

		$oAccommodation->accommodation_id = $this->accommodation_id;
		$oAccommodation->roomtype_id = $this->roomtype_id;
		$oAccommodation->meal_id = $this->meal_id;
		$oAccommodation->from = $this->from;
		$oAccommodation->until = $this->until;
		$oAccommodation->weeks = $this->weeks;
		$oAccommodation->from_time = $this->arrival_time;
		$oAccommodation->until_time = $this->departure_time;
		$oAccommodation->visible = 1;

		if(array_key_exists('comment', $aData)) {
			$oAccommodation->comment = $this->comment;
		}

		if($oAccommodation instanceof Ext_TS_Inquiry_Journey_Accommodation) {
			$oAccommodation->setInquiry($this->getEnquiry());
		}

	}

	/**
	 * Gibt die Fehlermeldung für Unterkünfte zurück, wenn kein Leistungszeitraum gebucht wurde.
	 *
	 * @return string
	 */
	protected function _getErrorMessage() {

		return sprintf(
			Ext_Thebing_L10N::t('Sie haben für die Unterkunft "%s" keinen Leistungszeitraum angegeben'),
			$this->getCategory()->getName()
		);

	}

	/**
	 * Helper-Klasse für Extranächte setzen.
	 *
	 * @param null|Ext_TS_Service_Accommodation_Helper_Extranights $oHelper
	 */
	public function setExtranightHelper(Ext_TS_Service_Accommodation_Helper_Extranights $oHelper = null) {
		$this->_oExtraNightHelper = $oHelper;
	}

}
