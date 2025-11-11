<?php

include_once \Util::getDocumentRoot().'phpunit/school/testSetUp.php';

/**
 * Setup für die Preisberechnung
 * 
 * @todo noch eine allgemeine AmountSetUp Klasse erstellen, 
 * die eine Schule anlegt nach Preismodel & Preise anlegt, da das später bei den Kursen auch benötigt wird
 * 
 * @backupGlobals disabled
 * @backupStaticAttributes enabled
 */
class AmountSetUp extends schoolTestSetup
{
	protected $_sPriceStructure;
	
	/**
	 *
	 * @var Ext_Thebing_School 
	 */
	protected $_oSchool;
	
	/**
	 *
	 * @var Ext_TS_Inquiry 
	 */
	protected $_oInquiry;
	
	/**
	 *
	 * @var Ext_Thebing_Accommodation_Category
	 */
	protected $_oAccommodationCategory;
	
	/**
	 *
	 * @var Ext_Thebing_Accommodation_Roomtype 
	 */
	protected $_oRoomType;
	
	/**
	 *
	 * @var Ext_Thebing_Accommodation_Meal
	 */
	protected $_oMeal;
	
	/**
	 *
	 * @var Ext_Thebing_Marketing_Saison <array>
	 */
	protected $_aSeasons = array();
	
	/**
	 *
	 * @var Ext_Thebing_School_Week <array> 
	 */
	protected $_aWeeks = array();

	/**
	 * Schuldatenbank kopieren mit leeren Daten und ein paar Daten wie client etc füllen
	 */
	public function setUp()
    {
		parent::setUp();
		
		//Schule nach Preisstruktur erstellen
		$this->_oSchool = $this->_createDefaultSchoolByPriceStructrue($this->_sPriceStructure, array(
			'accommodation_start' => 'so',
		));

		// School ID setzen
        $iSessionSchoolId = \Core\Handler\SessionHandler::getInstance()->get('sid');
		
		//Client injekten um Lizenz zu faken
		$this->_oSchool->setClient($this->_oClient);
		
		//Standardbuchung anlegen
		$this->_oInquiry = $this->_createDefaultInquiry($this->_oSchool);
		
		//Standard Raumtyp anlegen
		$this->_oRoomType = $this->_createDefaultRoomType($this->_oSchool);
		
		//Standard Mahlzeit anlegen
		$this->_oMeal = $this->_createDefaultMeal($this->_oSchool);
		
		//Saisons anlegen
		$this->_aSeasons[1] = $this->_createSeason($this->_oSchool, array(
			'valid_from'	=> '2012-01-01',
			'valid_until'	=> '2012-12-31',
		));
		
		$this->_aSeasons[2] = $this->_createSeason($this->_oSchool, array(
			'valid_from'	=> '2012-02-01',
			'valid_until'	=> '2012-03-03',
		));
		
		$this->_aSeasons[3] = $this->_createSeason($this->_oSchool, array(
			'valid_from'	=> '2012-03-04',
			'valid_until'	=> '2012-04-01',
		));
	
		$this->_aSeasons[4] = $this->_createSeason($this->_oSchool, array(
			'valid_from'	=> '2012-04-01',
			'valid_until'	=> '2012-04-21',
		));
		
		$this->_aSeasons[5] = $this->_createSeason($this->_oSchool, array(
			'valid_from'	=> '2012-06-03',
			'valid_until'	=> '2012-06-23',
		));
		
		$this->_aSeasons[6] = $this->_createSeason($this->_oSchool, array(
			'valid_from'	=> '2012-07-08',
			'valid_until'	=> '2012-09-08',
		));
		
		$this->_aSeasons[7] = $this->_createSeason($this->_oSchool, array(
			'valid_from'	=> '2012-07-29',
			'valid_until'	=> '2012-08-18',
		));
	}
	
	/**
	 * Für eine bestimmte Saison mehrere Preise hinterlegen
	 * 
	 * @param int $iSeasonKey
	 * @param array $aData
	 */
	protected function _createWeekPrices($iSeasonKey, array $aData)
	{
		if(!isset($this->_aSeasons[$iSeasonKey]))
		{
			Throw new Exception('Season not found!');
		}
		
		$oSeason	= $this->_aSeasons[$iSeasonKey];
		$sKey		= 'accommodation_' . $this->_oAccommodationCategory->id . '_' . $this->_oRoomType->id . '_' . $this->_oMeal->id;
		
		foreach($aData as $iKey => $mValue)
		{
			$aData = array();
			
			if(
				isset($this->_aWeeks[$iKey])
			)
			{
				$aData['week_id'] = $this->_aWeeks[$iKey]->id;
			}
			
			$aData['parent_type']	= $sKey;
			$aData['value']			= $mValue;
			
			$this->_createPrice($this->_oSchool, $oSeason, $aData);
		}
		
		return $this;
	}
	
	/**
	 * Wrapper für den parent, da wir ja in unsere amountsetup immer die Informationen Buchung,U.kategorie,Raumart,Verpflegung
	 * schon haben und das nicht in jeder Testmethode unnötig definieren müssen
	 * 
	 * @param array $aData
	 */
	protected function _createJourneyAccommodation($aData)
	{
		$oInquiry					= $this->_oInquiry;
		
		$aData['accommodation_id']	= $this->_oAccommodationCategory->id;
		$aData['roomtype_id']		= $this->_oRoomType->id;
		$aData['meal_id']			= $this->_oMeal->id;
		
		return parent::_createJourneyAccommodation($oInquiry, $aData);
	}
	
	/**
	 *
	 * @param Ext_TS_Inquiry_Journey_Accommodation $oJourneyAccommodation
	 * @return float
	 */
	protected function _calculateAmount(Ext_TS_Inquiry_Journey_Accommodation $oJourneyAccommodation, $iStartWeek=0) {

		$oAccommodationAmount = new Ext_Thebing_Accommodation_Amount();
		$oAccommodationAmount->setInquiryAccommodation($oJourneyAccommodation->id);
		$fAmount = $oAccommodationAmount->calculate(false, $iStartWeek);
				
		__pout($oAccommodationAmount->aCalculationDescription);
	
		return $fAmount;

	}
	
	/**
	 * Unterkunftskategorie so manipulieren, dass nur Extrawoche zur Verfügung steht
	 * @return AmountSetUp 
	 */
	protected function _makeAccommodationCategoryOnlyExtraWeek()
	{
		$oExtraWeek		= null;
		$aWeeks			= $this->_aWeeks;
		
		//Hier wollen wir speziell nur mit Extrawochen arbeiten, darum suchen wir uns die Extrawoche
		//in den definierten Wochen aus und übergeben diese der Unterkunftskategorie
		foreach($aWeeks as $oWeek)
		{
			if($oWeek->extra == 1)
			{
				$oExtraWeek = $oWeek;
			}
		}
		
		//Wenn keine Extrawoche definiert ist soll dieser Test fehlschlagen!
		if(!$oExtraWeek)
		{
			Throw new Exception("No extra week defined!");
		}
		
		//Unterkunftskategorie "Wochen" ändern und abspeichern
		$this->_oAccommodationCategory->weeks = array($oExtraWeek->id);
		$this->_oAccommodationCategory->save();
		
		return $this;
	}
	
	protected function _assertPriceEquals($fAmountExpected, $fAmountCalculated)
	{
		$this->assertEquals($fAmountExpected, $fAmountCalculated, 'Statt ' . $fAmountExpected . ' kommt ' . $fAmountCalculated . ' raus!');
	}
}