<?php

class Ext_Thebing_Price {

	use \Ts\Traits\PriceCalculationColumns;
	
	/**
	 * @var Ext_Thebing_School
	 */
	public $oSchool = null;

	/**
	 * @var Ext_Thebing_Agency
	 */
	public $oAgency = null;

	/**
	 * @var Ext_Thebing_Saison
	 */
	protected $oSaison = null;

	/**
	 * @var Ext_Thebing_Currency_Util
	 */
	protected $oCurrency = null;

	/**
	 * @var $aWeekList Weeklist Array
	 */
	protected $aWeekList = array();

	/**
	 * @var		ARRAY	$aCourseUnitList	course-unit list
	 */
	protected $aCourseUnitList = array();

	protected static $_aCache = array();

	protected $iAccommodation;
	protected $iRoom ;
	protected $iMeal;
	protected $iCourse;
	protected $iCourseAdditional;

	/**
	 * @var string
	 */
	protected $sDisplayLang;

	public $useFallback = true;

	/**
	 * @param string|int|Ext_Thebing_School $oSchool
	 * @param Ext_Thebing_Saison $oSaison
	 * @param Ext_Thebing_Currency_Util $oCurrency
	 * @param string $sDisplayLang
	 * @param ?string $nationality
	 * @param Ext_Thebing_Agency_Category|null $agencyCategory
	 * @param ?array $countryGroupIds
	 * @throws Exception
	 */
	public function __construct(
		$oSchool = "noData",
		$oSaison = null,
		$oCurrency = null,
		$sDisplayLang = '',
		?string $nationality=null,
		Ext_Thebing_Agency_Category $agencyCategory = null,
		?array $countryGroupIds = null
	) {

		if(!is_object($oSchool)) {
			if((int)$oSchool > 0) {
				$oSchool = Ext_Thebing_School::getInstance((int)$oSchool);
			} else {
				$oSchool = Ext_Thebing_School::getSchoolFromSession();
			}
		}

		if(
			!($oSchool instanceof Ext_Thebing_School) ||
			$oSchool->id < 1
		) {
			throw new \LogicException('No school available (price)');
		}

		$this->oSchool = $oSchool;
		$this->_setWeekList();
		$this->_setCourseUnitList();

		if($oSaison == null){
			$oSaison = new Ext_Thebing_Saison($oSchool);
		}
		$this->setSaison($oSaison);

		if($oCurrency == null) {
			$oCurrency = new Ext_Thebing_Currency_Util($oSchool);
		}
		$this->setCurrency($oCurrency);

		if($sDisplayLang != '') {
			$this->sDisplayLang = $sDisplayLang;
		}

		$this->agencyCategory = $agencyCategory;
		$this->nationality = $nationality;
		$this->countryGroupIds = $countryGroupIds;
	}

	/**
	 * @param Ext_Thebing_Saison $oSaison
	 */
	public function setSaison($oSaison) {
		$this->oSaison = $oSaison;
	}

	/*
	 * Agenturbezogene Methoden
	 */

	/**
	 * Definiert eine Agentur
	 *
	 * @param Ext_Thebing_Agency $oAgency
	 */
	public function setAgency($oAgency = null) {
		$this->oAgency = $oAgency;
	}

	/*
	 * Wochenbezogene Methoden
	 */

	/**
	 * Liefert ein Array mit allen Wochen
	 *
	 * @return mixed[]
	 */
	public function getWeekList() {
		return $this->aWeekList;
	}

	public function getWeekById($iWeekId){
		foreach((array)$this->aWeekList as $aWeek){
			if($aWeek['id'] == $iWeekId){
				return $aWeek;
			}
		}
		return false;
	}

	/**
	 * Setzt ein bestimmtes wochen array
	 * @param array $aWeek Wochen Array
	 */
	public function setWeek($aWeek){
		$this->aWeek = $aWeek;
	}

	/**
	 * Liefert den Wochenname
	 * @return string Week name
	 */
	public function getWeekName(){
		return $this->aWeek['title'];
	}

	/**
	 * Liefert die Wochenid
	 *
	 * @return int
	 */
	public function getWeekId() {
		if($this->aWeek == null) {
			$this->aWeek['id'] = 0;
		}
		return $this->aWeek['id'];
	}

	/**
	 * Schaut ob es eine Extra Woche ist
	 *
	 * @return int 1 oder 0
	 */
	public function isExtraWeek() {
		if($this->aWeek['extra'] == 1) {
			return 1;
		}
		return 0;
	}

	/*
	 * Lektionen-bezogene Methoden
	 */

	/**
	 * Liefert ein Array mit allen Lektionen
	 *
	 * @return mixed[]
	 */
	public function getCourseUnitList() {
		return $this->aCourseUnitList;
	}

	/**
	 * Setzt ein bestimmtes Lektions-Array
	 *
	 * @param mixed[] $aCourseUnit
	 */
	public function setCourseUnit($aCourseUnit) {
		$this->aCourseUnit = $aCourseUnit;
	}

	/**
	 * Lieftert ein bestimmtes Lektions-Array
	 *
	 * @retuern mixed[]
	 */
	public function getCourseUnit() {
		return $this->aCourseUnit;
	}

	/**
	 * Liefert den Lektionsnamen
	 *
	 * @return string
	 */
	public function getCourseUnitName(){
		return $this->aCourseUnit['title'];
	}

	/**
	 * Liefert die Lektions-ID
	 *
	 * @return int
	 */
	public function getCourseUnitId(){
		if($this->aCourseUnit == null){
			$this->aCourseUnit['id'] = 0;
		}
		return $this->aCourseUnit['id'];
	}

	/**
	 * Schaut ob es eine Extra Lektion ist
	 *
	 * @return int 1 oder 0
	 */
	public function isExtraCourseUnit() {
		if($this->aCourseUnit['extra'] == 1) {
			return 1;
		}
		return 0;
	}

	/*
	 * Gesamt Preis bezogene Methoden
	 */

	 /**
	  * Liefert den Gesamtpreis
	  * @param bolean $bWithSign true -> it return the price with die Currency Sign
	  * @return mixed Price or Price+Sign
	  */
	public function getPrice($iAccommodation,$iRoom,$iMeal,$iCourse,$bWithSign = true,$iCourseAdditional=0){

		$this->iAccommodation = $iAccommodation;
		$this->iRoom = $iRoom;
		$this->iMeal = $iMeal;
		$this->iCourse = $iCourse;
		$this->iCourseAdditional = $iCourseAdditional;

		return $this->_getPrice($bWithSign);
	}

	/*
	 * Sonstige Preisbezogene Methoden
	 */

	/**
	 * Liefert den Preis zurück
	 * @param bolean $bWithSign true -> it return the price with die Currency Sign
	 * @return mixed Price or Price+Sign
	 */
	public function getPlacmentFee($bWithSign = true){

		$this->aWeek = null;
		$sBack = $this->_getPriceData('placement_fee',0,0);
		if($bWithSign == true){
			$sBack.=" ".$this->oCurrency->getSign();
		}

		return $sBack;
	}

	/**
	 * Liefert den Preis zurück
	 * @param bolean $bWithSign true -> it return the price with die Currency Sign
	 * @return mixed Price or Price+Sign
	 */
	public function getEnrolmentFee($bWithSign = true){

		$this->aWeek = null;
		$sBack = $this->_getPriceData('enrolment_fee',0,0);
		if($bWithSign == true){
			$sBack.=" ".$this->oCurrency->getSign();
		}
		return $sBack;
	}

	/**
	 * Liefert den Preis zurück
	 * @param bolean $bWithSign true -> it return the price with die Currency Sign
	 * @return mixed Price or Price+Sign
	 */
	public function getMaterialPrice($bWithSign = true){

		$this->aWeek = null;
		$sBack = $this->_getPriceData('lerning_materials',0,0);
		if($bWithSign == true){
			$sBack.=" ".$this->oCurrency->getSign();
		}
		return $sBack;
	}

	/**
	 * Liefert den Preis zurück
	 * @param bolean $bWithSign true -> it return the price with die Currency Sign
	 * @return mixed Price or Price+Sign
	 */
	public function getAirportTransferPrice($sType,$iAccommodation,$iRoom=0,$iMeal=0,$bWithSign = true){

		$this->aWeek = null;
		$sBack = $this->_getPriceData('transfer_airport_'.$sType.'_'.$iAccommodation.'_'.$iRoom.'_'.$iMeal,0,0);
		if((int)$sBack <= 0){
			$sBack = $this->getOldAirportTransferPrice($sType,false);
		}
		if($bWithSign == true){
			$sBack.=" ".$this->oCurrency->getSign();
		}
		return $sBack;
	}

	public function getOldAirportTransferPrice($sType,$bWithSign = true){
		$this->aWeek = null;
		$sBack = $this->_getPriceData('transfer_airport_'.$sType,0,0);
		if($bWithSign == true){
			$sBack.=" ".$this->oCurrency->getSign();
		}
		return $sBack;
	}

	public function getAirportTransferPacketPrice($iAirport,$iAccommodation,$iRoom =0,$iMeal=0,$bWithSign = true){

		$this->aWeek = null;

		$sTemp = 'transfer_airport_packet_'.$iAirport.'_'.(int)$iAccommodation.'_'.(int)$iRoom.'_'.(int)$iMeal;


		$sBack = $this->_getPriceData($sTemp,0,0);

		if((int)$sBack <= 0){
			$sBack = $this->getOldAirportTransferPacketPrice($iAirport,false);
		}
		if($bWithSign == true){
			$sBack.=" ".$this->oCurrency->getSign();
		}
		return $sBack;
	}

	public function getOldAirportTransferPacketPrice($sType,$bWithSign = true){
		$this->aWeek = null;
		$sBack = $this->_getPriceData('transfer_airport_packet_'.$sType,0,0);
		if($bWithSign == true){
			$sBack.=" ".$this->oCurrency->getSign();
		}
		return $sBack;
	}

//	public function getAirportTransferCost($iProvider,$iAirport,$iDay,$bWithSign = true){
//		$this->aWeek = null;
//		$sBack = $this->_getPriceData('cost_transfer_airport_'.$iProvider.'_'.$iAirport.'_'.$iDay,0,0);
//		if($bWithSign == true){
//			$sBack.=" ".$this->oCurrency->getSign();
//		}
//		return $sBack;
//	}

	public function getAirportTransferPacketCost($iProvider,$iAirport,$iDay,$bWithSign = true){
		$this->aWeek = null;
		$sBack = $this->_getPriceData('cost_transfer_airport_packet_'.$iProvider.'_'.$iAirport.'_'.$iDay,0,0);
		if($bWithSign == true){
			$sBack.=" ".$this->oCurrency->getSign();
		}
		return $sBack;
	}

//	public function getTeacherCourseCost($idCourse,$bWithSign = true){
//
//		$sBack = $this->_getPriceData('cost_teachercourse_'.$idCourse,0,0);
//		if($bWithSign == true){
//			$sBack.=" ".$this->oCurrency->getSign();
//		}
//		return $sBack;
//	}

	public function getBasicCost($iId = 0,$bWithSign = true){
		$sBack = $this->_getPriceData('cost_fix_'.$iId,0);
		if($bWithSign == true){
			$sBack.=" ".$this->oCurrency->getSign();
		}
		return $sBack;
	}

	public function getAdditionalCost($iId, $bWithSign = true){
		$sBack = $this->_getPriceData('additionalcost_'.$iId,0,0);
		if($bWithSign == true){
			$sBack.=" ".$this->oCurrency->getSign();
		}
		return $sBack;
	}

	public function getAdditionalCostList($iType=null) {

		$aResult = self::fetchAdditionalCostList($this->oSchool->id, $iType);

		return $aResult;

	}

	public static function fetchAdditionalCostList($mSchoolId, $mType=null) {

		$aSql = array();

		$sWhere = '';

		if(!is_null($mType)) {
			if(is_array($mType)) {
				$sWhere .= " AND `kc`.`type` IN(:type)";
				$aSql['type'] = $mType;
			}
			else {
				$sWhere .= " AND `kc`.`type` = :type";
				$aSql['type'] = (int)$mType;
			}
		}

		if(is_array($mSchoolId)) {
			$aSql['idSchool'] = $mSchoolId;
			$sWherePartSchool = "IN(:idSchool)";
		}
		else {
			$aSql['idSchool'] = (int)$mSchoolId;
			$sWherePartSchool = '= :idSchool';
		}

		$oSchool = Ext_Thebing_School::getSchoolFromSession();
		$sDefaultLang = $oSchool->getInterfaceLanguage();
		
		$aSql['name_field'] = 'name_'.$sDefaultLang;
		
		$sSql = "
			SELECT
				`kc`.*,
				#name_field `title`
			FROM
				`kolumbus_costs` AS `kc`
			WHERE
				`kc`.`idSchool` ".$sWherePartSchool." AND
				(`kc`.`valid_until` >= CURDATE() OR `kc`.`valid_until` = 0000-00-00) AND
				`kc`.`active` = 1
				".$sWhere;

		$aResult = DB::getPreparedQueryData($sSql,$aSql);

		return $aResult;
	}

	public function getAdditionalCourseCostList($iCourseId = 0){

		if($iCourseId > 0) {
			
			// Bestimmte Kosten
			$sSql = "
				SELECT
					`kc`.*
				FROM
					#table AS `kc` INNER JOIN
					`kolumbus_costs_courses` AS `kcc` ON
					`kcc`.`kolumbus_costs_id` = `kc`.`id`
				WHERE
					`kc`.`idSchool` = :idSchool AND
					`kc`.`type` = 0 AND
					`kc`.`active` = 1 AND
					`kcc`.`customer_db_3_id` = :course_id 
				";

			$aSql = array(
				'idSchool'=> (int)$this->oSchool->id,
				'table'=>'kolumbus_costs',
				'course_id'=> (int)$iCourseId,
			);

			$aResult = DB::getPreparedQueryData($sSql,$aSql);

			foreach((array)$aResult as $mKey => $mValue){
				if($mValue['name_'.$this->sDisplayLang] != ""){
					$aResult[$mKey]['title'] = $mValue['name_'.$this->sDisplayLang];
				}
			}

			return $aResult;

		} else {

			$aResult = $this->getAdditionalCostList(0);

			return $aResult;

		}
	}

	public function getAdditionalGeneralCostList(){

		$aResult = $this->getAdditionalCostList(2);

		return $aResult;
	}

	public function getAdditionalAccommodationCostList($iAccId = 0){

		if($iAccId > 0) {

			// Bestimtme Kosten
			$sSql = "SELECT
				`kc`.*
			FROM
				#table AS `kc` INNER JOIN
				`kolumbus_costs_accommodations` AS `kca` ON
				`kca`.`kolumbus_costs_id` = `kc`.`id`
			WHERE
				`kc`.`idSchool` = :idSchool AND
				`kc`.`type` = 1 AND
				`kc`.`active` = 1 AND
				`kca`.`customer_db_8_id` = :acc_id ";
			$aSql = array(
				'idSchool'=> (int) $this->oSchool->id,
				'table'=>'kolumbus_costs',
				'acc_id'=> (int)$iAccId,
				);

			$aResult = DB::getPreparedQueryData($sSql,$aSql);

			foreach((array)$aResult as $mKey => $mValue) {
				if($mValue['name_'.$this->sDisplayLang] != "") {
					$aResult[$mKey]['title'] = $mValue['name_'.$this->sDisplayLang];
				}
			}

			return $aResult;

		} else {

			$aResult = $this->getAdditionalCostList(1);

			return $aResult;

		}

	}

	public function getFixCostList(){

		$sSql = "SELECT * FROM #table WHERE idSchool = :idSchool AND currency_id = :idCurrency AND active = 1";
		$aSql = array('idSchool'=>(int)$this->oSchool->id,'table'=>'kolumbus_fixcosts','idCurrency'=>(int)$this->oCurrency->id);

		$aResult = DB::getPreparedQueryData($sSql,$aSql);

		return $aResult;

	}

	/*
	 * Unterkunftbezogene Methoden
	 */

	/**
	 * Liefert den Preis für ein Unterkunfts-Raum-Malzeit Object
	 * @param int $iAccommodation 		id of Accommodation Categorie
	 * @param int $iRoom 				id of the Roomtype
	 * @param int $iMeal 				id of the Meal
	 * @param bolean $bWithSign true -> it return the price with die Currency Sign
	 * @return mixed Price or Price+Sign
	 */
	public function getAccommodationPrice($iAccommodation,$iRoom,$iMeal,$iWeek = null,$bWithSign = true){
		if($bWithSign == true){
			$mBack = $this->_getPriceData('accommodation_'.$iAccommodation.'_'.$iRoom.'_'.$iMeal, 0, $iWeek)." ".$this->oCurrency->getSign();
		} else {
			$mBack = $this->_getPriceData('accommodation_'.$iAccommodation.'_'.$iRoom.'_'.$iMeal, 0, $iWeek);
		}

		return $mBack;

	}

//	public function getAccommodationCost($iAccommodation,$iRoom,$iMeal,$iWeek = null,$bWithSign = true){
//
//		if($bWithSign == true){
//			$mBack = $this->_getPriceData('cost_accommodation_'.$iAccommodation.'_'.$iRoom.'_'.$iMeal,0,$iWeek)." ".$this->oCurrency->getSign();
//		} else {
//			$mBack = $this->_getPriceData('cost_accommodation_'.$iAccommodation.'_'.$iRoom.'_'.$iMeal,0,$iWeek);
//		}
//		return $mBack;
//
//	}

	/**
	 * Liefert den Preis für ein Extra Unterkunfts-Raum-Malzeit Object
	 * @param int $iAccommodation 		id of Accommodation Categorie
	 * @param int $iRoom 				id of the Roomtype
	 * @param int $iMeal 				id of the Meal
	 * @param bolean $bWithSign true -> it return the price with die Currency Sign
	 * @return mixed Price or Price+Sign
	*/
	public function getExtraNightPrice($iAccommodation,$iRoom,$iMeal,$iWeek = null,$bWithSign = true){

		if($bWithSign == true){
			$mBack = $this->_getPriceData('extra_night_'.$iAccommodation.'_'.$iRoom.'_'.$iMeal,0,0)." ".$this->oCurrency->getSign();
		} else {
			$mBack = $this->_getPriceData('extra_night_'.$iAccommodation.'_'.$iRoom.'_'.$iMeal,0,0);
		}
		return $mBack;

	}
	public function getExtraWeekPrice($iAccommodation,$iRoom,$iMeal,$bWithSign = true){

		$aWeek = $this->getExtraWeek($iAccommodation);
		$iPrice = $this->getAccommodationPrice($iAccommodation,$iRoom,$iMeal,$aWeek['id'],$bWithSign);

		if($bWithSign == true){
			$mBack = $iPrice." ".$this->oCurrency->getSign();
		} else {
			$mBack = $iPrice;
		}

		return $mBack;

	}
//	public function getExtraWeekCost($iAccommodation,$iRoom,$iMeal,$bWithSign = true){
//
//		$aWeek = $this->getExtraWeek($iAccommodation);
//
//		$iPrice = $this->getAccommodationCost($iAccommodation,$iRoom,$iMeal,$aWeek['id'],$bWithSign);
//
//		if($bWithSign == true){
//			$mBack = $iPrice." ".$this->oCurrency->getSign();
//		} else {
//			$mBack = $iPrice;
//		}
//
//		return $mBack;
//
//	}
	public function getExtraWeek($iAccommodation = 0){

		$aBack = array();
		if($iAccommodation > 0){
			$oAccommodation = new Ext_Thebing_Accommodation_Util($this->oSchool);
			$oAccommodation->setAccommodationCategorie($iAccommodation);
			$aWeeks = $oAccommodation->getAccommodationWeekList();

			foreach($aWeeks as $aWeek){
				if($aWeek['extra'] == 1){
					$aBack = $aWeek;
				}
			}
		}
		return $aBack;
	}
//	public function getExtraNightCost($iAccommodation,$iRoom,$iMeal,$iWeek = null,$bWithSign = true){
//
//		if($bWithSign == true){
//			$mBack = $this->_getPriceData('cost_extra_night_'.$iAccommodation.'_'.$iRoom.'_'.$iMeal,0,0)." ".$this->oCurrency->getSign();
//		} else {
//			$mBack = $this->_getPriceData('cost_extra_night_'.$iAccommodation.'_'.$iRoom.'_'.$iMeal,0,0);
//		}
//
//		return $mBack;
//
//	}

	/*
	 * Kurs Methoden
	 */

	/**
	 * Liefert den Preis für einen Kurs
	 *
	 * @param int $idCourse
	 * @param bolean $bWithSign
	 * @return float
	 */
	public function getCoursePrice($idCourse, $iWeek = null, $bWithSign = true, $paymentConditionId=null, $languageId = null) {

		$course = Ext_Thebing_Tuition_Course::getInstance($idCourse);

        if(
			$iWeek === null &&	
			$course->per_unit == 1
		) {
            $iWeek = $this->getCourseUnitId();
        }

		$typeParent = 'course_'.$idCourse;

		if($languageId != null) {
			$typeParent .= '_'.$languageId;
		}

		$mBack = $this->_getPriceData($typeParent, 0, $iWeek, null, null, null, $paymentConditionId);

		if($bWithSign == true) {
			$mBack .= ' '.$this->oCurrency->getSign();
		}

		return $mBack;
	}

	/**
	 * Save
	 */
	public function savePriceArray($aSaveArray){

		$nationality = $this->nationality;
		$agencyCategory = $this->agencyCategory;
		$countryGroupIds = isset($this->countryGroupIds[0]) ? array_slice($this->countryGroupIds, 0, 1) : null;

		$this->manipulateNationalityAndAgencyCategory($nationality, $agencyCategory, $countryGroupIds);

		$sSql = "SELECT
					*
				FROM
					`kolumbus_prices_new`
				WHERE
					`idSchool` = :idSchool AND
					`idSaison` 	= :idSaison AND
					`idCurrency` = :idCurrency AND
					`idWeek` = :idWeek AND
					`idParent`	= :idParent AND
					`typeParent` = :typeParent AND
					`payment_condition_id` = :payment_condition_id AND
					".$this->addAgencyCategoryWherePart($agencyCategory)." AND
					".$this->addNationalityAndCountryGroupWherePart($nationality, $countryGroupIds)."
				";

		$sSqlSet = 	"SET
						`idSchool` 		= :idSchool,
						`idSaison` 		= :idSaison,
						`idCurrency` 	= :idCurrency,
						`idWeek` 		= :idWeek,
						`idParent`		= :idParent,
						`payment_condition_id` = :payment_condition_id,
						`typeParent` 	= :typeParent,
						`value`			= :value,
						`nationality` = :nationality,
						`agency_category_id` = :agency_category_id,
						`country_group_id` = :country_group_id
				";

		$aSql = array();
		$aSql['idSchool'] = $this->oSchool->getId();
		$aSql['idSaison'] = $this->oSaison->getSaisonId();
		$aSql['idCurrency'] = $this->oCurrency->getCurrencyId();
		$aSql['idParent'] = 0;
		$aSql['nationality'] = $nationality;
		$aSql['agency_category_id'] = $agencyCategory?->id;
		$aSql['country_group_id'] = $countryGroupIds[0] ?? null;

		foreach($aSaveArray as $iWeek => $aData) {
			
			foreach($aData as $sKey => $iValue) {
				
				$aSql['payment_condition_id'] = 0;
				
				if(strpos($sKey, '#') !== false) {
					list($aSql['typeParent'], $aSql['payment_condition_id']) = explode('#', $sKey);
				} else {
					$aSql['typeParent'] = $sKey;
				}
				
				unset($aSql['value']);			
				$aSql['idWeek'] = $iWeek;

				$aResult = DB::getPreparedQueryData($sSql, $aSql);

				$aSql['value'] = $iValue;

				if(empty($aResult)) {

					// Null-Werte nicht speichern
					if($iValue === null) {
						continue;
					}
					
					$sSql_2 = "INSERT INTO
							`kolumbus_prices_new`
						 ".$sSqlSet;

				} else {

					if($iValue === null) {
						
						$sSql_2 = "
							DELETE FROM
								`kolumbus_prices_new`
							WHERE
								`id` = :id
						";
						
					} else {

						$sSql_2 = "
							UPDATE
								`kolumbus_prices_new`
							 ".$sSqlSet."
							WHERE
								`id` = :id
						";
						
					}
					
					$aSql['id'] = $aResult[0]['id'];
					
				}

				DB::executePreparedQuery($sSql_2, $aSql);

			}
		}
	}

	/*
	 * Saison/Perioden bezogene Methoden
	 */

	/*
	 * Currencybezogene Methoden
	 */

	/**
	 * Setzt die Currency/Währung
	 *
	 * @param Ext_Thebing_Currency_Util $oCurrency
	 */
	protected function setCurrency($oCurrency) {
		$this->oCurrency = $oCurrency;
	}

	/*
	 * Wochebezogene Methoden
	 */

	/**
	 * Definiert die Wochenliste
	 */
	protected function _setWeekList() {
		$this->aWeekList = $this->_getWeekList();
	}

	/**
	 * Holt die Wochen und gibt sie Als Array zurück
	 * @return array 	List Of Weeks
	 */
	protected static $_aGetWeekListCache = array();
	protected function _getWeekList(){

		if(!is_object($this->oSchool)) {
			return false;
		}

		if(!isset(self::$_aGetWeekListCache[(int)$this->oSchool->id])) {

			$sSql = "
				SELECT
					`kw`.*
				FROM
					`kolumbus_weeks` `kw` INNER JOIN
					`ts_weeks_schools` `ts_ws` ON
						`ts_ws`.`week_id` = `kw`.`id` AND
						`ts_ws`.`school_id` = :idSchool
				WHERE
					`kw`.`active` = 1
				GROUP BY
					`kw`.`id`
				ORDER BY
					`kw`.`position` ASC,
					`kw`.`id` ASC
			";
			$aSql = ['idSchool'=>(int)$this->oSchool->id];
			$aResult = DB::getPreparedQueryData($sSql, $aSql);
			self::$_aGetWeekListCache[(int)$this->oSchool->id] = (array)$aResult;

		}

		return self::$_aGetWeekListCache[(int)$this->oSchool->id];
	}

	/*
	 * Lektionen-bezogene Methoden
	 */

	/**
	 * Definiert die Lektionenliste
	 */
	protected function _setCourseUnitList(){
		$this->aCourseUnitList = $this->_getCourseUnitList();
	}

	/**
	 * Holt die Lektionen und gibt sie Als Array zurück
	 *
	 * @return mixed[]
	 */
	protected static $_aGetCourseUnitListCache = array();
	protected function _getCourseUnitList() {

		if(!($this->oSchool instanceof Ext_Thebing_School)) {
			return false;
		}

		if(!isset(self::$_aGetCourseUnitListCache[$this->oSchool->id])) {

			$aTeachingUnits = Ext_Thebing_School_TeachingUnit::getListBySchool($this->oSchool);
			self::$_aGetCourseUnitListCache[$this->oSchool->id] = array_values(array_map(
				function(Ext_Thebing_School_TeachingUnit $oTeachingUnit) {
					return $oTeachingUnit->getData();
				},
				$aTeachingUnits
			));

		}

		return self::$_aGetCourseUnitListCache[$this->oSchool->id];

	}

	/**
	 * Holt die Lektionen und gibt sie Als Array zurück
	 * @return array 	List Of Weeks
	 */

	protected static $_aGetCourseUnitByIdCache = array();

	public static function getCourseUnitById($iId = 0) {

		$iId = (int)$iId;

		if($iId <= 0) {
			return false;
		}

		if(empty(self::$_aGetCourseUnitByIdCache[$iId])) {
			$oEntity = Ext_Thebing_School_TeachingUnit::getInstance($iId);
			self::$_aGetCourseUnitByIdCache[$iId] = $oEntity->getData();
		}

		return self::$_aGetCourseUnitByIdCache[$iId];

	}


	/**
	 * Gesamtpreisbezogene Methoden
	 */
	protected static $_aGetPriceDataCache = array();

	/**
	 * Ruft aus der Datenbank den passenden Preis ab
	 * @param string $sTypeParent
	 * @param int $idParent
	 */
	protected function _getPriceData($sTypeParent, $idParent, $iWeek = null, $idSchool = null, $idSaison = null, $idCurrency = null, $paymentConditionId=null) {

		if ($idSchool === null) {
			$idSchool = (int)$this->oSchool->getId();
		}

		if ($idSaison === null) {
			$idSaison = (int)$this->oSaison->getSaisonId();
		}

		if ($idCurrency === null) {
			$idCurrency = (int)$this->oCurrency->getCurrencyId();
		}

		if($iWeek === null){
			$iWeek = $this->getWeekId();
		}

		$priceData = null;
		$agencyCategory = $this->agencyCategory;
		$nationality = $this->nationality;
		$countryGroupIds = $this->countryGroupIds;

		if (!$this->useFallback) {
			// No fallback. Used for finding the exact price entry for administration.

			$this->manipulateNationalityAndAgencyCategory($nationality, $agencyCategory, $countryGroupIds, false);
			$priceData = $this->queryPriceData($sTypeParent, $idParent, $iWeek, (int)$idSchool, (int)$idSaison, (int)$idCurrency, $paymentConditionId, $nationality, $agencyCategory, $countryGroupIds);

		} else {
			// With fallback. Find a price for the customer.

			// Try agencyCategory first
			if ($agencyCategory) {
				$priceData = $this->queryPriceData($sTypeParent, $idParent, $iWeek, (int)$idSchool, (int)$idSaison, (int)$idCurrency, $paymentConditionId, null, $agencyCategory, null);
			}

			// Try nationality/countryGroup
			if (
				$priceData === null &&
				$nationality
			) {
				$priceData = $this->queryPriceData($sTypeParent, $idParent, $iWeek, (int)$idSchool, (int)$idSaison, (int)$idCurrency, $paymentConditionId, $nationality, null, null);

				if ($priceData === null) {
					// Setting $agencyCategory null, because it would set all other params null. We already looked for agency category
					$agencyCategory = null;
					// Gets the country group ids
					$this->manipulateNationalityAndAgencyCategory($nationality, $agencyCategory, $countryGroupIds, true);
					$priceData = $this->queryPriceData($sTypeParent, $idParent, $iWeek, (int)$idSchool, (int)$idSaison, (int)$idCurrency, $paymentConditionId, $nationality, $agencyCategory, $countryGroupIds);
				}
			}

			// Last effort, with ($agencyCategory, $nationality, $countryGroupIds) all null.
			if ($priceData === null) {
				$priceData = $this->queryPriceData($sTypeParent, $idParent, $iWeek, (int)$idSchool, (int)$idSaison, (int)$idCurrency, $paymentConditionId);
			}
		}

		return $priceData;
	}

	protected function queryPriceData(
		$sTypeParent, 
		$idParent, 
		$iWeek, 
		$idSchool, 
		$idSaison, 
		$idCurrency, 
		$paymentConditionId = null,
		$nationality = null,
		Ext_Thebing_Agency_Category $agencyCategory = null,
		$countryGroupIds = null
	) {

		$countryGroupIdsKey = is_array($countryGroupIds) ? implode(",", $countryGroupIds) : null;
		$aKey = [$idParent, $idSchool, $idSaison, $idCurrency, $iWeek, $nationality, $agencyCategory?->id, $countryGroupIdsKey];
		$sKey = 'KEY_'.implode('-', $aKey);

		if($paymentConditionId !== null) {
			$sTypeParent .= '#'.$paymentConditionId;
		}
		
		if(!isset(self::$_aCache['price_data'][$sKey])) {

			$sSql = "
				SELECT
					*
				FROM
					`kolumbus_prices_new`
				WHERE
					`idSchool` = :idSchool AND
					`idSaison` = :idSaison AND
					`idCurrency` = :idCurrency AND
					`idWeek` = :idWeek AND
					`idParent` = :idParent AND
					".$this->addAgencyCategoryWherePart($agencyCategory)." AND
					".$this->addNationalityAndCountryGroupWherePart($nationality, $countryGroupIds, $this->useFallback)."
				GROUP BY
					`typeParent`, 
					`payment_condition_id`
			";
			$aSql = [
				'idSchool' => (int)$idSchool,
				'idSaison' => (int)$idSaison,
				'idCurrency' => (int)$idCurrency,
				'idWeek' => (int)$iWeek,
				'idParent' => (int)$idParent,
				'nationality' => $nationality,
				'agency_category_id' => $agencyCategory?->id
			];

			$aResult = DB::getPreparedQueryData($sSql, $aSql);

			$aCache = [];
			foreach((array)$aResult as $aData) {

				$aData['value'] = (float)$aData['value'];
				
				if(!empty($aData['payment_condition_id'])) {
					$aData['typeParent'] .= '#'.$aData['payment_condition_id'];
				}
				
				$aCache[$aData['typeParent']] = $aData; // Because of country groups multiple entry can exist for same typeparent/payment_condition combo, but would be squashed by the grouping, which one stays will be random
			}

			self::$_aCache['price_data'][$sKey] = $aCache;

		}

		return self::$_aCache['price_data'][$sKey][$sTypeParent]['value'] ?? null;
	}

	/**
	 * Rechnet den Gesamtpreis aus
	 * @param 	boolean	$bWithSign		true ->it returns the price with price sign
	 * @return 	mixed	Price or Price+Sign
	 */
	protected function _getPrice($bWithSign = true){

		$iAccommodation = $this->iAccommodation;
		$iRoom = $this->iRoom;
		$iMeal = $this->iMeal;
		$iCourse = $this->iCourse;
		$iCourseAdditional = $this->iCourseAdditional;

		if($iAccommodation <= 0 || $iCourse <= 0){

			// ein die() ist schon hart wegen debuggen, dann doch lieber eine Exception ...
			#die('Ext_Thebing_Price ['.__LINE__.'] :: Keine Unterkunft oder/und Kurs defninert');
			throw new Exception('Keine Unterkunft oder/und Kurs defninert');

		}

		$iAccommodationPrice = $this->getAccommodationPrice($iAccommodation,$iRoom,$iMeal,null,false);
		$iCoursePrice = $this->getCoursePrice($iCourse,null,false);
		$iAdditionalCoursePrice = $this->getCoursePrice($iCourseAdditional,null,false);

		if($this->oAgency != null){
			$oSchoolProvision = $this->oAgency->getSchoolProvisions($this->oAgency->oSaison->getSaisonId());

			$oAccommodationProvision = $oSchoolProvision->getAccommodationProvision($iAccommodation, $iRoom, $iMeal);
			if ($oAccommodationProvision) {
				$iAccommodationPrice = $iAccommodationPrice - $oAccommodationProvision->calculate((float)$iAccommodationPrice);
			}

			$oCourseProvision = $oSchoolProvision->getCourseProvision($iCourse);
			if ($oCourseProvision) {
				$iCoursePrice = $iCoursePrice - $oCourseProvision->calculate((float)$iCoursePrice);
			}

			$oCourseAdditionalProvision = $oSchoolProvision->getCourseProvision($iCourseAdditional);
			if ($oCourseAdditionalProvision) {
				$iAdditionalCoursePrice = $iAdditionalCoursePrice - $oCourseAdditionalProvision->calculate((float)$iAdditionalCoursePrice);
			}
		}

		$iPrice = $iAccommodationPrice + $iCoursePrice + $iAdditionalCoursePrice;

		if($bWithSign == true){
			$mBack = $iPrice." ".$this->oCurrency->getSign();
		} else {
			$mBack = $iPrice;
		}
		return $mBack;
	}

	public function saveAccommodationNightPeriod($aData, $oSaison = null){

		$nationality = $this->nationality;
		$agencyCategory = $this->agencyCategory;
		// Its save so only one country group id is allowed
		$countryGroupIds = isset($this->countryGroupIds[0]) ? array_slice($this->countryGroupIds, 0, 1) : null;

		// Makes sure max one of the three ($agencyCategory, $nationality, $countryGroupIds) is not null, in that order.
		$this->manipulateNationalityAndAgencyCategory($nationality, $agencyCategory, $countryGroupIds);

		if($oSaison != null) {
			if(
				// Start oder Ende gar nicht eingegeben
				$aData['from'] <= 0 ||
				$aData['until'] <= 0 ||
				// Start oder Ende außerhalb der aktuellen Saison
				$aData['from'] < $oSaison->valid_from ||
				$aData['until'] > $oSaison->valid_until
			) {
				if($aData['id'] > 0) {
					$sSql = "
						UPDATE
							`kolumbus_accommodation_nightprices_periods`
						SET
							`active` = 0
						WHERE
							`id` = :id
						LIMIT 1
					";
					$aSql = array(
						'id' => (int)$aData['id']
					);
					DB::executePreparedQuery($sSql, $aSql);
				}
				return false;
			}
		}

		if($aData['id'] > 0) {
			$sSql = "UPDATE ";
		} else {
			$sSql = "INSERT INTO  ";
		}

		$sSql .= "
				`kolumbus_accommodation_nightprices_periods`
			SET
				`school_id` = :school_id,
				`categorie_id` = :categorie_id,
				`active` = 1,
				`from` = FROM_UNIXTIME(:from),
				`until` = FROM_UNIXTIME(:until),
				`nationality` = :nationality,
				`agency_category_id` = :agency_category_id,
				`country_group_id` = :country_group_id
		";
		$aSql = array(
			'school_id' => (int)$this->oSchool->getId(),
			'categorie_id' => (int)$aData['categorie_id'],
			'from' => $aData['from'],
			'until' => $aData['until'],
			'nationality' => $nationality,
			'agency_category_id' => $agencyCategory?->id,
			'country_group_id' => $countryGroupIds[0] ?? null,
		);

		if($aData['id'] > 0) {
			$sSql .= "
				WHERE
					id = :id AND
					school_id = :school_id
				LIMIT
					1
			";
			$aSql['id'] = $aData['id'];
		}

		DB::executePreparedQuery($sSql,$aSql);

		if($aData['id'] <= 0){
			$aData['id'] = DB::fetchInsertId();
		}

		foreach((array)$aData['price'] as $iCategorie => $aCategorie) {
			foreach((array)$aCategorie as $iRoomtype => $aRoomtype) {
				foreach((array)$aRoomtype as $iMeal => $mValue) {
					$mValue = Ext_Thebing_Format::convertFloat($mValue);
					$this->saveAccommodationNightPeriodPrice($aData['id'], $iCategorie, $iRoomtype, $iMeal, $mValue);
				}
			}
		}

	}

	public function searchAccommodationNightPeriod($iCategorieId, $iTime, $nationality = null, Ext_Thebing_Agency_Category $agencyCategory = null) {

		$countryGroupIds = null;
		// Achtung! Man kann nur Nationalität ODER Agenturkategorie verwenden
		$this->manipulateNationalityAndAgencyCategory($nationality, $agencyCategory, $countryGroupIds, true);

		$sSql = " 
				SELECT
					`id`
				FROM
					`kolumbus_accommodation_nightprices_periods`
				WHERE
					`school_id` = :school_id AND
					`categorie_id` = :categorie_id AND
					`active` = 1 AND
					(
						:time BETWEEN
							UNIX_TIMESTAMP(`from`) AND
							UNIX_TIMESTAMP(`until`)
					) AND
					".$this->addAgencyCategoryWherePart($agencyCategory)." AND
					".$this->addNationalityAndCountryGroupWherePart($nationality, $countryGroupIds, true)."
				ORDER BY
					`nationality` DESC,
					`from` DESC,
					`country_group_id`";

		$aSql = array();
		$aSql['school_id'] 		= (int)$this->oSchool->getId();
		$aSql['categorie_id'] 	= (int)$iCategorieId;
		$aSql['time'] 			= $iTime;
		$aSql['nationality'] = $nationality;
		$aSql['agency_category_id'] = $agencyCategory?->id;

		$aResult = DB::getPreparedQueryData($sSql, $aSql);
		$aLast = reset($aResult);
		return (int)$aLast['id'];
	}

	public function getAccommodationNightPeriods($iCategorieId, $iFrom, $iUntil){

		$nationality = $this->nationality;
		$agencyCategory = $this->agencyCategory;
		$countryGroupIds = $this->countryGroupIds;

		$this->manipulateNationalityAndAgencyCategory($nationality, $agencyCategory, $countryGroupIds);

		$aSql = array();
		$aSql['school_id'] 		= (int)$this->oSchool->getId();
		$aSql['categorie_id'] 	= (int)$iCategorieId;
		$aSql['from'] 			= $iFrom;
		$aSql['until'] 			= $iUntil;
		$aSql['nationality'] = $nationality;
		$aSql['agency_category_id'] = $agencyCategory?->id;


		$sSql = " 
			SELECT
				*,
				UNIX_TIMESTAMP(`from`) `from`,
				UNIX_TIMESTAMP(`until`) `until`
			FROM
				`kolumbus_accommodation_nightprices_periods`
			WHERE
				`school_id` = :school_id AND
				`categorie_id` = :categorie_id AND
				`active` = 1 AND
				(
					UNIX_TIMESTAMP(`until`) >= :from AND
					UNIX_TIMESTAMP(`from`) <= :until
				) AND
				".$this->addAgencyCategoryWherePart($agencyCategory)." AND
				".$this->addNationalityAndCountryGroupWherePart($nationality, $countryGroupIds)."
			ORDER BY 
				`from` ASC";

		$aResult = DB::getPreparedQueryData($sSql, $aSql);

		return $aResult;
	}

	public function getAccommodationNightPeriodPrice($iNightperiod, $iCategorie, $iRoomtype, $iMeal) {
		
		if($iNightperiod <= 0) {
			return null;
		}
		
		$sSql = " SELECT
						*
					FROM
						`kolumbus_accommodation_nightprices`
					WHERE
						`nightperiod_id` 	= :nightperiod_id AND
						`categorie_id` 		= :categorie_id AND
						`roomtype_id` 		= :roomtype_id AND
						`meal_id` 			= :meal_id AND
						`currency_id` 		= :currency_id";
		$aSql = array();
		$aSql['nightperiod_id'] = (int)$iNightperiod;
		$aSql['categorie_id'] 	= (int)$iCategorie;
		$aSql['roomtype_id'] 	= (int)$iRoomtype;
		$aSql['meal_id'] 		= (int)$iMeal;
		$aSql['currency_id'] 	= (int)$this->oCurrency->getCurrencyId();

		$aResult = DB::getPreparedQueryData($sSql, $aSql);
		
		if(isset($aResult[0]['amount'])) {
			return (float)$aResult[0]['amount'];
		}
		
		return null;
	}

	public function saveAccommodationNightPeriodPrice($iNightperiod, $iCategorie, $iRoomtype, $iMeal, $mValue){
		$sSql = " REPLACE INTO
						`kolumbus_accommodation_nightprices`
					SET
						`nightperiod_id` 	= :nightperiod_id,
						`categorie_id` 		= :categorie_id,
						`roomtype_id` 		= :roomtype_id,
						`meal_id` 			= :meal_id,
						`amount` 			= :amount,
						`currency_id` 		= :currency_id";
		$aSql = array();
		$aSql['nightperiod_id'] = (int)$iNightperiod;
		$aSql['categorie_id'] 	= (int)$iCategorie;
		$aSql['roomtype_id'] 	= (int)$iRoomtype;
		$aSql['meal_id'] 		= (int)$iMeal;
		$aSql['amount'] 		= (float)$mValue;
		$aSql['currency_id'] 	= (int)$this->oCurrency->getCurrencyId();

		DB::executePreparedQuery($sSql, $aSql);


	}

	/**
	 * Die Funktion liefert false/true und gibt Information darüber ob eine Extrakosten-id zu einer Unterkunfts-typ-id gehört
	 */
	public static function checkAdditionalAccommodationCost($iAccommodationId, $iExtracostId){
		$sSql = "SELECT
						*
					FROM
						`kolumbus_costs_accommodations`
					WHERE
						`kolumbus_costs_id` = :cost_id AND
						`customer_db_8_id` = :type_id
					";
		$aSql['type_id'] = (int)$iAccommodationId;
		$aSql['cost_id'] = (int)$iExtracostId;
		$aResult = DB::getPreparedQueryData($sSql, $aSql);

		if(empty($aResult)){
			$bResult = false;
		}else{
			$bResult = true;
		}

		return $bResult;
	}

	/**
	 * Die Funktion liefert false/true und gibt Information darüber ob eine Extrakosten-id zu eines Kurs-typ-id gehört
	 */
	public static function checkAdditionalCourseCost($iCourseId, $iExtracostId) {
		
		$sSql = "
			SELECT
				*
			FROM
				`kolumbus_costs_courses`
			WHERE
				`kolumbus_costs_id` = :cost_id AND
				`customer_db_3_id` = :type_id
			";
		$aSql['type_id'] = (int)$iCourseId;
		$aSql['cost_id'] = (int)$iExtracostId;
		$aResult = DB::getPreparedQueryData($sSql, $aSql);

		if(empty($aResult)){
			$bResult = false;
		}else{
			$bResult = true;
		}

		return $bResult;
	}


	public function saveInsurancePrices($aPrices, $bPacket, $iPeriodID, $iCurrencyID) {
		
		// Nicht mehr genutzte Preise müssen gelöscht werden, daher hier abrufen
		$sqlQuery = "
			SELECT 
				`id` `key`,
				`id` `value`
			FROM
				`kolumbus_insurance_prices`
			WHERE
				`school_id` = :school_id AND
				`period_id` = :period_id AND
				`currency_id` = :currency_id
		";
		$sqlParam = [
			'school_id' => (int)$this->oSchool->id,
			'period_id' => (int)$iPeriodID,
			'currency_id' => (int)$iCurrencyID
		];
		$currentPriceIds = \DB::getQueryPairs($sqlQuery, $sqlParam);

		foreach((array)$aPrices as $iInsuranceID => $aWeeks) {
			
			foreach((array)$aWeeks as $iWeekID => $aData) {
				
				foreach((array)$aData as $iPriceID => $iPrice) {
					
					if(empty($iPriceID)) {
						
						$aCriteria = [
							'school_id' => (int)$this->oSchool->id,
							'insurance_id' =>(int)$iInsuranceID,
							'week_id' => (int)$iWeekID,
							'period_id' => (int)$iPeriodID,
							'currency_id' => (int)$iCurrencyID
						];

						$oPrice = Ext_Thebing_Insurances_Price::getRepository()->setCheckActive(false)->findOneBy($aCriteria);
						
						if($oPrice === null) {
							$oPrice = new Ext_Thebing_Insurances_Price();
						}
						
					} else {
						$oPrice = Ext_Thebing_Insurances_Price::getInstance($iPriceID);
					}

					$oPrice->active = 1;
					$oPrice->packet = (int)$bPacket;
					$oPrice->school_id = (int)$this->oSchool->id;
					$oPrice->insurance_id = (int)$iInsuranceID;
					$oPrice->week_id = (int)$iWeekID;
					$oPrice->period_id = (int)$iPeriodID;
					$oPrice->currency_id = (int)$iCurrencyID;
					$oPrice->price = (float)$iPrice;
					
					$oPrice->save();

					if(isset($currentPriceIds[$oPrice->id])) {
						unset($currentPriceIds[$oPrice->id]);
					}
					
				}
			}
		}

		$sqlQuery = "
			UPDATE
				`kolumbus_insurance_prices`
			SET
				`active` = 0
			WHERE
				`school_id` = :school_id AND
				`period_id` = :period_id AND
				`currency_id` = :currency_id AND
				`id` IN (:ids)
		";
		$sqlParam = [
			'school_id' => (int)$this->oSchool->id,
			'period_id' => (int)$iPeriodID,
			'currency_id' => (int)$iCurrencyID,
			'ids' => $currentPriceIds
		];
		\DB::executePreparedQuery($sqlQuery, $sqlParam);
		
	}


	public function getInsurancePricesList($iPeriodID, $iCurrencyID, $sTrace, $sLang = '') {

		$oSchool = $this->oSchool;
		$oClient = $oSchool->getClient();
		$sDefaultLang = $oSchool->getLanguage();

		/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */ // Get all insurances

		$sSQL = "
			SELECT
				`kins`.*,
				`kip`.`id` AS `price_id`,
				`kip`.`price`,
				`kip`.`packet`
			FROM
				`kolumbus_insurances` AS `kins` INNER JOIN
				`kolumbus_insurance_providers` AS `kinsp` ON
					`kins`.`provider_id` = `kinsp`.`id` LEFT OUTER JOIN
				`kolumbus_insurance_prices` AS `kip` ON
					`kins`.`id` = `kip`.`insurance_id` AND
					`kip`.`week_id` = 0 AND
					`kip`.`period_id` = :iPeriodID AND
					`kip`.`currency_id` = :iCurrencyID AND
					`kip`.`school_id` = :iSchoolID AND
					`kip`.`active` = 1
			WHERE
				`kinsp`.`active` = 1 AND
				`kins`.`active` = 1
			ORDER BY
				`name_".$sDefaultLang."`
		";
		$aSQL = array(
			'iPeriodID' => (int)$iPeriodID,
			'iCurrencyID' => (int)$iCurrencyID,
			'iSchoolID' => (int)$oSchool->id
		);
		$aInsurances = DB::getPreparedQueryData($sSQL, $aSQL);

		/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */

		$sWhere = "";
		if($oClient->insurance_price_method == 1) {
			$sWhere .= " `kinsw`.`startweek` != 0 ";
		} else {
			$sWhere .= " `kinsw`.`startweek` = 0 ";
		}

		$aWeekTitles = Ext_Thebing_Insurances_Gui2_Insurance::getCalculationMethods($sTrace, $sLang);

		foreach((array)$aInsurances as $iKey => $aValue) {
			if($aValue['payment'] == 3) {
				$sSQL = "
					SELECT
						`kinsw`.*,
						`kip`.`id` AS `price_id`,
						`kip`.`price`,
						`kip`.`packet`
					FROM
						`kolumbus_insurance_weeks` AS `kinsw`
					INNER JOIN
						`kolumbus_insurances2weeks` AS `ki2w`
					ON
						`kinsw`.`id` = `ki2w`.`week_id`
					LEFT OUTER JOIN
						`kolumbus_insurance_prices` AS `kip`
					ON
						`ki2w`.`insurance_id` = `kip`.`insurance_id` AND
						`kip`.`insurance_id` = :iInsuranceID AND
						`ki2w`.`week_id` = `kip`.`week_id` AND
						`kip`.`period_id` = :iPeriodID AND
						`kip`.`currency_id` = :iCurrencyID AND
						`kip`.`school_id` = :iSchoolID AND
						`kip`.`active` = 1
					WHERE
						`kinsw`.`active` = 1 AND
						`ki2w`.`insurance_id` = :iInsuranceID AND
						".$sWhere."
					ORDER BY
						`kinsw`.`position`,
						`kinsw`.`id`
				";
				$aSQL = array(
					'iPeriodID' => (int)$iPeriodID,
					'iCurrencyID' => (int)$iCurrencyID,
					'iSchoolID' => (int)$oSchool->id,
					'iInsuranceID' => (int)$aValue['id']
				);
				$aWeeks = DB::getPreparedQueryData($sSQL, $aSQL);
				$aInsurances[$iKey]['weeks'] = $aWeeks;
			} else {
				$aInsurances[$iKey]['weeks'][] = array(
					'id' => 0,
					'title' => $aWeekTitles[$aValue['payment']],
					'price_id' => (int)$aValue['price_id'],
					'price' => $aValue['price'],
					'packet' => $aValue['packet']
				);
			}
		}

		return $aInsurances;
	}

	public static function getCostListByType() {

		$oClient = Ext_Thebing_Client::getFirstClient();
		$aTempSchools = $oClient->getSchools();

		$aSchools = array();
		foreach((array)$aTempSchools as $aSchool) {
			$aSchools[$aSchool['id']] = $aSchool;
		}

		$aSchoolIds = array_keys($aSchools);

		$aReturn = [];

		$aCostTypes = self::getCostTypes();
		$aCostTypesIds = array_keys($aCostTypes);

		$aCostData = Ext_Thebing_Price::fetchAdditionalCostList($aSchoolIds, $aCostTypesIds);

		if(!empty($aCostData)) {
			
			foreach($aCostData as $aData) {
				
				$sTitle			 = $aCostTypes[$aData['type']];
				$aSelectedSchool = $aSchools[$aData['idSchool']];
				$sSchoolLanguage = 'en';
				if(!empty($aSelectedSchool['language'])) {
					$sSchoolLanguage = $aSelectedSchool['language'];
				}
				$sSchoolTitle = $aSelectedSchool['ext_1'];

				$sLanguageColumn = $aData['name_'.$sSchoolLanguage];
				if(empty($sLanguageColumn)) {
					$sLanguageColumn = $aData['title'];
				}
				$aReturn[$sSchoolTitle][$sTitle][$aData['id']] = $sLanguageColumn;
			}
		}

		ksort($aReturn);

		return $aReturn;
	}

	public static function getCostTypes() {

		return [
			Ext_Thebing_School_Additionalcost::TYPE_COURSE => L10N::t('Zus. Kurskosten', 'Thebing » Marketing » Additionalcosts'),
			Ext_Thebing_School_Additionalcost::TYPE_ACCOMMODATION => L10N::t('Zus. Unterkunftskosten', 'Thebing » Marketing » Additionalcosts'),
			Ext_Thebing_School_Additionalcost::TYPE_GENERAL =>L10N::t('Generelle Kosten', 'Thebing » Marketing » Additionalcosts')
		];

	}

	/**
	 * recursively convert all floating point values from a nested array into dot-format 0.00 
	 * @param	ARRAY	$aList	
	 * @param	ARRAY	$bReturnOnlyConvertedItems	flag to enable item skipping
	 * @param	ARRAY	$aFieldsToSkip				list of item keys to skip converting
	 * @return	ARRAY
	 */
	public function convertPriceFloatInputs($aList = array(), $bReturnOnlyConvertedItems = false, $aFieldsToSkip = array()) {
		
		foreach( (array)$aList as $mKey => $mValue) {
			
			if( !is_array($mValue) ) {
				
				if($mValue === '') {
					$aList[$mKey] = null;
					continue;
				}
				
				$mConverted = \Ext_Thebing_Format::convertFloat( $mValue );
				$aList[$mKey] = is_float($mConverted) ? $mConverted : $mValue ;
				
			} else if ( is_array($mValue) && !in_array( (string)$mKey, $aFieldsToSkip ) ) {
				$aList[$mKey] = $this->convertPriceFloatInputs( $mValue, $bReturnOnlyConvertedItems, $aFieldsToSkip  );
			} else {
				if ( 
					$bReturnOnlyConvertedItems && 
					is_array($aFieldsToSkip) && 
					( count((array)$aFieldsToSkip) > 0 ) && 
					in_array( (string)$mKey, $aFieldsToSkip ) 
				) {
					unset($aList[$mKey]);
				}
			}
		}

		return $aList;
	}
	
	public function t($text) {
		return \Ext_Thebing_L10N::t($text, '', 'Thebing » Marketing » Prices');
	}

	public function formatPrice($price, $currency=null) {
		
		if(is_null($price)) {
			return '';
		}
		
		return Ext_Thebing_Format::Number($price, $currency, $this->oSchool, true, 5);
	}

}