<?php

/**
 * Klasse für die Berechnung von Unterkunftspreisen 
 */
class Ext_Thebing_Accommodation_Amount {

	use \Ts\Traits\SpecialAmount;
	
	const PRICE_PER_WEEK = 0;
	const PRICE_PER_NIGHT = 1;
	const PRICE_PER_NIGHT_WEEKS = 2;

	protected $_iAccommodationCategoryId		= 0;
	protected $_iRoomtypeId				= 0; 
	protected $_iMealId					= 0;
	protected $_iWeeks					= 0;
	protected $_iCalculateStart			= 0;
	protected $_iCalculateEnd			= 0;
	protected $_iSalaryTime				= 0;
	protected $_iDiscountTimepoint	= 0;	
	protected $_oCostCategory			= null;
	protected $_aSalary					= array();
	protected $_oSaison					= null;
	protected $_oCurrency				= null;
	protected $_oAgency					= null;

	protected $nationality;

	/** @var Ext_TS_Inquiry|Ext_TS_Enquiry */
	protected $_oInquiry				= null;

	/** @var Ext_TS_Inquiry_Journey_Accommodation|Ext_TS_Enquiry_Combination_Accommodation */
	protected $_oInquiryAccommodation	= null;
	protected $_iAccommodationProvider	= 0;

	/**
	 * @var Ext_Thebing_Accommodation_Category
	 */
	protected $oAccommodationCategory;

	// Special
	protected $_fSpecialAmount			= 0;
	protected $_fSpecialAmountNetto		= 0;
	protected $_aSpecialBlocks			= array();

	protected $_sCalculateType			= 'price';
	protected $_sCalculateModel			= 'week';
	
	/**
	 * @var Ext_Thebing_School
	 */
	protected $_oSchool					= null;
	protected $_bAgencyAmount			= false;

	public $aErrors						= array(); 
	public $aWeekPrices					= array();
	public $aNightPrices				= array(); 
	
	public $aCalculationDescription;
	public $aSpecialCalculationDescription = [];
	
	public $aCurrencExtraNight = null;

	public function  __construct() {
		// definiere on Preise oder Kosten gerechnet werden müssen
		$this->setCalculateType();
	}

	/**
	 * Setzt den Zeitpunkt für die Saisonberechnung bei frühbucher Rabatt
	 * Wichtig für das Anmeldeformular
	 * @param int $iTime
	 */
	public function setDiscountTime($iTime){
		$this->_iDiscountTimepoint = (int)$iTime;
	}
	
	public function setAccommodation($iAccommodation) {
		$this->_iAccommodationCategoryId = $iAccommodation;
	}

	public function setRoomtype($iRoomtype){
		$this->_iRoomtypeId = $iRoomtype;
	}

	public function setMeal($iMeal){
		$this->_iMealId = $iMeal;
	}

	public function setWeeks($iWeeks){
		$this->_iWeeks = $iWeeks;
	}

	public function setAccommodationProvider($iAccommodationProvider){
		$this->_iAccommodationProvider = $iAccommodationProvider;
	}

	public function setCalculatePeriod($iStart, $iEnd){
		$this->_iCalculateStart = $iStart;
		$this->_iCalculateEnd = $iEnd;
		$this->setCalculateModel();
	}

	/**
	 * @param Ext_Thebing_School|int $iSchool
	 */
	public function setSchool($iSchool = null) {

		if($iSchool instanceof Ext_Thebing_School) {
			$this->_oSchool = $iSchool;
			return;
		}

		if($iSchool == null) {
            $iSessionSchoolId = \Core\Handler\SessionHandler::getInstance()->get('sid');
			$iSchool = $iSessionSchoolId;
		}

		if($iSchool <= 0) {
			return;
		}

		$this->_oSchool = Ext_Thebing_School::getInstance($iSchool);
	}

	/**
	 * Kostenkaegorie setzen
	 * @param <type> $iCostCategory
	 */
	public function setCostCategory($iCostCategory = 0){
		if($iCostCategory <= 0){
			$iCostCategory = (int)$this->_aSalary['costcategory_id'];
		}
		if($iCostCategory > 0){
			$oCostCategory = Ext_Thebing_Accommodation_Cost_Category::getInstance($iCostCategory);
			$this->_oCostCategory = $oCostCategory;
		}
		
	}

	/**
	 * Unterkunftsanbieter Kostenkategorie parameter
	 */
	public function setAccommodationSalary(){

		if(
			$this->_iAccommodationProvider > 0
		) {
			
			$iTime = $this->_iSalaryTime;
			if($iTime <= 0){
				$iTime = $this->_iCalculateStart;
			}
			$oAccommodation = Ext_Thebing_Accommodation::getInstance($this->_iAccommodationProvider);
			$oWDDate = new WDDate();
			$oWDDate->set($iTime, WDDate::TIMESTAMP);
			$aSalary = $oAccommodation->getSalary($oWDDate->get(WDDate::DB_DATE));
			
			$this->_aSalary = $aSalary;
			// lade Kostenkategorie
			$this->setCostCategory($aSalary['costcategory_id']);
		}
		
	}

	/*
	 * Liefert den SpecialBetrag
	 */
	public function getSpecialAmount($bNetto = false):float {
		if($bNetto){
			return $this->_fSpecialAmountNetto;
		}else{
			return $this->_fSpecialAmount;
		}
	}

	public function getCalculationDescription() {
		return $this->aCalculationDescription;
	}

	/*
	 * Liefert die verwendeten Special Blöcke
	 */
	public function getSpecialBlocks():array {
		return $this->_aSpecialBlocks;
	}

	/**
	 * gibt die Kostenkategorienparameter zurück
	 * @return <type>
	 */
	public function getAccommodationSalary(){

		if(empty($this->_aSalary)){
			$this->setAccommodationSalary();
		}

		return $this->_aSalary;
	}

	/**
	 * Setz das Berechnungsmodel
	 * pro woche/nacht oder monat(kosten)
	 */
	public function setCalculateModel() {

		$sType = 'week';

		$oAccommodationUtil	= new Ext_Thebing_Accommodation_Util($this->_oSchool);
		$oAccommodationUtil->setAccommodationCategorie($this->_iAccommodationCategoryId);

		$this->setAccommodationSalary();

		if(
			!empty($this->_aSalary) &&
			$this->_aSalary['costcategory_id'] == -1
		) {
			$sType = 'fix_'.(string)$this->_aSalary['salary_period'];
		} else if(
			$this->_oCostCategory &&
			$this->_oCostCategory->cost_type != ""
		) {
			$sType = (string)$this->_oCostCategory->cost_type;
		}

		if(
			$this->_sCalculateType != 'cost' &&
			$this->oAccommodationCategory->hasNightPrice($this->_oSchool)
		) {
			$sType = 'night';
		}

		$this->_sCalculateModel = $sType;

	}

	public function getCalculateModel() {
		return $this->_sCalculateModel;
	}
	
	/**
	 * Set die Berechnungsart ( Preise / Kosten )
	 * @param <type> $sType
	 */
	public function setCalculateType($sType = 'price') {
		$this->_sCalculateType = $sType;
	}

	public function setSalaryTime($iTime) {
		$this->_iSalaryTime = $iTime;
	}

	/**
	 * Setzt den Zeitraum der Berechnung
	 * @param <type> $iStart
	 * @param <type> $iEnd
	 */
	public function setCalculateTime($iStart, $iEnd) {
		$this->_iCalculateStart = $iStart;
		$this->_iCalculateEnd	= $iEnd;
	}

	public function setCurrency($iCurrency) {
		$oCurrency = Ext_Thebing_Currency::getInstance($iCurrency);
		$this->_oCurrency = $oCurrency;
	}

	public function setAgency($iAgency, $iSchool = 0) {

		$oSchool = null;

		if($iSchool > 0) {
			// Schule muss gesetzt werden für All-School Inbox
			$oSchool = Ext_Thebing_School::getInstance($iSchool);
		}

		$oAgency = Ext_Thebing_Agency::getInstance($iAgency);
		$oAgency->setSchool($oSchool);
		$this->_oAgency = $oAgency;
	}

	/**
	 * Setzt alle Informationen einer gebuchten Unterkufnt
	 * @param <type> $iInquiryAccommodation
	 */
	public function setInquiryAccommodation($mInquiryAccommodation) {

		if(
			is_object($mInquiryAccommodation) &&
			$mInquiryAccommodation instanceof Ext_TS_Service_Interface_Accommodation
		) {
			$oInquiryAccommodation = $mInquiryAccommodation;
			$iInquiryAccommodation = (int)$oInquiryAccommodation->id;
		} elseif(is_numeric($mInquiryAccommodation)) {
			$iInquiryAccommodation	= $mInquiryAccommodation;
			$oInquiryAccommodation	= Ext_TS_Inquiry_Journey_Accommodation::getInstance($iInquiryAccommodation);
		} else {
			throw new Exception('Accommodation Service must be an instance of Ext_TS_Service_Interface_Accommodation!');
		}

		// Nicht nur vorhandene wg. Preisliste
		if($oInquiryAccommodation->active == 1) {

			/*
			 * Es wird nach der gebuchten Kategorie abgerechnet sofern diese in den Kategorien des UA vorkommt, 
			 * ansonsten nach der Standardkategorie des UA
			 */
			if($this->_iAccommodationProvider > 0) {

				$oAccommodation = Ext_Thebing_Accommodation::getInstance($this->_iAccommodationProvider);

				$this->_iAccommodationCategoryId = $oAccommodation->default_category_id;

				if(
					in_array($oInquiryAccommodation->accommodation_id, $oAccommodation->accommodation_categories)
				) {
					$this->_iAccommodationCategoryId = $oInquiryAccommodation->accommodation_id;
				}

			} else {
				$this->_iAccommodationCategoryId = $oInquiryAccommodation->accommodation_id;
			}

			$this->oAccommodationCategory = Ext_Thebing_Accommodation_Category::getInstance($this->_iAccommodationCategoryId);
			
			$this->_iRoomtypeId				= $oInquiryAccommodation->roomtype_id;
			$this->_iMealId					= $oInquiryAccommodation->meal_id;
			$this->_iWeeks					= $oInquiryAccommodation->weeks;
			$oInquiry						= $oInquiryAccommodation->getInquiry();
			$oSchool						= $oInquiry->getSchool();
			$this->_oInquiry				= $oInquiry;
			$this->nationality = $this->_oInquiry->getCustomer()->nationality;
			
			if(empty($this->nationality)) {
				$this->nationality = null;
			}
			
			$this->_oInquiryAccommodation	= $oInquiryAccommodation;
			$this->_iDiscountTimepoint		= $oInquiry->getCreatedForDiscount(); // Zeitpunkt für Frühbucherrabattbestimmung

			$this->setSchool($oSchool->id);
			$this->setCurrency($oInquiry->getCurrency());
			$this->setAgency($oInquiry->agency_id, $oSchool->id);
			
			$oWDDate = new WDDate();
			$oWDDate->set($oInquiryAccommodation->from, WDDate::DB_DATE);
			$iStart = (int)$oWDDate->get(WDDate::TIMESTAMP);
			if($iStart <= 0){
				$iStart = 0;
			}
			$this->_iCalculateStart = $iStart;
			
			$oWDDate->set($oInquiryAccommodation->until, WDDate::DB_DATE);
			$iEnd = (int)$oWDDate->get(WDDate::TIMESTAMP);
			if($iEnd <= 0){
				$iEnd = 0;
			}           
			$this->_iCalculateEnd = $iEnd;

			$this->setCalculateModel();

			return true;

		}

		return false;
	}

	protected function checkSchool(){
		if(empty($this->_oSchool)){
			return false;
		}
		return true;
	}


	protected function setSaison($iDate=null) {
		$bPrice = true;
		$bCost	= false;

		if(!$iDate) {
			$iDate = $this->_iCalculateStart;
		}

		// Anhand dieser Zeit wird geguckt ob es eine alternativ (Frühbuchersaison) gibt.
		$iDiscountTime = $this->_iDiscountTimepoint;

		if($this->_sCalculateType == 'cost'){
			$bPrice 		= false;
			$bCost			= true;
			$iDiscountTime 	= 0;
		}

		if($this->checkSchool()){

			$oSaison = new Ext_Thebing_Saison($this->_oSchool->id, $bPrice , false, false, $bCost);
			$iSaison = $oSaison->search($iDate, 'accommodation', $iDiscountTime);

			if($iSaison > 0) {
				$oSaison->setSaisonById($iSaison);
				$this->_oSaison = $oSaison;
			} else {
				$this->_oSaison = null;
				$this->setSaisonError();
			}
		}

	}

	public function getWeekPrice($iWeekId, $bWithSign = false){

		$fPrice = 0;

		if(!$this->_oSaison){
			return 0;
		}

		if($this->_sCalculateType != 'cost'){
			$oPrice = new Ext_Thebing_Price($this->_oSchool->id, $this->_oSaison, $this->_oCurrency, '', $this->nationality, $this->_oAgency?->getCategory());
			$oPrice->setSaison($this->_oSaison);
			$aWeek = $oPrice->getWeekById($iWeekId);

			if($aWeek){
				$oPrice->setWeek($aWeek);
			}

			$fPrice = $oPrice->getAccommodationPrice($this->_iAccommodationCategoryId, $this->_iRoomtypeId, $this->_iMealId, $iWeekId, $bWithSign);

		} else if($this->_oCostCategory) {
			$oCosts = Ext_Thebing_School_Cost_Accommodation::getInstance($this->_oSchool->id);
			$oCosts->setSeasonIdAndCurrencyId($this->_oSaison->getSaisonId(), $this->_oCurrency->id);
			$fPrice = $oCosts->getCost($this->_oCostCategory->id, $this->_iAccommodationCategoryId, $this->_iRoomtypeId, $this->_iMealId, $iWeekId);
		}
		
		return $fPrice;
	}

	/**
	 * NUR KOSTEN!
	 * Berechnet einen FIXEN Betrag
	 * Unabhängig von einer Inquiry Acco.
	 * @return float
	 */
	public function calculateFixMonth(){

		$oWDDate = new WDDate();

		// Setzte den Ersten des Monats
		$oWDDate->set($this->_iCalculateStart, WDDate::TIMESTAMP);
		$oWDDate->set(1, WDDate::DAY);
		$oWDDate->set('00:00:00', WDDate::TIMES);
		$iTempStart = $oWDDate->get(WDDate::TIMESTAMP);

		// Setze den letzen des Monats
		$oWDDate->set($this->_iCalculateEnd, WDDate::TIMESTAMP);
		$oWDDate->set($oWDDate->get(WDDate::MONTH_DAYS), WDDate::DAY);
		$oWDDate->set('00:00:00', WDDate::TIMES);

		// Monats Diff bilden
		$iMonthCount = $oWDDate->getDiff(WDDate::MONTH, $iTempStart, WDDate::TIMESTAMP);
		// +1 da der anfangs monat dazu zählt
		$iMonthCount = $iMonthCount + 1;

		// wieder auf den Start setzen
		$oWDDate->set($iTempStart, WDDate::TIMESTAMP);

		for($i = 1; $i <= $iMonthCount; $i++){
			$this->aWeekPrices[$oWDDate->get(WDDate::TIMESTAMP)] = $this->_aSalary['salary'];
			$oWDDate->add(1, WDDate::MONTH);
		}

		
		return $this->_aSalary['salary'];
		
	}
	/**
	 * NUR KOSTEN!
	 * Berechnet einen FIXEN Betrag
	 * Unabhängig von einer Inquiry Acco.
	 * @return float
	 */
	public function calculateFixWeek(){

		$oWDDate = new WDDate();

		$oWDDate->set($this->_iCalculateStart, WDDate::TIMESTAMP);
		$oWDDate->set(1, WDDate::DAY);
		$oWDDate->set('00:00:00', WDDate::TIMES);

		$iWeekCount = $oWDDate->getDiff(WDDate::WEEK, $this->_iCalculateEnd, WDDate::TIMESTAMP);
		$iWeekCount = $iWeekCount + 1;

		for($i = 1; $i <= $iWeekCount; $i++){
			$this->aWeekPrices[$oWDDate->get(WDDate::TIMESTAMP)] = $this->_aSalary['salary'];
			$oWDDate->add(1, WDDate::WEEK);
		}

		return $this->_aSalary['salary'];

	}

	public function calculate($bAgencyAmount = false, $iStartWeek = 0) {

		$this->_bAgencyAmount = $bAgencyAmount;
		$this->aWeekPrices = array();
		
		$this->setSaison();

		$this->aCalculationDescription[] = $this->_sCalculateModel;
		if($iStartWeek > 0) {
			$this->aCalculationDescription[] = L10N::t('SW').': '.$iStartWeek;	
		}
		$this->aCalculationDescription[] = 'line';

		if($this->_sCalculateModel == 'night') {
			return $this->calculateNight($iStartWeek);
		} else if(
			$this->_sCalculateModel == 'week' ||
			$this->_sCalculateModel == 'periods'
		) {
			return $this->calculateWeek($iStartWeek);
		} else if($this->_sCalculateModel == 'fix_week') {
			return $this->calculateFixWeek();
		} else if($this->_sCalculateModel == 'fix_month') {
			return $this->calculateFixMonth();
		}

	}

	/*
	 * Unterkunft die außerhalb von seanson liegen
	 */
	public function setSaisonError(){
		$this->aErrors['accommodation_season_not_found'][] = (int)$this->_oInquiryAccommodation->id;	
	}

	/*
	 * season für Unterkünfte gefunden
	 */
	public function setSeasonFound(){
		$this->aErrors['accommodation_season_found'][] = (int)$this->_oInquiryAccommodation->id;
	}
	
	public function getWeekByNumber($iWeek, $oAccommodationUtil){

		$aBack = array();

		if($this->_sCalculateType == 'cost') {
			
			$aExtraWeek = array();
			$aWeeks = $this->_oCostCategory->getCostWeeks();

			// Dazugehörige Wochen durchlaufen
			foreach($aWeeks as $iKey=>$aWeek) {
	
				if($aWeek['id'] <= 0) {
					continue;
				}

				// Wenn Wochen Start = Angegebene Wochennummer dann nehme diese
				if(
					$aWeek['start_week'] == $iWeek &&
					$aWeek['extra'] != 1 &&
					empty($aBack)
				) {
					$aBack = $aWeek;
				}
				
				// Wenn extra nacht, dann merke dir das
				if($aWeek['extra'] == 1) {
					$aExtraWeek = $aWeek;
					// Extrawoche nicht als letzte Woche verwenden
					unset($aWeeks[$iKey]);
				}
			}
			
			// Wenn keine Woche gefunden es aber generel wochen gibt
			if(
				empty($aBack) &&
				!empty($aWeeks)
			) {
				// nehme die letzte woche
				$aBack = end($aWeeks);
				// und setzte die extra Woche
				$aBack['extraWeek'] = $aExtraWeek;
			}

		} else {
			$aBack = $this->_oSchool->getWeekByPositionForAccommodation($iWeek, $oAccommodationUtil);
		}

		return $aBack;
	}
	
	public function getWeekByCount($iWeekCount, $oAccommodationUtil) {

		$aBack = array();

		if($this->_sCalculateType == 'cost') {

			$aExtraWeek = array();
			$aWeeks = $this->_oCostCategory->getCostWeeks();

			// Dazugehörige Wochen durchlaufen
			foreach((array)$aWeeks as $aWeek) {
				// Wenn Wochen Start = Angegebene Wochennummer dann nehme diese
				if(
					$iWeekCount >= $aWeek['start_week'] &&
					$iWeekCount <= ($aWeek['start_week'] + $aWeek['week_count'] - 1)
				){
					$aBack = $aWeek;
				}
				// Wenn extra nacht, dann merke dir das
				if($aWeek['extra'] == 1){
					$aExtraWeek = $aWeek;
				}
			}

			// Wenn keine Woche gefunden es aber generel wochen gibt
			if(empty($aBack) && !empty($aWeeks) && !empty($aExtraWeek) ){
				// nehme die letzte woche
				$aBack = $aExtraWeek;
				// und setzte die extra Woche
				$aBack['extraWeek'] = $aExtraWeek;
			}

		} else {
			$aBack = $this->_oSchool->getWeekByNumberOfWeeksForAccommodation($iWeekCount, $oAccommodationUtil);
		}

		return $aBack;
	}

	public function getExtraWeek(){

		if($this->_sCalculateType == 'cost'){
			$aWeeks = $this->_oCostCategory->getCostWeeks();
			foreach((array)$aWeeks as $aWeek){
				if($aWeek['extra'] == 1){
					return $aWeek;
				}
			}
		} else {
			$oPrice = new Ext_Thebing_Price($this->_oSchool, null, null, '', $this->nationality, $this->_oAgency?->getCategory());
			$aWeek = $oPrice->getExtraWeek($this->_iAccommodationCategoryId);
			return $aWeek;
		}

		return false;
	}

	public function calculateExtraWeek($bAgencyAmount = false){

		$this->_bAgencyAmount = $bAgencyAmount;

		$this->setSaison();
		
		### DEBUG Ausgaben
		Ext_Thebing_Util::debug('Zeitpunkt:'.strftime('%x', $this->_iCalculateStart), 'calculateExtraWeek');
		###
		
		### DEBUG Ausgaben
		Ext_Thebing_Util::debug('Saison:', 'calculateExtraWeek');
		Ext_Thebing_Util::debug($this->_oSaison, 'calculateExtraWeek');
		###
		
		if(!$this->_oInquiry || !$this->_oSaison){
			return 0;
		}

		$bCost = false;
		if($this->_sCalculateType == 'cost'){
			if(!$this->_oCostCategory){
				return 0;
			}
			$bCost = true;
		}


		$iPrice = 0;
		
		if($this->_sCalculateType == 'cost'){
			
			$aExtraWeeks = $this->_oInquiry->getExtraWeeks('forCalculate', $this->_oInquiryAccommodation->id, $this->_iCalculateStart, $this->_iCalculateEnd, $bCost);
			
			### DEBUG Ausgaben
			Ext_Thebing_Util::debug('gebuchte Extrawochen:', 'calculateExtraWeek');
			Ext_Thebing_Util::debug($aExtraWeeks, 'calculateExtraWeek');
			###
			
			$aExtraWeek = $this->getExtraWeek();
			
			### DEBUG Ausgaben
			Ext_Thebing_Util::debug('Kosten Extrawoche:', 'calculateExtraWeek');
			Ext_Thebing_Util::debug($aExtraWeek, 'calculateExtraWeek');
			###
			
			if($aExtraWeek){

				$iNightsOfExtraWeek = $this->_oSchool->extra_nights_price;
				if($this->_sCalculateType == 'cost'){
					$iNightsOfExtraWeek = $this->_oSchool->extra_nights_cost;
				}
				if($iNightsOfExtraWeek <= 0){
					$iNightsOfExtraWeek = 7;
				}

				$iPrice = $this->getWeekPrice($aExtraWeek['id']);
				
				### DEBUG Ausgaben
				Ext_Thebing_Util::debug('Wochenpreis:'.$iPrice, 'calculateExtraWeek');
				###
							
				### DEBUG Ausgaben
				Ext_Thebing_Util::debug('Nächte der Woche:'.$iNightsOfExtraWeek, 'calculateExtraWeek');
				###

			}

			$oWDDate = new WDDate();

			foreach((array)$aExtraWeeks as $aData){

				$oWDDate->set($aData['from'], WDDate::TIMESTAMP);

				for($i = 1; $i <= $aData['nights']; $i++){

					$aWeekLimits = self::getWeekLimits($oWDDate, $this->oAccommodationCategory, $this->_oSchool->id);
					$this->aWeekPrices[$aWeekLimits['start']] += $iPrice;

					$oWDDate->sub(1, WDDate::WEEK);

				};
			}

		} else {
			
			$iExtraWeeks = $this->_oInquiry->getExtraWeeks('', $this->_oInquiryAccommodation);

			### DEBUG Ausgaben
			Ext_Thebing_Util::debug('Anzahl Extrawochen:'.$iExtraWeeks, 'calculateExtraWeek');
			###
			
			$oPrice = new Ext_Thebing_Price($this->_oSchool, $this->_oSaison, $this->_oCurrency, '', $this->nationality, $this->_oAgency?->getCategory());
			
			if($this->_bAgencyAmount == true){
				$oAgency = Ext_Thebing_Agency::getInstance($this->_oInquiry->agency_id);
				$oAgency->setSchool($this->_oSchool);
				$oPrice->setAgency($oAgency);
			}

			$iPrice = $oPrice->getExtraWeekPrice($this->_oInquiryAccommodation->accommodation_id, $this->_oInquiryAccommodation->roomtype_id, $this->_oInquiryAccommodation->meal_id,false);
		
			### DEBUG Ausgaben
			Ext_Thebing_Util::debug('Extrawochenpreis:'.$iPrice, 'calculateExtraWeek');
			###
			
		}

		$iPrice = $iPrice * $iExtraWeeks;

		### DEBUG Ausgaben
		Ext_Thebing_Util::debug('Extrawochenpreis (gesamt):'.$iPrice, 'calculateExtraWeek');
		###
		
		return $iPrice;
	}

	public function calculateExtraNight($bAgencyAmount = false, $bFullLastWeek=false) {

		$this->_bAgencyAmount = $bAgencyAmount;

		if(!$this->_oInquiry) {
			return 0;
		}

		$bCost = false;
		if($this->_sCalculateType == 'cost'){
			$bCost = true;
		}

		// Keine Zeitpunkte übermitteln!
		// Das kann zu fehlern führen!
		// EDIT: Bei Bezahlung muss was übergeben werden!

		if($this->aCurrencExtraNight === null) {
			if($bCost) {     
				$aExtraNights	= $this->_oInquiry->getExtraNights('forCalculate', $this->_oInquiryAccommodation, $this->_iCalculateStart, $this->_iCalculateEnd, $bCost, $bFullLastWeek);
			} else {        
				$aExtraNights	= $this->_oInquiry->getExtraNights('forCalculate', $this->_oInquiryAccommodation, 0, 0, $bCost, $bFullLastWeek);
			}	
		} else {
			$aExtraNights = array(
				$this->aCurrencExtraNight
			);
		}

		### DEBUG Ausgaben
		Ext_Thebing_Util::debug('Extranächte:', 'calculateExtraNight'); 
		Ext_Thebing_Util::debug($aExtraNights, 'calculateExtraNight');
		###
		$this->aCalculationDescription[] = 'Extra nights';
		
		$iAccommodationId		= (int)$this->_oInquiryAccommodation->accommodation_id;
		$iRoomtypId				= (int)$this->_oInquiryAccommodation->roomtype_id;
		$iMealId				= (int)$this->_oInquiryAccommodation->meal_id;

		$oAgency = null;
		if($this->_bAgencyAmount == true){
			$oAgency = Ext_Thebing_Agency::getInstance($this->_oInquiry->agency_id);
			$oAgency->setSchool($this->_oSchool);
		}

		$iExtra = 0;

		$iTempCalculateStart = $this->_iCalculateStart;

		### DEBUG Ausgaben
		Ext_Thebing_Util::debug('Zeitpunkt:'.strftime('%x', $iTempCalculateStart), 'calculateExtraNight');
		###

		$oWDDate = new WDDate();

		foreach((array)$aExtraNights as $aExtraNight) {

			### DEBUG Ausgaben
			Ext_Thebing_Util::debug('Aktuelle Extranacht:', 'calculateExtraNight');
			Ext_Thebing_Util::debug($aExtraNight, 'calculateExtraNight');
			###
			
			$oWDDate->set($aExtraNight['from'], WDDate::TIMESTAMP);

			$this->_iCalculateStart = $oWDDate->get(WDDate::TIMESTAMP);
			$this->setSaison();
			$oSaison 				= $this->_oSaison;
			
			### DEBUG Ausgaben
			Ext_Thebing_Util::debug('Saison:', 'calculateExtraNight');
			Ext_Thebing_Util::debug($oSaison, 'calculateExtraNight');
			###
			
			if(!$oSaison) {
				continue;
			}

			$iPrice					= 0;
			if($this->_sCalculateType == 'cost'){
				$iPrice		= $this->getWeekPrice(-1);
				
				### DEBUG Ausgaben
				Ext_Thebing_Util::debug('Preis (Kosten):'.$iPrice, 'calculateExtraNight');
				###
				
			} else {
				$oPrice 	= new Ext_Thebing_Price($this->_oSchool->id, $this->_oSaison, $this->_oCurrency, '', $this->nationality, $this->_oAgency?->getCategory());
				$iPrice 	= $oPrice->getExtraNightPrice($aExtraNight['accommodation_id'], $aExtraNight['roomtype_id'], $aExtraNight['meal_id'], null, false);
				
				### DEBUG Ausgaben
				Ext_Thebing_Util::debug('Preis:'.$iPrice, 'calculateExtraNight');
				###

				if($this->_bAgencyAmount){
					$oAgency			->setSaison($this->_oSaison);
					$oSchoolProvision 	= $oAgency->getSchoolProvisions($this->_oSaison->getSaisonId());
					$oPrice				->setAgency($oAgency);

					$oProvision = $oSchoolProvision->getExtraNightProvision($iAccommodationId, $iRoomtypId, $iMealId);
					if ($oProvision) {
						$iPrice = $iPrice - $oProvision->calculate((float)$iPrice);
					}
				}
				
				### DEBUG Ausgaben
				Ext_Thebing_Util::debug('Preis (Agentur):'.$iPrice, 'calculateExtraNight');
				###
			}

			$this->aCalculationDescription[] = $aExtraNight['nights'].' * '.$iPrice;
			
			$iExtra += $aExtraNight['nights'] * $iPrice;
			$iExtraCurrent = $aExtraNight['nights'] * $iPrice;
	
			### DEBUG Ausgaben
			Ext_Thebing_Util::debug('Preis (Summe) :'.$iExtraCurrent, 'calculateExtraNight');
			###
			
			$iTempNights = $aExtraNight['nights'];

			do{

				$aWeekLimits = self::getWeekLimits($oWDDate, $this->oAccommodationCategory, $this->_oSchool->id);

				$this->aWeekPrices[$aWeekLimits['start']] += $iPrice;

				$oWDDate->sub(1, WDDate::DAY);
				$iTempNights--;

			} while ($iTempNights > 0);


		}

		$this->_iCalculateStart = $iTempCalculateStart;

		$iFinal = $iExtra;

		### DEBUG Ausgaben
		Ext_Thebing_Util::debug('Preise je Woche :', 'calculateExtraNight');
		Ext_Thebing_Util::debug($this->aWeekPrices, 'calculateExtraNight');
		###
		
		### DEBUG Ausgaben
		Ext_Thebing_Util::debug('Gesammtpreis :'.$iExtra, 'calculateExtraNight');
		###

		return $iFinal;
	}

	public function calculateWeek($iStartWeek = 0){

		// Auf Feriensplittung prüfen!
		// Nur bei nicht foltlaufend nötig also wenn keine start woche angegeben ist
		if(
			$iStartWeek <= 0 &&
			$this->_oInquiryAccommodation &&
			$this->_oInquiryAccommodation instanceof Ext_TS_Inquiry_Journey_Accommodation
			//$this->_oInquiryAccommodation->id
		) {

			$iTempAllOtherWeeks = 0;
			$iTempAllWeeksBefore = 0;

			// Analog zu Kursen: Feriensplittung beachten für korrekte Preiswoche
			$aRelatedAccommodations = $this->_oInquiryAccommodation->getRelatedServices();
			$aRelatedAccommodations = array_filter($aRelatedAccommodations, function(Ext_TS_Inquiry_Journey_Accommodation $oJourneyAccommodation) {
				return $oJourneyAccommodation !== $this->_oInquiryAccommodation;
			});

			foreach($aRelatedAccommodations as $oTempInquiryAccommodation) {

				if($oTempInquiryAccommodation->active != 1){
					continue;
				}
				
				$iTempAllOtherWeeks += $oTempInquiryAccommodation->weeks;
				$oWDDate = new WDDate($this->_oInquiryAccommodation->from, WDDate::DB_DATE);
				if(
					$oWDDate->compare($oTempInquiryAccommodation->from, WDDate::DB_DATE) == 1
				){
					$iTempAllWeeksBefore += $oTempInquiryAccommodation->weeks;
				}
			}

			if($this->_oSchool->price_structure_week == 0){
				$iStartWeek = $iTempAllWeeksBefore;
			} else if($this->_oSchool->price_structure_week == 1){
				$iStartWeek = $iTempAllOtherWeeks;
			}

		}
		
		$fAmount		= 0;
		$aPriceFinal	= array();

		$iWeeks			= $this->_iWeeks + $iStartWeek;
		$iWeekCount		= 1;
		$iWeekCount		= $iWeekCount + $iStartWeek;
		$iRemainingStartWeeks = $iStartWeek;

		//Gibt die Tatsächliche Woche an die gerade berechnet wird
		$iCurrentAccommodationWeek		= 0;

		// Util Klasse aufbauen ( hat diverse methoden um an wochen etc. einer Unterkunft zu kommen )
		$oAccommodationUtil	= new Ext_Thebing_Accommodation_Util($this->_oSchool);
		$oAccommodationUtil->setAccommodationCategorie($this->_iAccommodationCategoryId);
		$oAccommodationUtil->setRoomtypeById($this->_iRoomtypeId);
		$oAccommodationUtil->setMealById($this->_iMealId);

		$iCalculateStartOrginal = $this->_iCalculateStart;

		$bForCost = false;
		if($this->_sCalculateType == 'cost') {
			$bForCost = true;
		}

		// Inquiry/Enquiry ist nicht unbedingt gesetzt (ob das eigentlich ein Fehler ist, ist die andere Frage)
		$iExtraNightsBefore = 0;
		if($this->_oInquiry instanceof Ext_TS_Inquiry_Abstract) {
			$aExtraNights = $this->_oInquiry->getExtraNightsWithWeeks('forCalculate', $this->_oInquiryAccommodation, 0, 0, $bForCost);
			foreach($aExtraNights as $aExtraNight) {
				if($aExtraNight['type'] == 'nights_at_start') {
					$iExtraNightsBefore += $aExtraNight['nights'];
				}
			}
		}

		// Wochenstart setzen!
		// Berechnung muss immer nach den Unterkunftwochen gehen!
		$oWDDate = new WDDate($this->_iCalculateStart, WDDate::TIMESTAMP);

		// Wenn Extranächte vorhanden sind, dann muss die Startzeit für den Wochendurchlauf entsprechend korrigiert werden
		// Extranächte werden nicht in dieser Methode berechnet, das sind auch eigene Positionen!
		if($iExtraNightsBefore != 0) {
			$oWDDate->add($iExtraNightsBefore, WDDate::DAY);
			$this->_iCalculateStart = $oWDDate->get(WDDate::TIMESTAMP);

			$this->setSaison();

			// Fehler abfangen
			if(!is_object($this->_oSaison)) {
				return 0;
			}
		}

		$aWeekLimits = self::getWeekLimits($oWDDate, $this->oAccommodationCategory, $this->_oSchool->id);

		// Variable auf letzten Sa/So setzen (vorher: Starttag der Unterkunft)
		$this->_iCalculateStart = $aWeekLimits['start'];

		// -------------------------------------------------------------------------------------

		$aPriceStructureWeeks	= Ext_Thebing_School_Gui2::getPriceStructureWeeks();
		$aCalculationDescription = array();

		################################
		## KOSTEN:                    ##
		## FESTGEHALT + WOCHE         ##
		################################
		if(
			$this->_sCalculateType == 'cost' &&
			$this->_sCalculateModel == 'week' &&
			!$this->_oCostCategory
		) {		
			$fAmount = $this->_aSalary['salary'];
			for($i = $iWeekCount; $i <= $iWeeks; $i++){
				$oWDDate = new WDDate();
				$oWDDate->set($this->_iCalculateStart, WDDate::TIMESTAMP);
				$oWDDate->add(1, WDDate::WEEK);
				$iWeekDate = $oWDDate->get(WDDate::TIMESTAMP);
				$this->aWeekPrices[$iWeekDate] = $fAmount;
				$aPriceFinal[$iWeekDate] = $fAmount;
			}
		################################
		## KOSTEN / PREISE            ##
		## Preisberechnung wie bisher ##
		## "Normale Preisstruktur     ##
		################################
		} else if(
			// Preisberechnung + Preis je woche
			(
				$this->_sCalculateType != 'cost' &&
				$this->_oSchool &&
				$this->_oSchool->price_structure_week == 0
			) ||
			// Kostenberechnung + Kosten je woche
			(
				$this->_sCalculateType == 'cost' &&
				$this->_oCostCategory &&
				$this->_oCostCategory->cost_type == 'periods'
			)
		) { // NPS

			$aCalculationDescription[] = $aPriceStructureWeeks[0];

			$bSaisionChanged = false;
			$iLastSaisionWeek = 0;
			$iLastPrice = 0;
			$aExtraWeekCount = array();
			$iPriceFromLastWeekOfLastSaisionForCurrentSaision = 0;

			// letzte Saison ist die Aktuelle da wir gerade erst starten :)
			$oLastSaison = $this->_oSaison;

			$iLastPriceOfWeek = 0;
			$aWeekPriceCount = array();
			$iTempWeekPriceCount = 0;
			$iExtraCountForSaison = 0;

			// Wochen durchlaufen
			$iSetPrice = 0;
			for($i = $iWeekCount; $i <= $iWeeks; $i++){

				$aCalculation[$iSetPrice] = Ext_Thebing_Format::LocalDate($this->_oSaison->valid_from).' - '.Ext_Thebing_Format::LocalDate($this->_oSaison->valid_until).': ';

				// Fehler zwischenspeichern
				$aErrorsCache = $this->aErrors;
				
				// Startwochen zwischenspeichern
				$iRemainingStartWeeksCache = $iRemainingStartWeeks;

				// Gerade zu berechnende Woche DIESES Kurses
				$iCurrentAccommodationWeek++;
				
				// Wenn keine Saison gefunden wird kann nichts berechnet werden
				if(
					!$this->_oSaison ||
					 $this->_oSaison->getSaisonId() <= 0
				){

					// Fehlerarray fullen damit ein Fehler in der buchungsmaske sichtbar wird
					$this->setSaisonError();

					// Nächste Saison setzen!
					//Rechne 7 Tage hinzu um festzustellen ob die kommende Woche eine neue Saison ist
					$oWDDate = new WDDate();
					$oWDDate->set($this->_iCalculateStart, WDDate::TIMESTAMP);
					$oWDDate->add(1, WDDate::WEEK);
					$iNextWeekStart = $oWDDate->get(WDDate::TIMESTAMP);
					// Hole die neue aktuelle Saison
					$this->_iCalculateStart = $iNextWeekStart;
					$this->setSaison();
					continue;
				} else {
					// saison gefunden (nur zur Info im Rechnungsdialog)
					$this->setSeasonFound();
				}

				$iTempWeekPriceCount++;

				$iSaisonId = $this->_oSaison->getSaisonId();
				$iPrice = 0;
				$iPriceOfWeek = 0;

				// Wenn $iStartWeek > 0 => gibt es einen vorgänger kurs
				$aWeekForDiff = array();
				if($iStartWeek > 0) {
				    $aWeekForDiff = $this->getWeekByNumber($iStartWeek, $oAccommodationUtil);
				}

				// Aktuelle Woche
				$aWeek = $this->getWeekByNumber($i, $oAccommodationUtil);
				$aLastWeek = $this->getWeekByNumber($iLastSaisionWeek, $oAccommodationUtil);

                if(isset($aWeek['extraWeek']) && empty($aWeek['extraWeek'])){
                    // in den cache da in diesem Fall die woche übersprungen wird und der check wieder eingespielt wird
                    $aErrorsCache['accommodation_week_not_found'][] = (int)$this->_oInquiryAccommodation->id;	
                    $this->aErrors['accommodation_week_not_found'][] = (int)$this->_oInquiryAccommodation->id;	
                }
                
				$iTemp = 0;

				// Wenn eine Woche vorhanden ist
				if(!empty($aWeek)){

					// Wenn sich die Saison ändert
					if(
						$bSaisionChanged //&&
						//$iLastSaisionWeek > 0 // Auskommentiert da es sonst nicht klappt wenn wir nur extra wochen haben + saison wechsel
					) {

						// Der Preis der letzten Saison und der dort benutzen Woche ausrechnen
						/**
						 * Preis der Wochenanzahl der letzten Saison mit Preisen der aktuellen Saison berechnen
						 */
						$iPriceFromLastWeekOfLastSaisionForCurrentSaision = $this->getWeekPrice($aLastSeasonWeek['id']);
						$sPriceFromLastWeekOfLastSaisionForCurrentSaision = $iPriceFromLastWeekOfLastSaisionForCurrentSaision.' ('.$aLastSeasonWeek['start_week'].'W)';;


						// Aktueller Preis ermitteln
						$iPriceNow = $this->getWeekPrice($aWeek['id']);
						$sPriceNow = $iPriceNow.' ('.$aWeek['start_week'].'W)';

						// Wenn wir nun in einer Extra woche sind
						// muss anderst gerechnet werden
						if($aWeek['extraWeek']) {

							#$iExtraCountForSaison++;

							// Anzahl Extrawochen in der nächsten Saison
							#$iExtraWeeks = ($iTempWeekPriceCount - $aWeek['start_week']);
							#$iExtraWeeks = $iExtraCountForSaison;
							

							// Anzahl der Extra wochen berechnen
							$aExtraWeekCount[$iSaisonId]	= ($i - $aWeek['start_week']);
							
							// Extraprochenpreis
							$iExtraWeekPrice				= $this->getWeekPrice($aWeek['extraWeek']['id']);

							// Wochenpreis
							$iTempPrice	= $this->getWeekPrice($aWeek['id']);

							$sPriceNow = $iTempPrice.' ('.$aWeek['start_week'].'W) + ('.$iExtraWeekPrice.' * '.$aExtraWeekCount[$iSaisonId].'EW)';
							$iPriceNow = $iTempPrice + ($iExtraWeekPrice * $aExtraWeekCount[$iSaisonId]);

							// Preis + Extrawochenreis * anzahl der Extra wochen DIESER Saison
							#$sPriceNow = '('.$iExtraWeekPrice.' * '.$iExtraWeeks.') + '.$iPriceNow.' ('.$aWeek['start_week'].'W)';

							#$iPriceNow						=  ($iExtraWeekPrice * $iExtraWeeks) + $iPriceNow;//$iExtraWeekPrice * $iTempWeekPriceCount;
							// Preis der Letzten woche der letzten Saison anpassen, Extrawochenpreis aller Extrawochen der LETZTEN Saison muss hinzuadiert werden
							//$iPriceFromLastWeekOfLastSaisionForCurrentSaision = 0;

							// Preis der Letzten woche der letzten Saison anpassen, Extrawochenpreis aller Extrawochen der LETZTEN Saison muss hinzuadiert werden
							$sPriceFromLastWeekOfLastSaisionForCurrentSaision .= ' + ('.$iExtraWeekPrice.' * '.(int)$aExtraWeekCount[$oLastSaison->getSaisonId()].'EW)';
							$iPriceFromLastWeekOfLastSaisionForCurrentSaision = $iPriceFromLastWeekOfLastSaisionForCurrentSaision + ((int)$aExtraWeekCount[$oLastSaison->getSaisonId()] * $iExtraWeekPrice);
						}

						$aCalculation[$iSetPrice] .= $sPriceNow.' - '.$sPriceFromLastWeekOfLastSaisionForCurrentSaision;
						
						// Aktueller Preis minus Preis der letzten Woche der letzten Saison ( und dabei auch extrawochen DIESER Saison abzüglich extrawochen der LETZTEN Saison )
						$iPrice = $iPriceNow - $iPriceFromLastWeekOfLastSaisionForCurrentSaision;
						$iPriceOfWeek = $iPrice;

					// Wenn sich die Saison NICHT ändert
					} else {

						// Wenn extra Wochen
						if($aWeek['extraWeek']) {
							// Anzahl Extra Wochen in dieser Saison
							$iExtraWeekCount				= ($i - $aWeek['start_week']);

							if($iExtraWeekCount <= 0) {
								$iExtraWeekCount = 1;
							}
							$aExtraWeekCount[$iSaisonId]	= $iExtraWeekCount;
							// Extrawochenpreis
							$iExtraWeekPrice				= $this->getWeekPrice($aWeek['extraWeek']['id']);
							
							// Normalerpreis
							$iTempPrice						= $this->getWeekPrice($aWeek['id']);

							// Preis errechnen
							$aCalculation[$iSetPrice] .= $iTempPrice.' + '.$iExtraWeekPrice.' * '.$aExtraWeekCount[$iSaisonId].'EW';
							$iPrice							= $iTempPrice + $iExtraWeekPrice * $aExtraWeekCount[$iSaisonId];
							$iPriceOfWeek					= $iExtraWeekPrice;
							
						// Wenn keine Extra Woche
						} else {
							// Hole den Preis							
							$iPrice = $this->getWeekPrice($aWeek['id']);
							$aCalculation[$iSetPrice] .= $iPrice.' ('.$aWeek['start_week'].'W)';
							$iPriceOfWeek = $iPrice;
						}

					}

				}

				// Wenn $iLastWeekOfLastAcco > 0 => gibt es einen vorgänger kurs
				if(
					$iStartWeek > 0 &&
					$iRemainingStartWeeks > 0
				) {
					
					$aCalculation[$iSetPrice] .= ', SW: '.$iStartWeek; 
					
					if($this->_sCalculateType == 'cost') {

						if(!$aWeekForDiff['extraWeek']) {
							/*
							 * Wenn die aktuelle Woche eine Extrawoche ist und die Startwoche nicht, 
							 * dann muss genau eine Extrawoche abgzogen werden
							 */
							if($aWeek['extraWeek']) {
								$iPriceForDiff = $this->getWeekPrice($aWeek['id']);
								$aCalculation[$iSetPrice] .= ', EWK: '.$iPriceForDiff;
								$iPriceForDiff = $iPriceForDiff;
							} else {
								$iPriceForDiff = $this->getWeekPrice($aWeekForDiff['id']);
							}
						} else {

							$iPriceForDiff = $this->getWeekPrice($aWeekForDiff['extraWeek']['id']);

							if($aWeek['extraWeek']) {	

								// Bei Saisonwechsel darf man nicht mit allen Wochen rechnen, sondern darf nur die Wochen bis zum Saisonwechsel berücksichtigen
								$iDiffWeekCount = ($i - $iStartWeek);

								$aCalculation[$iSetPrice] .= ', '.$iPrice.' - ('.$iDiffWeekCount.' * '.$iPriceForDiff.')';
							
								// Preis für Zeitraum bis Startwoche errechnen
								$iPriceForDiff = $iPrice - ($iDiffWeekCount * $iPriceForDiff);

								$aCalculation[$iSetPrice] .= ', KORREKTUR: '.$iPriceForDiff;
								
							} else {

								/*
								 * Da bei der Berechnung NUR die Extrawochen genommen werden, falls es 
								 * eine Startwoche gibt, muss auch nur die relative Anzahl der Wochen bis 
								 * zu Startwoche genommen werden
								 */
								if(
									!empty($aExtraWeekCount[$iSaisonId])
								) {
									$iWeekMultiplicator = $iStartWeek - $aWeekForDiff['start_week'] + 1;
								} else {							
									$iWeekMultiplicator = $iStartWeek;
								}

								$aCalculation[$iSetPrice] .= ', EW: '.$iPriceForDiff.' * '.$iWeekMultiplicator;
								$iPriceForDiff = $iPriceForDiff * $iWeekMultiplicator;
							}
						}
						
					} else {
						// Preis für die Wochenanzahl der letzten Unterkunft holen
						if(!$aWeekForDiff['extraWeek']) {
							$iPriceForDiff = $this->getWeekPrice($aWeekForDiff['id']);
						} else {
							$iPriceForDiff = $this->getWeekPrice($aWeekForDiff['extraWeek']['id']);
							$aCalculation[$iSetPrice] .= ', EW: '.$iPriceForDiff.' * '.$iStartWeek;
							$iPriceForDiff = $iPriceForDiff * $iStartWeek;
						}
					}

				    // aktueller Preis abzüglich Preis für Wochenanzahl des letzten Kurses
					$aCalculation[$iSetPrice] .= ', '.$iPrice.' - '.$iPriceForDiff;
				    $iPrice = $iPrice - $iPriceForDiff;

					$iPriceOfWeek	= $iPrice;

					// Startwochen dürfen nur einmal substrahiert werden
					$iRemainingStartWeeks -= $iStartWeek;

				}

				$iPriceOfWeekTemp	= $iPriceOfWeek;
				// Wenn KEINE extra Wochen
				if(!$aWeek['extraWeek']){
					$iPriceOfWeek		= $iPriceOfWeek - $iLastPriceOfWeek;
				}
				$iLastPriceOfWeek	= $iPriceOfWeekTemp;

				// Wenn Agenturbertag dann muss die Provision abgezogen werden
				if($this->_bAgencyAmount == true){
					$oSchoolProvision = $this->_oAgency->getSchoolProvisions($this->_oSaison->getSaisonId());

					$oProvision = $oSchoolProvision->getAccommodationProvision($this->_iAccommodationCategoryId, $this->_iRoomtypeId, $this->_iMealId);

					if ($oProvision) {
						$iPrice = $iPrice - $oProvision->calculate((float)$iPrice);
					}
				}   

				$oWDDateTemp = new WDDate($this->_iCalculateStart);
				$aTemp = self::getWeekLimits($oWDDateTemp, $this->oAccommodationCategory, $this->_oSchool->id);
				// Wochenpreis merken, falls man später sehen möchte welcher preis für welche Woche errechnet wurde
				// Sehr wichtig für Special!
				$this->aWeekPrices[$aTemp['start']] = $iPriceOfWeek;

				//wenn man $oWDDateTemp in die getWeekLimits übergibt, wird es irgendwie als referenz behandelt und das 
				//datum in der wddate klasse ändert sich, darum setzten wir es hier erneut
				$oWDDateTemp = new WDDate($this->_iCalculateStart);

				if($i == $iWeeks) {

					$aWeekPriceCount[$aTemp['start']]['count'] = $iTempWeekPriceCount;
					$aWeekPriceCount[$aTemp['start']]['amount'] = $iPrice;

					$iTempWeekPriceCount = 0;

					$aPriceFinal[] = $iPrice;
					$iSetPrice++;
					break;
				}

				//Rechne 7 Tage hinzu um festzustellen ob die kommende Woche eine neue Saison ist
				$oWDDateTemp->add(1, WDDate::WEEK);
				$iNextWeekStart = $oWDDateTemp->get(WDDate::TIMESTAMP);
				
				// Hole die neue aktuelle Saison
				$this->_iCalculateStart = $iNextWeekStart;
				$oLastSaisonTemp = $this->_oSaison;
				$this->setSaison();

				if(!$this->_oSaison) {
					$this->setSaisonError();
					break;
				} else {
					// saison gefunden (nur zur Info im Rechnungsdialog)
					$this->setSeasonFound();
				}

				// Wenn die kommende Saision nicht die aktuelle ist Speicher die Id der Woche
				if($iSaisonId != $this->_oSaison->getSaisonId()) {

					$oLastSaison		= $oLastSaisonTemp;
					$aLastSeasonWeek	= $aWeek;

					$iLastSaisionWeek	= $aWeek['id'];
					// bei Saison Wechsel wird der Preis in das Price Array geschrieben
					$aPriceFinal[]		= $iPrice;
					$iSetPrice++;

					$aWeekPriceCount[$aTemp['start']]['count'] = $iTempWeekPriceCount;
					$aWeekPriceCount[$aTemp['start']]['amount'] = $iPrice;

					$iTempWeekPriceCount = 0;
					$iExtraCountForSaison = 0;

					$iLastPrice			= $iPrice;
					$bSaisionChanged	= true;

				} else {

					// Extrawochen Cache muss geleert werden, wenn die Woche nicht relevant ist
					unset($aExtraWeekCount[$oLastSaisonTemp->getSaisonId()]);

					// Woche wird übersprungen, 
					// Fehler aus Cache laden
					$this->aErrors = $aErrorsCache;

					// Startwochen aus Cache
					$iRemainingStartWeeks = $iRemainingStartWeeksCache;

				}

			}

			$aWeekPrices = array();
			foreach((array)$aWeekPriceCount as $iTempWeek => $aTemp){
				foreach((array)$this->aWeekPrices as $iWeekPriceTime => $fWeekPrice){
					if($iWeekPriceTime <= $iTempWeek){
						$aWeekPrices[$iWeekPriceTime] = ( $aTemp['amount'] / $aTemp['count'] ) ;
						unset($this->aWeekPrices[$iWeekPriceTime]);
					}
				}
			}

			$this->aWeekPrices = $aWeekPrices;

			// Special berechnung
			$iTempCount = 1;
			foreach((array)$this->aWeekPrices as $iTempTimestamp => $fAmountWeek){
				// Special: Berechnen ob der preis ge-specialt werden muss
				$oSpecialAmount = new Ext_Thebing_Inquiry_Special_Amount(array($iTempTimestamp => $fAmountWeek), $this->_oInquiryAccommodation, $iTempCount);
				$oSpecialAmount->setCalculationType('weekly');
				$fSpecialAmount = $oSpecialAmount->getAmount();

				$this->aSpecialCalculationDescription = array_merge($this->aSpecialCalculationDescription, $oSpecialAmount->getCalculation());

				if($fSpecialAmount > 0) {
					$this->_aSpecialBlocks = array_merge($this->_aSpecialBlocks, $oSpecialAmount->getBlocks());
					if($this->_bAgencyAmount == true) {
						$this->_fSpecialAmountNetto += $fSpecialAmount;
					} else {
						$this->_fSpecialAmount += $fSpecialAmount;
					}
				}
				$iTempCount++;
			}
			
		################################
		## KOSTEN / PREISE            ##
		## je Zeitraum                ##
		## "Preis pro Woche"		  ##
		################################
		} else if(
			// Preisberechnung + Preis je Zeitraum
			(
				$this->_sCalculateType != 'cost' &&
				$this->_oSchool &&
				$this->_oSchool->price_structure_week == 1
			) ||
			// Kostenberechnung + Kosten je Zeitraum
			(
				$this->_sCalculateType == 'cost' &&
				$this->_oCostCategory &&
				$this->_oCostCategory->cost_type == 'week'
			)
		) { // PPW

			$aCalculationDescription[] = $aPriceStructureWeeks[1];
			
			$aTemp = array();
			$t = 1;
			$aSpecialTemp = array();
			$iPrice = 0;
			$iPriceTemp = 0;
			$iPriceSpecialBrutto = 0;
			$bOnlyExtraWeekCalculation = false; // Überspringt die Wochenenweise Berechnung

			// LETZTE Woche suchen um zu schauen ob ExtraWoche
			$aWeek = $aLastWeek = $this->getWeekByCount($iWeeks, $oAccommodationUtil);

			$iCurrentPrice		= $this->getWeekPrice($aWeek['id']);

			$iCurrentExtraWeekPrice = 0;
			if(!empty($aLastWeek['extraWeek'])) {

				// preis holen
				$iCurrentExtraWeekPrice = $this->getWeekPrice($aLastWeek['extraWeek']['id']);

			}

			// Wochen durchlaufen
			$iSetPrice = 0;
			for($i = $iWeekCount; $i <= $iWeeks; $i++){

				$aCalculation[$iSetPrice] = Ext_Thebing_Format::LocalDate(new \DateTime($this->_oSaison->valid_from_mysql)).' - '.Ext_Thebing_Format::LocalDate(new \DateTime($this->_oSaison->valid_until_mysql)).': ';

				// Wenn letzte Woche ExtraWoche
				if($iCurrentExtraWeekPrice > 0){
					// preis holen
					$iPrice		= $iCurrentExtraWeekPrice;
				} else {
					// preis holen
					$iPrice		= $iCurrentPrice;
				}

				// Prüfen ob Saison gültig ist
				if(
					!$this->_oSaison ||
					$this->_oSaison->getSaisonId() <= 0
				){
					$this->setSaisonError();
					
					// Nächste Saison setzen!
					//Rechne 7 Tage hinzu um festzustellen ob die kommende Woche eine neue Saison ist
					$oWDDate = new WDDate((int)$this->_iCalculateStart);
					$oWDDate->set('00:00:00', WDDate::TIMES);
					$oWDDate->add(1, WDDate::WEEK);
					$iNextWeekStart = $oWDDate->get(WDDate::TIMESTAMP);
					// Hole die neue aktuelle Saison
					$this->_iCalculateStart = $iNextWeekStart;
					$this->setSaison();
					
					$iCurrentPrice		= $this->getWeekPrice($aWeek['id']);

                        
					$iCurrentExtraWeekPrice = 0;
					if(!empty($aLastWeek['extraWeek'])){
						// preis holen
						$iCurrentExtraWeekPrice = $this->getWeekPrice($aLastWeek['extraWeek']['id']);
					}
					
					continue;

				} else {
					// saison gefunden (nur zur Info im Rechnungsdialog)
					$this->setSeasonFound();
				}
				
				// Gerade zu berechnende Woche DIESES Kurses
				$iCurrentAccommodationWeek++;

				$iLastWeek = $this->_iCalculateStart;
				$oWDDateTemp = new WDDate($iLastWeek);
				$aTemp = self::getWeekLimits($oWDDateTemp, $this->oAccommodationCategory, $this->_oSchool->id);

				// preis zwischenspeichern
				$iPriceTemp = $iPrice;
				// Special errechnen
				if($this->_oInquiry instanceof Ext_TS_Inquiry){

					// Special: Berechnen ob der preis ge-specialt werden muss
					$oSpecialAmount = new Ext_Thebing_Inquiry_Special_Amount(array($aTemp['start'] => $iPrice), $this->_oInquiryAccommodation, $iCurrentAccommodationWeek);
					$oSpecialAmount->setCalculationType('weekly');
					$fSpecialAmount = $oSpecialAmount->getAmount();

					$this->aSpecialCalculationDescription = array_merge($this->aSpecialCalculationDescription, $oSpecialAmount->getCalculation());

					$iPriceSpecialBrutto	= $fSpecialAmount;
					$this->_aSpecialBlocks = array_merge($this->_aSpecialBlocks, $oSpecialAmount->getBlocks());
					// Special zeitpunkt merken, wenn ein special gefunde wurde
					#if($fSpecialAmount > 0){
					#	$aSpecialTemp[$t] = $this->_iCalculateStart;
					#}
					
					if($this->_bAgencyAmount == true){
						$this->_fSpecialAmountNetto += $fSpecialAmount;
					}else{
						$this->_fSpecialAmount += $fSpecialAmount;
					}

				}

				if(
					$this->_bAgencyAmount == true &&
					$this->_oAgency
				) {
					$oSchoolProvision = $this->_oAgency->getSchoolProvisions($this->_oSaison->getSaisonId());

					$oProvision = $oSchoolProvision->getAccommodationProvision($this->_iAccommodationCategoryId, $this->_iRoomtypeId, $this->_iMealId);

					if ($oProvision) {
						$iPrice = $iPrice - $oProvision->calculate((float)$iPrice);
					}
				}

				//Rechne 7 Tage hinzu um festzustellen ob die kommende Woche eine neue Saison ist
				$oDate = new WDDate((int)$this->_iCalculateStart);
				$oDate->set('00:00:00', WDDate::TIMES);
				$iWeekDay = $oDate->add(1, WDDate::WEEK);
				$iNextWeekStart = $oDate->get(WDDate::TIMESTAMP);

				// Hole die neue aktuelle Saison
				$this->_iCalculateStart = $iNextWeekStart;

				$oLastSaison = $this->_oSaison;
				$this->setSaison();

				// Prüfen ob Saison gültig ist
				if(
					!$this->_oSaison ||
					$this->_oSaison->getSaisonId() <= 0
				){
					$this->setSaisonError();
					continue;
				}else{
					// saison gefunden (nur zur Info im Rechnungsdialog)
					$this->setSeasonFound();
				}

				// Wenn sich die Saison wechselt
				if(
					$this->_oSaison->getSaisonId() != $oLastSaison->getSaisonId() ||
					$i == $iWeeks
				){

					// Bisherige wochen nochmal durchlaufen
					for($a = 1; $a <= $t; $a++){ 

						$iPriceSpecial = $iPrice;

						$aPriceFinal[] = $iPriceSpecial;
						$iSetPrice++;

					}

					// Wechsel daher für die nächste Wochen neuer Preis errechnen
					if(!empty($aLastWeek['extraWeek'])){
						// preis holen
						$iCurrentExtraWeekPrice = $this->getWeekPrice($aLastWeek['extraWeek']['id']);
					}
					
					$iCurrentPrice		= $this->getWeekPrice($aWeek['id']);

					$t = 1;
				} else {
					$t++;
				}

                        
				$oWDDateTemp = new WDDate($iLastWeek);
				$aTemp = self::getWeekLimits($oWDDateTemp, $this->oAccommodationCategory, $this->_oSchool->id);
				$this->aWeekPrices[$aTemp['start']] = $iPrice;
			}

			if(count($aPriceFinal) <= 0){
				$aPriceFinal[] = $iPrice * $iWeeks;
				$iSetPrice++;
			}

			// Wenn es nur Extrawochen gibt werden diese einfach folgendermaßen berechnen
			if($bOnlyExtraWeekCalculation) {

				$aPriceFinal = array();

				// Wochenstart ermitteln
				$oWDDateTemp = new WDDate($this->_iCalculateStart);

				// Wochen durchgehen und pro Woche den Preis setzten
				for($w = 1; $w <= $iWeeks; $w++){
					// Wochenstart
					$aWeekLimits = self::getWeekLimits($oWDDateTemp, $this->oAccommodationCategory, $this->_oSchool->id);
					$this->aWeekPrices[$aWeekLimits['start']] += $iCurrentExtraWeekPrice;
					$oWDDateTemp->add(1, WDDate::WEEK);
				};

				// Gesamtpreis array füllen
				$iCalculateWeeks = ($iWeeks-$iStartWeek);
				$iPriceTemp2 = $iCurrentExtraWeekPrice * $iCalculateWeeks;
				$aCalculation[$iSetPrice] .= $iCurrentExtraWeekPrice.' * '.$iCalculateWeeks.'W';

				// Netto ausrechnen
				if(     
					$this->_bAgencyAmount == true &&
					$this->_oAgency &&
					!is_null($this->_oSaison)
				){
					$oSchoolProvision = $this->_oAgency->getSchoolProvisions($this->_oSaison->getSaisonId());

					$oProvision = $oSchoolProvision->getAccommodationProvision($this->_iAccommodationCategoryId, $this->_iRoomtypeId, $this->_iMealId);

					if ($oProvision) {
						$iPriceTemp2 = $iPriceTemp2 - $oProvision->calculate((float)$iPriceTemp2);
					}
				}

				// Special das Wochenweise berechnet wird muss hier auch berechnet werden können
				for($iTempCount = 1; $iTempCount <= $iWeeks; $iTempCount++){

					// Wochenweise Special berechnen
					// Special: Berechnen ob der preis ge-specialt werden muss
					$oSpecialAmount = new Ext_Thebing_Inquiry_Special_Amount(array($iTempCount => $iCurrentExtraWeekPrice), $this->_oInquiryAccommodation, $iTempCount);
					$oSpecialAmount->setCalculationType('weekly');
					$fSpecialAmount = $oSpecialAmount->getAmount();

					$this->aSpecialCalculationDescription = array_merge($this->aSpecialCalculationDescription, $oSpecialAmount->getCalculation());

					if($fSpecialAmount > 0){
						$this->_aSpecialBlocks = array_merge($this->_aSpecialBlocks, $oSpecialAmount->getBlocks());
						if($this->_bAgencyAmount == true){
							$this->_fSpecialAmountNetto += $fSpecialAmount;
						}else{
							$this->_fSpecialAmount += $fSpecialAmount;
						}
					}

				}
				
				$aPriceFinal[] = $iPriceTemp2;
				$iSetPrice++;

			}           
                        
		}
		
		$aCalculation = array_merge((array)$aCalculationDescription, array('line'), (array)$aCalculation);

		$this->aCalculationDescription = array_merge((array)$this->aCalculationDescription, (array)$aCalculation);

		$fAmount = array_sum($aPriceFinal);

		$this->_iCalculateStart = $iCalculateStartOrginal;

		// Special: Einmaliger Fixer Rabatt auf Unterkunft
		if($this->_sCalculateType != 'cost') {
			$oSpecialAmount = new Ext_Thebing_Inquiry_Special_Amount($this->aWeekPrices, $this->_oInquiryAccommodation);
			$fSpecialAmount = $oSpecialAmount->getAmount();

			$this->aSpecialCalculationDescription = array_merge($this->aSpecialCalculationDescription, $oSpecialAmount->getCalculation());

			if($fSpecialAmount > 0){
				$this->_aSpecialBlocks = array_merge($this->_aSpecialBlocks, $oSpecialAmount->getBlocks());
				if($this->_bAgencyAmount == true){
					$this->_fSpecialAmountNetto += $fSpecialAmount;
				}else{
					$this->_fSpecialAmount += $fSpecialAmount;
				}
			}
		}

		return $fAmount;

	}

	public function calculateNight($iStartWeek) {

		$fAmount = 0;

		if(!$this->_oSaison) {
			return 0;
		}

		$oPrice 	= new Ext_Thebing_Price($this->_oSchool->id, $this->_oSaison, $this->_oCurrency, '', $this->nationality, $this->_oAgency?->getCategory());
		$oWDDate	= new WDDate();

		$oAccommodationUtil	= new Ext_Thebing_Accommodation_Util($this->_oSchool);
		$oAccommodationUtil->setAccommodationCategorie($this->_iAccommodationCategoryId);

		$aCalculationDescription = array();
		
		// Berechnung der Nächte
		for($i = $this->_iCalculateStart; $i < $this->_iCalculateEnd; $i = strtotime('+1 day', $i)){
			$fAmountPart = 0;

			$oWDDate->set($i, WDDate::TIMESTAMP);
			$oWDDate->set('00:00:00', WDDate::TIMES);

			$i = $oWDDate->get(WDDate::TIMESTAMP);
			$sDate = $oWDDate->get(WDDate::DB_DATE);

			// Saison neu setzen, da es innerhalb dieser Schleife einen Saisonwechsel geben kann
			$this->setSaison($i);

			// Es muss immer eine Saison geben
			if(!$this->_oSaison) {
				return 0;
			}

			// berechnung von KOSTEn
			if($this->_sCalculateType == 'cost') {
				if($this->_oCostCategory) {

					$oCosts = new Ext_Thebing_School_Cost_Accommodation($this->_oSchool->id);
					$oCosts->setSeasonIdAndCurrencyId($this->_oSaison->getSaisonId(), $this->_oCurrency->id);

					$aPeriods = $oCosts->getNightPeriod($this->_oCostCategory->id, $this->_iAccommodationCategoryId , $sDate);
					if(!empty($aPeriods)){

						$aPeriodIds = array();
						foreach($aPeriods as $aPeriod) {
							$aPeriodIds[] = (int)$aPeriod['id'];
						}

						$fAmountPart = $oCosts->getNightCost($aPeriodIds, $this->_iRoomtypeId, $this->_iMealId);
					}
				}
			// berechnung von PREISEN
			} else {
				
				if($this->oAccommodationCategory->getSetting($this->_oSchool)->price_night == Ext_Thebing_Accommodation_Amount::PRICE_PER_NIGHT_WEEKS) {

					$aWeek = $aLastWeek = $this->getWeekByCount($this->_iWeeks, $oAccommodationUtil);

					$fAmountPart = $this->getWeekPrice($aWeek['id']);

//					$iCurrentExtraWeekPrice = 0;
//					if(!empty($aLastWeek['extraWeek'])) {
//						$iCurrentExtraWeekPrice = $this->getWeekPrice($aLastWeek['extraWeek']['id']);
//					}

				} else {

					// Zeitraum auslesen
					$iNightperiod = null;
					$agencyCategory = $this->_oAgency?->getCategory();
					if ($agencyCategory) {
						$iNightperiod = $oPrice->searchAccommodationNightPeriod($this->_iAccommodationCategoryId, $i, null, $agencyCategory);
					}
					if (
						empty($iNightperiod) &&
						$this->nationality
					) {
						$iNightperiod = $oPrice->searchAccommodationNightPeriod($this->_iAccommodationCategoryId, $i, $this->nationality, null);
					}
					if (empty($iNightperiod)) {
						$iNightperiod = $oPrice->searchAccommodationNightPeriod($this->_iAccommodationCategoryId, $i);
					}
					// Preis ermitteln
					$fAmountPart	= $oPrice->getAccommodationNightPeriodPrice($iNightperiod, $this->_iAccommodationCategoryId, $this->_iRoomtypeId, $this->_iMealId);
				}

				// Special errechnen ( falls möglich )
				if($this->_oInquiry) {
					//$fAmountPart = $this->_oInquiry->calculateSpecialAmount($fAmountPart, $this->_iCalculateStart, 'accommodation', $this->_iAccommodationId);
					if($this->_bAgencyAmount == false){
						// Beträge für die einzelnen Special merken um später den einmaligen betrag errechnen zu können
						#$_SESSION['special_amounts'][$this->_oInquiry->id][$this->_oInquiry->iLastSpecialId]['brutto'][] = $fAmount;
					}
				}

			}

			$this->aWeekPrices[$i] = $fAmountPart;

			$aCalculationDescription[] = $fAmount.' + '.$fAmountPart;
			
			$fAmount = $fAmount + $fAmountPart;
			
		}

		// Wenn agentur -> provision abziehen
		if($this->_bAgencyAmount == true){
			$oSchoolProvision = $this->_oAgency->getSchoolProvisions($this->_oSaison->getSaisonId());

			$oProvision = $oSchoolProvision->getAccommodationProvision($this->_iAccommodationCategoryId, $this->_iRoomtypeId, $this->_iMealId);

			if ($oProvision) {
				$fAmount = $fAmount - $oProvision->calculate((float)$fAmount);
			}

			// Beträge für die einzelnen Special merken um später den einmaligen betrag errechnen zu können
			#$_SESSION['special_amounts'][$this->_oInquiry->id][$this->_oInquiry->iLastSpecialId]['netto'][] = $fAmount;
		}

		// Special: Einmaliger Fixer Rabatt auf die Unterkunft
		$oSpecialAmount = new Ext_Thebing_Inquiry_Special_Amount($this->aWeekPrices, $this->_oInquiryAccommodation);
		$fSpecialAmount = $oSpecialAmount->getAmount();

		$this->aSpecialCalculationDescription = array_merge($this->aSpecialCalculationDescription, $oSpecialAmount->getCalculation());

		if($fSpecialAmount > 0){
			$this->_aSpecialBlocks = array_merge($this->_aSpecialBlocks, $oSpecialAmount->getBlocks());
			if($this->_bAgencyAmount == true){
				$this->_fSpecialAmountNetto += $fSpecialAmount;
			}else{
				$this->_fSpecialAmount += $fSpecialAmount;
			}
		}
		
		$this->aCalculationDescription = array_merge($this->aCalculationDescription, $aCalculationDescription);

		return $fAmount;
	}

	static public function getWeekCount($mStart, $mEnd, $mSchool, $bCost = false){

		if(!$mSchool instanceof Ext_Thebing_School) {
			$mSchool = Ext_Thebing_School::getInstance($mSchool);
		}
		$iNightsOfExtraWeek = $mSchool->extra_nights_price;

		if($bCost) {
			$iNightsOfExtraWeek = $mSchool->extra_nights_cost;
		}

		if($iNightsOfExtraWeek <= 0) {
			$iNightsOfExtraWeek = 7;
		}

		if(!$mStart instanceof DateTime) {
			$mStart         = Ext_TC_Util::getDateTimeObject($mStart);
		}
        if(!$mEnd instanceof DateTime) {
			$mEnd         = Ext_TC_Util::getDateTimeObject($mEnd);
		}
        $oDiff          = $mStart->diff($mEnd, true);
        $iNightsTotal   = $oDiff->days;

		$iCurrentWeeks = 0;

        // wenn die nächte nicht den incl. nächten entspricht und auch nicht größer ist gibt es keine woche
        // 7 nächte bei einstellung 7 incl nächte = 0 wochen und nicht 1 Woche
        // meiner meinung nach ist das aber eig nicht ganz korrekt, da es aber scheinbar nur bei Extrawochen etc benutzt wird passt es!
		if($iNightsTotal < $iNightsOfExtraWeek) {
			return 0;
		}
        
        // Wochen errechnen und nur ganze Woche nehmen
        $iCurrentWeeks = $iNightsTotal / 7;
        $iCurrentWeeks = floor($iCurrentWeeks);
		
		$iDaysRemainder = $iNightsTotal % 7;

		// Wochenzahl sonst falsch, da letzte Woche je nach Einstellungen nicht komplett ist
		if($iDaysRemainder >= $iNightsOfExtraWeek) {
			$iCurrentWeeks++;
		}
		
		return $iCurrentWeeks;
	}

	static public function getWeekLimits($oWDDate, \Ext_Thebing_Accommodation_Category $category, $iSchool = 0){

		$aLimits = array();

		if($iSchool <= 0) {
            $iSessionSchoolId = \Core\Handler\SessionHandler::getInstance()->get('sid');
			$iSchool = $iSessionSchoolId;
		}

		$oSchool = Ext_Thebing_School::getInstance($iSchool);
		$sStartDay = $category->getAccommodationStart($oSchool);

		$oWDDateWeekLimits = $oWDDate;

		$iCurrentDay = $oWDDateWeekLimits->get(WDDate::WEEKDAY);

		// TODO Ext_TC_Util::convertWeekdayToInt()
		$iSchoolDay = 6; // SA
		if($sStartDay == 'so'){
			$iSchoolDay = 7;
		} else if($sStartDay == 'mo'){
			$iSchoolDay = 1;
		}

		if($iSchoolDay != $iCurrentDay){

			if($iCurrentDay < $iSchoolDay) {
				$oWDDateWeekLimits->sub(1, WDDate::WEEK);
			}

			$oWDDateWeekLimits->set($iSchoolDay, WDDate::WEEKDAY);
		}

		$oWDDateWeekLimits->set('00:00:00', WDDate::TIMES);

		$aLimits['start'] = $oWDDateWeekLimits->get(WDDate::TIMESTAMP);

		$oWDDateWeekLimits->add(1, WDDate::WEEK);
		$oWDDateWeekLimits->sub(1, WDDate::SECOND);
		$oWDDateWeekLimits->set('00:00:00', WDDate::TIMES);

		$aLimits['end'] = $oWDDateWeekLimits->get(WDDate::TIMESTAMP);

		return $aLimits;

	}

}
