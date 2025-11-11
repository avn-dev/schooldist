<?php

/**
 * @property $id
 * @property $idSchool 	
 * @property $user_id 	
 * @property $created 	
 * @property $changed 	
 * @property $title 	
 * @property $type TYPE_COURSE|TYPE_ACCOMMODATION|TYPE_GENERAL
 * @property $active 	
 * @property $creator_id 	
 * @property $name_de 	
 * @property $name_en 	
 * @property $timepoint
 * @property $fee_type 	
 * @property $group_option
 * @property $charge "auto"|"semi"|"2"manual"
 * @property $calculate CHARGE_ONCE|CHARGE_PER_SERVICE|CHARGE_PER_WEEK|per night
 * @property $dependency_on_duration
 * @property $dependency_on_age
 * @property $no_price_display
 * @property array $costs_courses
 * @property array $costs_accommodations
 * @property array $dependencies_age
 * @property string $frontend_icon_class
 * @property string $cost_center (Dieser Wert kommt aus der WDBasic_Attributes-Tabelle)
 */
class Ext_Thebing_School_Additionalcost extends Ext_Thebing_Basic {

	use \Core\Traits\WdBasic\TransientTrait;

	const TYPE_COURSE = 0;

	const TYPE_ACCOMMODATION = 1;

	const TYPE_GENERAL = 2;

	const CALCULATION_ONCE = 0;

	const CALCULATION_PER_SERVICE = 1;

	const CALCULATION_PER_WEEK = 2;

	const CALCULATION_PER_NIGHT = 3;

	const CREDIT_PROVIDER_ALL = 1;
	const CREDIT_PROVIDER_ONLY_PROVIDER = 2;
	
	// Tabellenname
	protected $_sTable = 'kolumbus_costs';

	// Tabellenalias
	protected $_sTableAlias = 'kcos';

	protected $_aAttributes = [
		'frontend_icon_class' => [
			'class' => 'WDBasic_Attribute_Type_Varchar'
		],
		'cost_center' => [
			'class' => 'WDBasic_Attribute_Type_Varchar'
		],
		'use_service_category_cost_center' => [
			'type' => 'int'
		]
	];

	// joined tables
	protected $_aJoinTables = array(
		'costs_courses'=>array(
			'table'=>'kolumbus_costs_courses',
			'foreign_key_field'=>'customer_db_3_id',
			'primary_key_field'=>'kolumbus_costs_id'
		),
		'costs_accommodations'=>array(
			'table'=>'kolumbus_costs_accommodations',
			'foreign_key_field' =>	array('customer_db_8_id','roomtype_id','meal_id'),
			'primary_key_field' =>	'kolumbus_costs_id',
		),
		'dependencies_age' => [
			'table' => 'kolumbus_costs_dependencies_age',
			'primary_key_field' => 'fee_id',
			'on_delete' => 'no_action',
		]
	);

	protected $_aJoinedObjects = array(
		// TODO Gegen JoinTable / Multi Rows austauschen (wie Alter)
		'calculation_combination' => array(
			'class' => 'Ext_Thebing_School_Cost_Combination',
			'key' => 'cost_id',
			'type' => 'child',
			'check_active' => true,
			'query' => false,
			'on_delete' => 'cascade'
		),
		'pos_stock' => [
			'class' => 'Ts\Entity\PointOfSale\Stock',
			'key' => 'cost_id',
			'type' => 'child',
			'check_active' => true,
		]
	);
	
	protected $_aFlexibleFieldsConfig = [
		'marketing_additional_costs' => []
	];

	// Liefert den Namen der Kosten je nach Sprache
	public function getName($sLang = ''){
		
		if(empty($sLang)) {
			$oSchool = Ext_Thebing_School::getInstance((int)$this->getSchoolId());
			$sLang = $oSchool->getInterfaceLanguage();
		}

		if(isset($this->_aData['name_'.$sLang])){
			return $this->_aData['name_'.$sLang];
		}else{
			return $this->title;
		}

	}

	public function validate($bThrowExceptions = false) {

		// Ein Eintrag ist irgendwie immer vorhanden?
		if (!$this->dependency_on_age) {
			$this->dependencies_age = [];
		}

		$mValidate = parent::validate($bThrowExceptions);

		// Da irgendein Held 0 als Wert verwendet hatte, funktioniert bei dem Feld kein required
		if ($mValidate === true) {
			$oSelection = new Ext_Thebing_Gui2_Selection_Marketing_Additionalcost_Calculate();
			$aOptions = $oSelection->getOptions([], [], $this);
			if (!isset($aOptions[$this->calculate])) {
				$mValidate = [$this->_sTableAlias.'.calculate' => 'EMPTY'];
			}
		}

		if ($mValidate === true) {
			foreach((array)$this->dependencies_age as $iKey => $aDependency) {
				if((int)$aDependency['age'] <= 0) {
					if(!is_array($mValidate)) {
						$mValidate = [];
					}
					$mValidate['dependencies_age.age-'.$iKey.''][] = 'INVALID_INT_POSITIVE';
				}
			}
		}

		return $mValidate;


	}

	public function save($bLog = true) {
		
//		global $user_data;
//
//		$this->user_id = $user_data['id'];
//		$this->changed = date('Y-m-d H:i:s');

		$mReturn = parent::save($bLog);
		
		WDCache::deleteGroup(Ext_Thebing_School::ADDITIONAL_SERVICES_CACHE_GROUP);
		
		return $mReturn;
	}
	
	// Special Info
	public function getSpecialInfo($iSchoolId, $sDisplayLanguage){
		
		$sName = $this->getName($sDisplayLanguage);

		$sName = \Ext_TC_Placeholder_Abstract::translateFrontend('Vergünstigung für:', $sDisplayLanguage) . ' ' . $sName;

		return $sName;
	}
	
	/**
	 * gibt alle Kombinationen der Preisberechnung zurück
	 * @return Ext_Thebing_School_Cost_Combination []
	 */
	public function getPriceCalculationCombinations() {
		$aBack = $this->getJoinedObjectChilds('calculation_combination', true);
		return $aBack;
	}
	
	public function __get($sName) 
	{	
		
		Ext_Gui2_Index_Registry::set($this);
		
		$mValue = parent::__get($sName);

		if(
			$sName == 'costs_accommodations'
		){
			$mValue = (array)$mValue;
			$aCosts = array();
			
			foreach($mValue as $aIds)
			{
				$sKey = $aIds['customer_db_8_id'].'_'.$aIds['roomtype_id'].'_'.$aIds['meal_id'];
				
				$aCosts[] = $sKey;
}

			$mValue = $aCosts;
		}
		
		return $mValue;
	}
	
	public function __set($sName, $mValue) {
		
		if(
			$sName == 'costs_accommodations'
		){
			$aCosts = (array)$mValue;
			$aIds	= array();
			
			foreach($aCosts as $mCostInfo){
				
				$aEntry = array();
				
				if(is_array($mCostInfo)) {
					$aEntry = $mCostInfo;
				} else {
					$aCostInfo = explode('_', $mCostInfo);
					$aEntry['customer_db_8_id'] = (int)$aCostInfo[0];
					$aEntry['roomtype_id']		= (int)$aCostInfo[1];
					$aEntry['meal_id']			= (int)$aCostInfo[2];
				}
				
				$aIds[] = $aEntry;
			}
			
			$mValue = $aIds;
		}
		
		parent::__set($sName, $mValue);
	}
		
	public function showZeroAmount(){
		
		if(
			$this->charge === 'auto' &&
			$this->no_price_display == 0
		) {
			return false;
		}
		
		return true;
	}

	/**
	 * prüft ob die übergebenen Wochen für diese Zusatzkosten passen
	 * wenn nicht darf die zusatzkosten nicht berechnet werden
	 * @param int $iWeeks
	 * @return boolean
	 */
	public function checkWeeksDependency($iWeeks) {

		$iCheckDurration = (int)$this->dependency_on_duration;

		if($iCheckDurration){
			$aCombinations = $this->getPriceCalculationCombinations();
			foreach($aCombinations as $oCombination) {
				$sSymbol = $oCombination->symbol;
				$iSymbolWeeks = (int)$oCombination->factor;
				switch ($sSymbol) {
					case '>':
						if($iWeeks > $iSymbolWeeks) {
							return true;
						}
						break;
					case '<':
						if($iWeeks < $iSymbolWeeks) {
							return true;
						}
						break;
					case '=':
						if($iWeeks == $iSymbolWeeks) {
							return true;
						}
						break;
				}
			}
		} else {
			return true;
		}

		return false;
	}

	/**
	 * Abhängig von Alter überprüfen
	 *
	 * Die Inquiry wird direkt übergeben, da der Anwendungsfall mit nur $iAge bisher nicht benötigt wird.
	 *
	 * @param Ext_TS_Inquiry_Abstract $oInquiry
	 * @return bool
	 */
	public function checkAgeDependency(Ext_TS_Inquiry_Abstract $oInquiry) {

		if(!$this->dependency_on_age) {
			return true;
		}

		$oTimeframe = \Carbon\Carbon::parse($oInquiry->service_from)->toPeriod($oInquiry->service_until);
		if($oTimeframe === null) {
			// Hier könnte man als Fallback created einbauen, aber eigentlich sollte ein Zeitraum vorhanden sein
			throw new RuntimeException('Inquiry has no service timeframe!');
		}

		$iAge = $oInquiry->getCustomer()->getAge($oTimeframe->start);
//		if($iAge === 0) {
//			// Alter 0 ist eigentlich ein Fehler (die Methode macht das nicht besser)
//			return false;
//		}

		foreach($this->dependencies_age as $aDependency) {
			if(
				(
					$aDependency['operator'] === '<' &&
					$iAge < $aDependency['age']
				) || (
					$aDependency['operator'] === '=' &&
					(int)$aDependency['age'] === $iAge
				) || (
					$aDependency['operator'] === '>' &&
					$iAge > $aDependency['age']
				)
			) {
				return true;
			}
		}

		return false;

	}

	public static function getTimepointOptions(\Tc\Service\LanguageAbstract $language): array {
		return [
			1 => $language->translate('erster Kurstag'),
			2 => $language->translate('letzter kurstag')
		];
	}

}
