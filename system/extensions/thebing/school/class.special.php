<?php

/**
 * @property $id
 * @property $changed 	
 * @property $created 	
 * @property $active 	
 * @property $creator_id 	
 * @property $visible 	
 * @property $from 	
 * @property $to 	
 * @property $period_type
 * @property $service_period_calculation
 * @property $limit_type 	
 * @property $limit 	
 * @property $direct_booking 	
 * @property $agency_grouping 	
 * @property $use_student_status 	
 * @property $amount_type 	
 * @property $school_id 	
 * @property $name 	
 * @property $user_id 	
 * @property $position
 */
class Ext_Thebing_School_Special extends Ext_Thebing_Basic {

	/**
	 * @var string
	 */
	protected $_sTable = 'ts_specials';
	protected $_sTableAlias = 'ts_sp';

	/**
	 * @var string
	 */
	static protected $sL10N = 'Thebing » Marketing » Special';

	/**
	 * @var int
	 */
	protected $_iAllCountries = 0;
	/**
	 * @var int
	 */
	protected $_iAllNationalities = 0;

	/**
	 * @var array
	 */
	protected $_aFormat = array(
		'changed' => array(
			'format' => 'TIMESTAMP'
		),
		'created' => array(
			'format' => 'TIMESTAMP'
		),
		'from' => array(
			'format' => 'DATE',
			),
		'to' => array(
			'format' => 'DATE',
			),
		'limit' => array(
			'validate' => 'INT_NOTNEGATIVE'
		)
	);

	/**
	 * @var array
	 */
	protected $_aJoinTables = array(
		'join_countries'=> array(
			'table'=>'ts_specials_countries',
			'foreign_key_field'=>'country_id',
            'primary_key_field'=>'special_id'
		),
		'nationalities'=> array(
			'table'=>'ts_specials_nationalities',
			'foreign_key_field'=>'nationality_iso',
            'primary_key_field'=>'special_id'
		),
		'join_country_groups'=> array(
			'table'=>'ts_specials_countries_group',
			'foreign_key_field'=>'country_group_id',
			'primary_key_field'=>'special_id'
		),
		'join_agency_countries'=> array(
			'table'=>'ts_specials_agencies_country',
			'foreign_key_field'=>'agency_country_id',
            'primary_key_field'=>'special_id'
		),
		'join_agency_groups'=> array(
			'table'=>'ts_specials_agencies_group',
			'foreign_key_field'=>'agency_group_id',
            'primary_key_field'=>'special_id'
		),
		'join_agency_country_groups'=> array(
			'table'=>'ts_specials_agencies_countries_group',
			'foreign_key_field'=>'country_group_id',
			'primary_key_field'=>'special_id'
		),
		'join_agency_categories'=> array(
			'table'=>'ts_specials_agencies_categories',
			'foreign_key_field'=>'agency_category_id',
            'primary_key_field'=>'special_id'
		),
		'join_agencies'=> array(
			'table'=>'ts_specials_agencies',
			'foreign_key_field'=>'agency_id',
            'primary_key_field'=>'special_id'
		),
		'student_status' => array(
			'table' => 'ts_specials_to_student_status',
			'foreign_key_field' => 'student_status_id',
			'primary_key_field' => 'special_id'
		),
		'schools' => [
			'table' => 'ts_specials_schools',
			'class' => \Ext_Thebing_School::class,
			'foreign_key_field' => 'school_id',
			'primary_key_field' => 'special_id'
		]
	);

	protected $_aAttributes = [
		'cost_center' => [
			'class' => 'WDBasic_Attribute_Type_Varchar'
		]
	];

	/**
	 * @param string $sName
	 * @return int|mixed|null|string
	 * @throws ErrorException
	 */
	public function __get($sName){
		
		Ext_Gui2_Index_Registry::set($this);
		
		if($sName == 'all_countries') {

			$aCountries = $this->join_countries;
			$countryGroups = $this->join_country_groups;
			$this->_iAllCountries = 0;
			if(empty($aCountries) && empty($countryGroups)) {
				$this->_iAllCountries = 1;
			}

			return $this->_iAllCountries;

		} elseif($sName == 'all_nationalities') {

			$nationalities = $this->nationalities;
			$this->_iAllNationalities = 0;
			if(empty($nationalities)){
				$this->_iAllNationalities = 1;
			}

			return $this->_iAllNationalities;

		} else {
			return parent::__get($sName);
		}
	}

	/**
	 * @param string $sName
	 * @param mixed $mValue
	 */
	public function __set($sName, $mValue) {

		if($sName == 'all_countries') {
			if($mValue == 1) {
				$this->join_countries = [];
				$this->join_country_groups = [];
			}
			$this->_iAllCountries = $mValue;
		} elseif($sName == 'all_nationalities') {
			if($mValue == 1) {
				$this->nationalities = [];
			}
			$this->_iAllNationalities = $mValue;
		} else if($sName == 'join_countries') {
			if($this->_iAllCountries == 1) {
				$mValue = array();
			}
			parent::__set($sName, $mValue);
		} else if($sName == 'join_country_groups') {
			if($this->_iAllCountries == 1) {
				$mValue = array();
			}
			parent::__set($sName, $mValue);
		} else{
			parent::__set($sName, $mValue);
		}

	}
	
	// =======================================================================
	// Speichermethoden für Dynamische Dialogdaten
	// =======================================================================

	/**
	 * Prozent-Daten speichern
	 *
	 * @param array $aData
	 * @return array
	 * @throws Exception
	 */
	public function savePercentData($aData){

		$aError = array();

		foreach((array)$aData['amount'] as $iKey => $fPercent) {

			$fPercent = Ext_Thebing_Format::convertFloat($fPercent);

			if($fPercent <= 0) {
				continue;
			}

			$iOption = (int)$aData['option'][$iKey];
			$aCourse = $aData['course'][$iKey];
			$aAccommodation = $aData['accommodation'][$iKey];
			$aTransfer = $aData['transfer'][$iKey];
			$aAdditional = $aData['additional'][$iKey];
			$aConditionSymbols = $aData['condition_symbol'][$iKey];
			$aConditionWeeks = $aData['condition_weeks'][$iKey];
			$iDependencyOnDuration = (int) $aData['dependency_on_duration'][$iKey];
			
			// Rausfinden welche Daten in diesem Block gespeichert werden dürfen
			$aSaveData = array();
			$sSaveDataType	= '';

			switch($iOption){
				case 1: // Kurs
					$aSaveData = $aCourse;
					$sSaveDataType = 'course';
					break;
				case 2: // Unterkunft
					$aSaveData = $aAccommodation;
					$sSaveDataType = 'accommodation';
					break;
				case 3: // Transfer
					$aSaveData = $aTransfer;
					$sSaveDataType = 'transfer';
					break;
				case 4: // Additional
					$aSaveData = $aAdditional;
					$sSaveDataType = 'additional';
					break;
			}

			// Block speichern
			$oSpecialBlock = new Ext_Thebing_Special_Block_Block();
			$oSpecialBlock->special_id = (int)$this->id;
			$oSpecialBlock->option_id = $iOption;
			$oSpecialBlock->percent = $fPercent;
			$oSpecialBlock->dependency_on_duration = $iDependencyOnDuration;

			if($iDependencyOnDuration == 0) {
				$oSpecialBlock->cleanJoinedObjectChilds('conditions');
			} else {
				foreach((array)$aConditionSymbols as $iConditionKey => $iSymbol) {
					$oCondition = $oSpecialBlock->getJoinedObjectChild('conditions');
					$oCondition->symbol = (int)$iSymbol;
					$oCondition->weeks = (int)$aConditionWeeks[$iConditionKey];
				}
			}
			
			$mValidate = $oSpecialBlock->validate();

			if($mValidate === true) {

				$oSpecialBlock->save();
				// Blockbezogene Daten speichern
				$oSpecialBlock->saveAdditionalData($aSaveData, $sSaveDataType);

			} else {
				$aError = array_merge($aError, $mValidate);
			}

		}

		return $aError;
	}

	/**
	 * Absolut Daten speichern
	 *
	 * @param array $aData
	 * @return array
	 * @throws Exception
	 */
	public function saveAbsolutData($aData){

		$aError = array();

		foreach((array)$aData['amount'] as $iKey => $aAmounts) {

			$bSaveAmount = false;
			$aSaveAmount = array();

			// Beträge vorbereiten
			foreach((array)$aAmounts as $iCurrency => $fAmount) {
				$fAmountTemp = Ext_Thebing_Format::convertFloat($fAmount);
				if($fAmountTemp > 0) {
					// Wenn eine Währung > 0 dann block speichern
					$bSaveAmount = true;
				}
				$aSaveAmount[$iCurrency] = $fAmountTemp;
			}

			if(!$bSaveAmount) {
				continue;
			}

			$iOption = (int)$aData['option'][$iKey];
			$aCourse = $aData['course'][$iKey];
			$aAccommodation	= $aData['accommodation'][$iKey];
			$aConditionSymbols = $aData['condition_symbol'][$iKey];
			$aConditionWeeks = $aData['condition_weeks'][$iKey];
			$iDependencyOnDuration	= (int) $aData['dependency_on_duration'][$iKey];
			
			// Rausfinden welche Daten in diesem Block gespeichert werden dürfen
			$aSaveData = array();
			$sSaveDataType	= '';

			switch($iOption){
				case 1: // Einmalig
					break;
				case 2: // Kurs pro woche
				case 4: // Kurs einmalig
					$aSaveData = $aCourse;
					$sSaveDataType = 'course';
					break;
				case 3: // Unterkunft pro woche
				case 5: // Unterkunft einmalig
					$aSaveData = $aAccommodation;
					$sSaveDataType = 'accommodation';
					break;
			}
			
			// Block speichern
			$oSpecialBlock = new Ext_Thebing_Special_Block_Block();
			$oSpecialBlock->special_id = (int)$this->id;
			$oSpecialBlock->option_id = $iOption;
			$oSpecialBlock->dependency_on_duration	= (int) $iDependencyOnDuration;
			
			if($iDependencyOnDuration == 0) {
				$oSpecialBlock->cleanJoinedObjectChilds('conditions');
			} else {
				foreach($aConditionSymbols as $iConditionKey => $iSymbol) {			
					$oCondition = $oSpecialBlock->getJoinedObjectChild('conditions');
					$oCondition->symbol = (int)$iSymbol;
					$oCondition->weeks = (int)$aConditionWeeks[$iConditionKey];
				}
			}
			
			$mValidate = $oSpecialBlock->validate();

			if($mValidate === true) {

				$oSpecialBlock->save();
				// Blockbezogene Daten speichern
				$oSpecialBlock->saveAdditionalData($aSaveData, $sSaveDataType);
				// Währungen speichern
				$oSpecialBlock->saveAdditionalData($aSaveAmount, 'currency');

			} else {
				$aError = array_merge($aError, $mValidate);
			}

		}

		return $aError;
	}

	/**
	 * Wochendaten speichern
	 *
	 * @param array $aData
	 * @return array
	 */
	public function saveWeekData($aData) {

		$aError = array();

		foreach((array)$aData['weeks'] as $iKey => $iWeeks) {

			$iWeeks = (int)$iWeeks;

			if($iWeeks <= 0) {
				continue;
			}

			$iFreeWeeks	= (int)$aData['free'][$iKey];
			$iOption = (int)$aData['option'][$iKey];
			$aCourse = $aData['course'][$iKey];
			$aAccommodation = $aData['accommodation'][$iKey];

			// Rausfinden welche Daten in diesem Block gespeichert werden dürfen
			$aSaveData = array();
			$sSaveDataType = '';

			switch($iOption) {
				case 1: // Kurs
					$aSaveData = $aCourse;
					$sSaveDataType = 'course';
					break;
				case 2: // Unterkunft
					$aSaveData = $aAccommodation;
					$sSaveDataType = 'accommodation';
					break;
			}


			// Block speichern
			$oSpecialBlock = new Ext_Thebing_Special_Block_Block();
			$oSpecialBlock->special_id = (int)$this->id;
			$oSpecialBlock->option_id = $iOption;
			$oSpecialBlock->weeks = $iWeeks;
			$oSpecialBlock->free_weeks = $iFreeWeeks;

			$mValidate = $oSpecialBlock->validate();

			if($mValidate === true) {

				$oSpecialBlock->save();
				// Blockbezogene Daten speichern
				$oSpecialBlock->saveAdditionalData($aSaveData, $sSaveDataType);


			} else {
				$aError = array_merge($aError, $mValidate);
			}

		}

		return $aError;
	}

	/**
	 * Blockdaten löschen des specials
	 *
	 * @TODO Das ist Müll und muss raus; durch JoinedObjectContainer ersetzen!
	 */
	public function deleteBlockData() {

		$aBlocks = $this->getBlocks();
		foreach($aBlocks as $oBlock){
			$oBlock->delete();
		}

	}

    /**
     * @return Ext_Thebing_Special_Block_Block[]
     */
	public function getBlocks() {

		$aBack = array();

		if($this->id > 0) {

			$sSql = "
				SELECT
					*
				FROM
					`ts_specials_blocks`
				WHERE
					`special_id` = :special_id AND
					`active` = 1
			";

			$aSql = array();
			$aSql['special_id'] = (int)$this->id;

			$aResult = (array)DB::getPreparedQueryData($sSql, $aSql);
			
			foreach($aResult as $aData){
				$aBack[] = Ext_Thebing_Special_Block_Block::getInstance($aData['id']);
			}

		}

		return $aBack;
	}

	/**
	 * Übrige Specials bestimmen
	 *
	 * @return bool|int
	 */
	public function getAvailable() {

		// verbrauchte specials
		$iUsed = $this->getUsedQuantity();

		$iAvailable = (int)($this->limit - $iUsed);

		if($iAvailable < 0) {
			$iAvailable = 0;
		}

		if($this->limit_type == 1) {
			// unbegrenzt verfügbar
			$mBack = true;
		} else {
			$mBack = $iAvailable;
		}

		return $mBack;
	}

	public function getDiscountCode(string $code=null) {
		
		if(empty($code)) {
			return false;
		}

		$sqlQuery = "
			SELECT 
				* 
			FROM 
				`ts_specials_codes`
			WHERE 
				`special_id` = :special_id AND 
				`valid` = 1 AND 
				`code` = :code AND
				(
					(`valid_from` IS NULL OR CURDATE() >= `valid_from`) AND
					(`valid_until` IS NULL OR CURDATE() <= `valid_until`)
				)
		";
		$sqlParams = [
			'special_id' => (int)$this->id,
			'code' => (string)$code
		];
		
		$specialData = \DB::getQueryRow($sqlQuery, $sqlParams);

		if(empty($specialData)) {
			return null;
		}
		
		return Ts\Entity\Special\Code::getObjectFromArray($specialData);
	}
	
	/**
	 * Verbrauchte Specials bestimmen
	 *
	 * @return int
	 */
	public function getUsedQuantity() {

		$sSql = "
			SELECT
				`kips`.`id`
			FROM
				`kolumbus_inquiries_positions_specials` `kips` INNER JOIN
				`ts_specials` `ks` ON
					`ks`.`id` = `kips`.`special_id` AND
					`ks`.`active` = 1 INNER JOIN
				`ts_inquiries_to_special_positions` `ts_i_to_sp` ON
					`ts_i_to_sp`.`special_position_id` = `kips`.`id`
			WHERE
				`kips`.`active` = 1 AND
				`kips`.`used` = 1 AND
				`kips`.`special_id` = :special_id
			GROUP BY
				`ts_i_to_sp`.`inquiry_id`
		";

		$aResult = DB::getPreparedQueryData($sSql, array(
			'special_id' => (int)$this->id
		));

		return (int)count($aResult);
	}

	/**
	 * Prozentoptionen
	 *
	 * @return array
	 */
	public static function getPercentOptions(){

		$aPercentOptions = array();
		$aPercentOptions[1] = L10N::t('Kurs', self::$sL10N);
		$aPercentOptions[2] = L10N::t('Unterkunft', self::$sL10N);
		$aPercentOptions[3] = L10N::t('Transfer', self::$sL10N);
		$aPercentOptions[4] = L10N::t('Zusatzkosten', self::$sL10N);

		return $aPercentOptions;
	}

	/**
	 * Periodtype
	 *
	 * @return array
	 */
	public static function getPeriodeTypes(){

		$aPeriodType = array();
		$aPeriodType[1] = L10N::t('Kurs/Unterkunfts/Transfer Datum', self::$sL10N);
		$aPeriodType[2]	= L10N::t('Buchungsdatum', self::$sL10N);

		return $aPeriodType;
	}

	/**
	 * Limittypes
	 *
	 * @return array
	 */
	public static function getLimitTypes(){

		$aLimitType	= array();
		$aLimitType[1] = L10N::t('unbegrenzte Verfügbarkeit', self::$sL10N);
		$aLimitType[2] = L10N::t('begrenzte Verfügbarkeit', self::$sL10N);

		return $aLimitType;
	}

	/**
	 * Verfügbar für
	 *
	 * @return array
	 */
	public static function getAvailableFor(){

		$aAvailableFor = array();
		$aAvailableFor[1] = L10N::t('Direktbuchungen', self::$sL10N);
		$aAvailableFor[2] = L10N::t('Agenturen', self::$sL10N);

		return $aAvailableFor;
	}

	/**
	 * Agencygrouping
	 *
	 * @return array
	 */
	public static function getAgencyGrouping(){

		$aAgencyGrouping = array();
		$aAgencyGrouping[1] = L10N::t('Alle', self::$sL10N);
		$aAgencyGrouping[2]	= L10N::t('Länder', self::$sL10N);
		$aAgencyGrouping[3]	= L10N::t('Agenturgruppen', self::$sL10N);
		$aAgencyGrouping[4]	= L10N::t('Agenturkategorien', self::$sL10N);
		$aAgencyGrouping[5]	= L10N::t('Agenturen', self::$sL10N);
		$aAgencyGrouping[6]	= L10N::t('Ländergruppen', self::$sL10N);
		// Sollten hier mal Listen ergänzt werden: Ext_Thebing_Agency::getSpecials()

		return $aAgencyGrouping;
	}

	/**Amount Types
	 *
	 * @return array
	 */
	public static function getAmountTypes(){

		$aAmountTypes = array();
		$aAmountTypes[1] = L10N::t('Prozent', self::$sL10N);
		$aAmountTypes[2] = L10N::t('absolut', self::$sL10N);
		$aAmountTypes[3] = L10N::t('freie Wochen', self::$sL10N);

		return $aAmountTypes;
	}

	/**
	 * @return array
	 */
	public static function getAbsolutOptions(){

		$aAbsolutOptions = array();
		$aAbsolutOptions[1] = L10N::t('einmalig für jede(n) Kurs/Unterkunft', self::$sL10N);
		$aAbsolutOptions[4]	= L10N::t('einmalig pro Kurs', self::$sL10N);
		$aAbsolutOptions[5]	= L10N::t('einmalig pro Unterkunft', self::$sL10N);
		$aAbsolutOptions[2]	= L10N::t('pro Kurswoche', self::$sL10N);
		$aAbsolutOptions[3]	= L10N::t('pro Unterkunftswoche', self::$sL10N);

		return $aAbsolutOptions;
	}

	/**
	 * @return array
	 */
	public static function getWeekOptions(){

		$aWeekOptions = array();
		$aWeekOptions[1] = L10N::t('Kurs', self::$sL10N);
		$aWeekOptions[2] = L10N::t('Unterkunft', self::$sL10N);

		return $aWeekOptions;
	}

	/**
	 * @param bool $bThrowExceptions
	 * @return array|bool
	 * @throws Exception
	 */
	public function validate($bThrowExceptions = false) {

		$mValidate = parent::validate($bThrowExceptions);

		$oAddError = function($sField, $sError) use(&$mValidate) {
			if(!is_array($mValidate)) {
				$mValidate = [];
			}
			$mValidate[$sField][] = $sError;
		};

		if(
			$this->created_from !== null &&
			$this->created_until !== null
		) {
			$dFrom = new DateTime($this->created_from);
			$dUntil = new DateTime($this->created_until);

			// Enddatum nach Startdatum
			if($dFrom > $dUntil) {
				$oAddError('to', 'INVALID_DATE_UNTIL_BEFORE_FROM');
			} else {
				// Zeitraum darf nur verlängert werden
				if(
					$this->exist() &&
					$this->getUsedQuantity() > 0
				) {
					$dFromOriginal = new DateTime($this->_aOriginalData['created_from']);
					$dUntilOriginal = new DateTime($this->_aOriginalData['created_until']);

					if($dFrom > $dFromOriginal) {
						$oAddError('created_from', 'INVALID_DATE_PAST');
					}

					if($dUntil < $dUntilOriginal) {
						$oAddError('created_until', 'INVALID_DATE_FUTURE');
					}
				}
			}
		}

		if(
			$this->exist() &&
			$this->getUsedQuantity() > 0
		) {
			// Zuvor gespeicherte Länder/Agenturen/etc dürfen nach Special-Verwendung nicht mehr rausgelöscht werden
			foreach(['join_agency_countries', 'join_agency_groups', 'join_agency_country_groups', 'join_country_groups', 'join_agency_categories', 'join_agencies'] as $sKey) {
				if(!empty(array_diff($this->_aOriginalJoinData[$sKey], $this->$sKey))) {
					$oAddError($sKey, 'USED_OPTION_REMOVED');
				}
			}
		}

		if ($this->discount_code_enabled === "0") {

			$usedCodesExist = \Ts\Entity\Special\Code::query()
				->where('special_id', $this->id)
				->whereNotNull('latest_use')
				->exists();

			if($usedCodesExist) {
				$oAddError($this->_sTableAlias.'.discount_code_enabled', 'DISCOUNT_CODE_DISABLED_BUT_USED_CODES_EXIST');
			}

		}

		return $mValidate;
	}

}