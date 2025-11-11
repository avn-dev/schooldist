<?php

/**
 * @property string $comment
 * @property string $transfer_mode
 * @property string $transfer_comment
 * @property float $amount
 * @property integer $currency_id
 */
class Ext_TS_Enquiry_Combination extends Ext_Thebing_Basic {

	protected $_sTable = 'ts_enquiries_combinations';

	protected $_sTableAlias = 'ts_ec';

	// Bearbeiter Spalte
	protected $_sEditorIdColumn = 'editor_id';

	/**
	 * Eine Liste mit Klassen, die sich auf dieses Object beziehen, bzw. 
	 * mit diesem verknüpft sind (parent: n-1, 1-1, child: 1-n, n-m)
	 */
	protected $_aJoinedObjects = array(
		'enquiry' => array(
			'class'					=> 'Ext_TS_Enquiry',
			'key'					=> 'enquiry_id',
			'check_active'			=> true
		),
		'course' => array(
			'class'					=> 'Ext_TS_Enquiry_Combination_Course',
			'key'					=> 'combination_id',
			'type'					=> 'child',
			'check_active'			=> true,
			'orderby'				=> 'from',
			// Total wichtig für getCreatedForDiscount() im Form, da ansonsten das Enquiry-Objekt mit Offer verloren geht
			'bidirectional' => true
		),
		'accommodation' => array(
			'class'					=> 'Ext_TS_Enquiry_Combination_Accommodation',
			'key'					=> 'combination_id',
			'type'					=> 'child',
			'check_active'			=> true,
			'orderby'				=> 'from',
			'bidirectional' => true
		),
		'arrival'	=> array(
			'class'					=> 'Ext_TS_Enquiry_Combination_Transfer',
			'key'					=> 'combination_id',
			'type'					=> 'child',
			'static_key_fields'		=> array('transfer_type' => 1, 'active' => 1), // Transfer ist nicht löschbar
			'check_active'			=> true,
			'bidirectional' => true
		),
		'departure'	=> array(
			'class'					=> 'Ext_TS_Enquiry_Combination_Transfer',
			'key'					=> 'combination_id',
			'type'					=> 'child',
			'static_key_fields'		=> array('transfer_type' => 2, 'active' => 1), // Transfer ist nicht löschbar
			'check_active'			=> true,
			'bidirectional' => true
		),
		'individual'	=> array(
			'class'					=> 'Ext_TS_Enquiry_Combination_Transfer',
			'key'					=> 'combination_id',
			'type'					=> 'child',
			'static_key_fields'		=> array('transfer_type' => 0, 'active' => 1), // Transfer ist nicht löschbar
			'check_active'			=> true,
			'bidirectional' => true
		),
		'transfer'	=> array(
			'class'					=> 'Ext_TS_Enquiry_Combination_Transfer',
			'key'					=> 'combination_id',
			'type'					=> 'child',
			'check_active'			=> true,
			'bidirectional' => true
		),
		'insurance'	=> array(
			'class'					=> 'Ext_TS_Enquiry_Combination_Insurance',
			'key'					=> 'combination_id',
			'type'					=> 'child',
			'check_active'			=> true,
			'orderby'				=> 'from',
			'bidirectional' => true
		)
	  );
	
	//JoinTables
	protected $_aJoinTables = array(
		'special_position_relation' => array(
			'table'					=> 'ts_enquiries_combinations_to_special_positions',
			'primary_key_field'		=> 'enquiry_combination_id',
			'foreign_key_field'		=> 'special_position_id',
			'autoload'				=> false,
			'class' => 'Ext_Thebing_Inquiry_Special_Position',
			'on_delete' => 'no_action'
		)
	);

	/**
	 * @return Ext_TS_Enquiry
	 */
	public function getEnquiry() {
		return $this->getJoinedObject('enquiry');
		#$oEnquiry = $this->getJoinedObject('enquiry');

		//Hier musste ich leider ohne die joinedobjects arbeiten, da beim generieren der Gruppenanfragenpositionen
		//die $_aInstance von der Ext_TS_Enquiry manipuliert wird(Traveller wird reingesetzt)
		$oEnquiry = Ext_TS_Enquiry::getInstance($this->enquiry_id);

		return $oEnquiry;

	}

	/**
	 * Gibt den ausgewählten Kurse der Kombination zurück
	 *
	 * @return Ext_TS_Enquiry_Combination_Course[]
	 */
	public function getCombinationCourses() {

		$aCombinationCourses = (array)$this->getJoinedObjectChilds('course', true);
		return $aCombinationCourses;

	}

	/**
	 * Gibt die ausgewählten Unterkünfte der Kombination zurück
	 * 
	 * @return Ext_TS_Enquiry_Combination_Accommodation[]
	 */
	public function getCombinationAccommodations() {

		$aCombinationAccommodations = (array)$this->getJoinedObjectChilds('accommodation', true);
		return $aCombinationAccommodations;

	}

	/**
	 * Gibt die ausgewählte Versicherung der Kombination zurück
	 *
	 * @return Ext_TS_Enquiry_Combination_Insurance[]
	 */
	public function getCombinationInsurances() {

		$aCombinationInsurances = (array)$this->getJoinedObjectChilds('insurance', true);
		return $aCombinationInsurances;

	}

	public function manipulateSqlParts(&$aSqlParts, $sView=null) {
		
		$sSelect = self::buildColumnsSelect();
		
		$aSqlParts['select'] .= "
			, `ts_ect_arr`.`start` `arrival_start`
			, `ts_ect_arr`.`start_type` `arrival_start_type`
			, `ts_ect_arr`.`end` `arrival_end`
			, `ts_ect_arr`.`end_type` `arrival_end_type`
			, `ts_ect_arr`.`transfer_date` `arrival_date`
			, `ts_ect_dep`.`start` `departure_start`
			, `ts_ect_dep`.`start_type` `departure_start_type`
			, `ts_ect_dep`.`end` `departure_end`
			, `ts_ect_dep`.`end_type` `departure_end_type`
			, `ts_ect_dep`.`transfer_date` `departure_date`
			".$sSelect."
		";
		
		$aSqlParts['from'] .= " LEFT JOIN
			
			## Kurse

			`ts_enquiries_combinations_courses` `ts_ecc` ON
				`ts_ecc`.`combination_id` = `".$this->_sTableAlias."`.`id` AND
				`ts_ecc`.`active` = 1 
			".self::getCourseJoins()." LEFT JOIN

			## Unterkünfte

			`ts_enquiries_combinations_accommodations` `ts_eca` ON
				`ts_eca`.`combination_id` = `".$this->_sTableAlias."`.`id` AND
				`ts_eca`.`active` = 1 
			".self::getAccommodationJoins()." LEFT JOIN
				
			## Versicherung

			`ts_enquiries_combinations_insurances` `ts_eci` ON
				`ts_eci`.`combination_id` = `".$this->_sTableAlias."`.`id` AND
				`ts_eci`.`active` = 1 
			".self::getInsuranceJoins()." LEFT JOIN
				
			## Transfer Anreise

			`ts_enquiries_combinations_transfers` `ts_ect_arr` ON
				`ts_ect_arr`.`combination_id` = `".$this->_sTableAlias."`.`id` AND
				`ts_ect_arr`.`active` = 1 AND
				`ts_ect_arr`.`transfer_type` = 1 LEFT JOIN
			
			## Transfer Abreise

			`ts_enquiries_combinations_transfers` `ts_ect_dep` ON
				`ts_ect_dep`.`combination_id` = `".$this->_sTableAlias."`.`id` AND
				`ts_ect_dep`.`active` = 1 AND
				`ts_ect_dep`.`transfer_type` = 2
			
		";
		
		$aSqlParts['groupby'] = $this->_sTableAlias . '.`id`';
	
	}
	
	/**
	 *
	 * Select-Parts für die Spalten als Funktion auslagern, da die auch bei den Angeboten benutzt werden
	 * 
	 * @return string 
	 */
	public static function buildColumnsSelect()
	{
		$oSchool			= Ext_Thebing_Client::getFirstSchool();
		$sInterfaceLanguage = $oSchool->getInterfaceLanguage();
		$sNameField			= '`name_'.$sInterfaceLanguage.'`';
		$sShortField		= '`short_'.$sInterfaceLanguage.'`';

		$sSql = "
			, GROUP_CONCAT(DISTINCT `ts_ecc`.`id` SEPARATOR '{||}' ) `all_course_ids` 
			, ".self::buildConcatSelect('`ts_ecc`.`id`', '`kolumbus_tc`.'.$sNameField, '`course_name`', '{||}')."
			, ".self::buildConcatSelect('`ts_ecc`.`id`', '`kolumbus_tc`.`name_short`', '`course_name_short`', '{||}')."
			, ".self::buildConcatSelect('`ts_ecc`.`id`', '`ts_ecc`.`from`', '`course_from`', '{||}')."
			, ".self::buildConcatSelect('`ts_ecc`.`id`', '`ts_ecc`.`until`', '`course_until`', '{||}')."
			, ".self::buildConcatSelect('`ts_ecc`.`id`', '`kolumbus_tl`.'.$sNameField, '`course_level`', '{||}')."
			, ".self::buildConcatSelect('`ts_ecc`.`id`', '`ts_ecc`.`weeks`', '`course_weeks`', '{||}')."
			, GROUP_CONCAT(
				DISTINCT CONCAT(
					'ID_', `ts_ecc`.`id`, '_', IF(
						`kolumbus_tc`.`per_unit` = 1,
						(`ts_ecc`.`units`),
						(
							SELECT 
								SUM(`ktc_program`.`lessons_per_week`)
							FROM
								`ts_tuition_courses_programs` `ts_tcp`  INNER JOIN
								`ts_tuition_courses_programs_services` `ts_tcps` ON
									`ts_tcps`.`program_id` = `ts_tcp`.`id` AND
									`ts_tcps`.`type` = '".\TsTuition\Entity\Course\Program\Service::TYPE_COURSE."' AND
									`ts_tcps`.`active` = 1 INNER JOIN
								`kolumbus_tuition_courses` `ktc_program` ON
									`ktc_program`.`id` = `ts_tcps`.`type_id` AND
									`ktc_program`.`active` = 1
							WHERE 
								`ts_tcp`.`course_id` = `kolumbus_tc`.`id` AND
								`ts_tcp`.`active` = 1	
						) * `ts_ecc`.`weeks`
					)
				)
				 SEPARATOR '{||}'
			) `course_units` 
			, GROUP_CONCAT(DISTINCT `ts_eca`.`id` SEPARATOR '{||}') `all_accommodation_ids`
			, ".self::buildConcatSelect('`ts_eca`.`id`', '`ts_eca`.`from`', '`accommodation_from`', '{||}')."
			, ".self::buildConcatSelect('`ts_eca`.`id`', '`ts_eca`.`until`', '`accommodation_until`', '{||}')."
			, ".self::buildConcatSelect('`ts_eca`.`id`', '`ts_eca`.`weeks`', '`accommodation_weeks`', '{||}')."
			, GROUP_CONCAT( DISTINCT
				'ID_', `ts_eca`.`id`, '_',
				CONCAT_WS(' / ', `kolumbus_ac`.".$sNameField.", `kolumbus_am`.".$sNameField.", `kolumbus_ar`.".$sNameField.")
				SEPARATOR '{||}'
			) `accommodation_all`
			, GROUP_CONCAT( DISTINCT
				'ID_', `ts_eca`.`id`, '_',
				CONCAT_WS(' / ', `kolumbus_ac`.".$sShortField.", `kolumbus_am`.".$sShortField.", `kolumbus_ar`.".$sShortField.")
				SEPARATOR '{||}'
			) `accommodation_all_short`
			, GROUP_CONCAT(DISTINCT `ts_eci`.`id`) `all_insurance_ids`
			, ".self::buildConcatSelect('`ts_eci`.`id`', '`kolumbus_i`.'.$sNameField, '`insurance_name`')."
			, ".self::buildConcatSelect('`ts_eci`.`id`', '`ts_eci`.`from`', '`insurance_from`')."
			, ".self::buildConcatSelect('`ts_eci`.`id`', '`ts_eci`.`until`', '`insurance_until`')."
		";	
		
		return $sSql;
	}
	
	/**
	 *
	 * Group_Concat select part vorbereiten für die Concat-Formatklasse
	 * 
	 * @param string $sIdField
	 * @param string $sSelectField
	 * @param string $sSelectName
	 * @return string 
	 */
	public static function buildConcatSelect($sIdField, $sSelectField, $sSelectName, $sSeperator = ',') {

		$sSql = "
			GROUP_CONCAT(
				DISTINCT CONCAT(
					'ID_', " . $sIdField . ", '_', ".$sSelectField."
				)
				SEPARATOR '".$sSeperator."'
			) ".$sSelectName."	
		";
		
		return $sSql;
	}
	
	/**
	 *
	 * Join von KombinationsKursen zu Kurs/Level
	 * 
	 * @return string 
	 */
	public static function getCourseJoins()
	{
		$sSql = " LEFT JOIN
			`kolumbus_tuition_courses` `kolumbus_tc` ON
				`ts_ecc`.`course_id` = `kolumbus_tc`.`id` AND
				`kolumbus_tc`.`active` = 1 LEFT JOIN
			`ts_tuition_levels` `kolumbus_tl` ON
				`ts_ecc`.`level_id` = `kolumbus_tl`.`id` AND
				`kolumbus_tl`.`active` = 1 
		";
		
		return $sSql;
	}
	
	/**
	 *
	 * Join von KombinationsUnterkünften zu U.kategorie/Zimmerart/Verpflegung
	 * 
	 * @return string 
	 */
	public static function getAccommodationJoins()
	{
		$sSql = " LEFT JOIN
			`kolumbus_accommodations_categories` `kolumbus_ac` ON
				`ts_eca`.`accommodation_id` = `kolumbus_ac`.`id` AND
				`kolumbus_ac`.`active` = 1 LEFT JOIN
			`kolumbus_accommodations_meals` `kolumbus_am` ON
				`ts_eca`.`meal_id` = `kolumbus_am`.`id` AND
				`kolumbus_am`.`active` = 1 LEFT JOIN
			`kolumbus_accommodations_roomtypes` `kolumbus_ar` ON
				`ts_eca`.`roomtype_id` = `kolumbus_ar`.`id` AND
				`kolumbus_ar`.`active` = 1	
		";
		
		return $sSql;
	}
	
	/**
	 *
	 * Join von KombinationsVersicherungen zu Versicherungsanbieter
	 * 
	 * @return string 
	 */
	public static function getInsuranceJoins()
	{
		$sSql = " LEFT JOIN
			`kolumbus_insurances` `kolumbus_i` ON
				`ts_eci`.`insurance_id` = `kolumbus_i`.`id` AND 
				`kolumbus_i`.`active` = 1	
		";
		
		return $sSql;
	}

	/**
	 * @return array
	 */
	public function getInsurancesWithPriceData($sDisplayLanguage = null)
	{
		$aInsurances	= $this->getJoinedObjectChilds('insurance');
		$oEnquiry		= $this->getEnquiry();
		
		$aResult		= Ext_TS_Enquiry_Combination_Helper::getInsurancesWithPriceData($oEnquiry, $aInsurances, $sDisplayLanguage);
		
		return $aResult;
	}
	
	/**
	 *
	 * Verbindungstabelle für Specialpositionen
	 * 
	 * @return array 
	 */
	public function getSpecialPositionRelationTableData()
	{
		if(isset($this->_aJoinTables['special_position_relation']))
		{
			return $this->_aJoinTables['special_position_relation'];
		}
		else
		{
			throw new Exception('Position Relation Table not found!');
		}
	}
	
	/**
	 * Wenn Filter arrival oder departure ist, wird statt ein array ein objekt zurückgegeben, weil das bei den inquiries genau
	 * so funktioniert musste ich diese verrückte sache übernehmen
	 * 
	 * @return Ext_TS_Enquiry_Combination_Transfer <array> | Ext_TS_Enquiry_Combination_Transfer
	 */
	public function getTransfers($sFilter = '', $bIgnoreBookingStatus = false)
	{
		$aCombinationTransfers	= $this->getJoinedObjectChilds('transfer');

		$aFilteredCombinationTransfers = Ext_TS_Enquiry_Combination_Helper::filterTransfers($aCombinationTransfers, $sFilter, $bIgnoreBookingStatus);
		
		return $aFilteredCombinationTransfers;
	}

	/**
	 * Liefert den ersten Kursbeginn einer Kombination
	 *
	 * @return string 
	 */
	public function getFirstCourseStart() {

		$sFrom = null;
		$oFirstCourse = $this->getFirstCourse();

		if($oFirstCourse) {
			$sFrom = $oFirstCourse->from;
		}

		return $sFrom;

	}

	/**
	 * Liefert den ersten Kursbeginn einer Kombination
	 *
	 * @return string 
	 */
	public function getFirstCourseName($sLang = '', $bShort = false) {

		$sName = null;
		$oFirstCombinationCourse = $this->getFirstCourse();

		if($oFirstCombinationCourse) {
			$sName = $oFirstCombinationCourse->getCourseName($sLang, $bShort);
		}

		return $sName;

	}

	/**
	 * Liefert den ersten Kursb einer Kombination
	 *
	 * @return Ext_TS_Enquiry_Combination_Course
	 */
	public function getFirstCourse(){

		$aCourses = $this->getCombinationCourses();
		$oFirst = $this->_getFirstStartObject($aCourses);
		return $oFirst;

	}

	/**
	 * Liefert anhand der übergebenen Objekten den ersten Leistungsbeginn aus
	 *
	 * @param array $aObjects
	 * @return string
	 */
	protected function _getFirstStartObject($aObjects) {

		$sFrom = '';
		$oWDDate = new WDDate();
		$oFirstObject = null;

		foreach ($aObjects as $oObject) {

			$sCurrentFrom = $oObject->getFrom();
			$oWDDate->set($sCurrentFrom, WDDate::DB_DATE);

			if(
				empty($sFrom) ||
				$oWDDate->compare($sFrom, WDDate::DB_DATE) < 0
			) {
				$sFrom = $sCurrentFrom;
				$oFirstObject = $oObject;
			}

		}

		return $oFirstObject;

	}

	/**
	 * Berechnet die Gesamtzahl der Wochen 
	 *
	 * @return integer
	 */
	public function getCourseWeeks() {

		$aCourses = $this->getCombinationCourses();

		$iWeeks = 0;
		foreach($aCourses as $oCourse) {
			$iWeeks = $iWeeks + $oCourse->weeks;
		}

		return $iWeeks;

	}	

	/**
	 * TransferTyp (Anreise/Abreise/An- und Abreise)
	 *
	 * @return string
	 */
	public function getTransferMode() {

		return $this->transfer_mode;

	}

	public function validate($bThrowExceptions = false) {

		$mReturn = parent::validate($bThrowExceptions);

		if($mReturn === true) {
			$mReturn = array();
		}

		$oEnquiry = $this->getEnquiry();
		$iCurrencyId = $oEnquiry->getCurrency();

		if($iCurrencyId <= 0) {
			$mReturn[] = 'ENQUIRY_CURRENCY_NOT_SAVED';
		}

		if(empty($mReturn)) {
			$mReturn = true;
		}

		return $mReturn;

	}

	/**
	 * Gibt true zurück wenn es bereits Angebote zu dieser Kombination gibt, ansonsten false.
	 *
	 * Die Kombination darf nicht mehr geändert oder gelöscht werden, wenn bereits ein Angebot erstellt wurde.
	 * Hier muss leider über die Enquiry gegangen werden, da es keine direkte Verknüpfung zwischen
	 * Kombination und Angebot gibt. Da es hier wiederum keine Zwischentabelle gibt und das Offer über die Services geht,
	 * wäre eine Ergänzung der Relation auch zu redundant für nur diesen Anwendungsfall.
	 *
	 * @return bool
	 */
	public function hasOffers() {

		$oEnquiry = $this->getEnquiry();
		$aOffers = $oEnquiry->getOffers();

		foreach($aOffers as $oOffer) {
			$aOfferCombinations = $oOffer->getCombinations();
			foreach($aOfferCombinations as $iOfferCombinationId => $oCombination) {
				if($this->id == $iOfferCombinationId) {
					return true;
				}
			}
		}

		return false;

	}

}
