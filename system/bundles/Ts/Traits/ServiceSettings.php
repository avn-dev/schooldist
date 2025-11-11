<?php

namespace Ts\Traits;

trait ServiceSettings {
	
	/**
	 * Trennzeichen zwischen den Schlüsseln für die Zuweisungen
	 * 
	 * @var type 
	 */
	public $sKeyDelimiter = '#';
	
	/**
	 *
	 * @var array 
	 */
	protected $groupedAllocations = null;
	
	public function createCourseAllocations($type, $object, $key) {		
		
		if($object->$key == 1) {
			// Konten pro Kurs
			
			$aCourseIds = $this->getCourseIds();
			
			foreach($aCourseIds as $iCourseId) {
				$aData = array(
					'type_id'		=> $iCourseId,
					'type'			=> 'course',
					'account_type'	=> $type,
				);

				$this->createAllocation($aData);
			}
			
		} elseif($object->$key == 2) {
			// Konten pro Kategorie
			
			$aCourseCategoryIds = $this->getCourseCategoryIds();
			
			foreach($aCourseCategoryIds as $iCourseCategoryId) {
				$aData = array(
					'type'				=> 'course',
					'parent_type_id'	=> $iCourseCategoryId,
					'parent_type'		=> 'course_category',
					'account_type'		=> $type,
				);

				$this->createAllocation($aData);
			}
		} else {
			// einmalig
			$this->_createDefaultAllocation('course', $type);
		}

	}
		
	public function createAccommodationAllocations($type, $object, $key) {
		
		if($object->$key == '2') {
			// pro Kategorie
			
			$aCategoryIds = $this->getAccommodationCategoryIds();
			
			foreach($aCategoryIds as $iCategoryId) {
				$aData = array(
					'type'				=> 'accommodation',
					'parent_type'		=> 'accommodation_category',
					'parent_type_id'	=> $iCategoryId,
					'account_type'		=> $type,
				);

				$this->createAllocation($aData);
			}
			
		} elseif($object->$key == '3') {
			// einmalig
			
			$this->_createDefaultAllocation('accommodation', $type);
		}
		
	}
	
	public function createActivityAllocation($type, $object, $key) {
		
		if($object->$key == '1') {
			// pro Aktivität

			$aActivityIds = $this->getActivityIds();

			foreach(array_keys($aActivityIds) as $iActivityId) {
				$aData = array(
					'type'				=> 'activity',
					'type_id'			=> $iActivityId,
					'account_type'		=> $type,
				);

				$this->createAllocation($aData);
			}
		} else {
			// einmalig

			$this->_createDefaultAllocation('activity', $type);
		}
	}
		
	public function createAdditionalAllocations($accountType, $costType, $object, $key) {	
			
		if($costType == 'course') {
			$sFunctionName = 'getAdditionalCourseCostList';
			$sFunctionNameForParent = 'getCourseIds';
			$sParentType = $costType;
		} elseif($costType == 'accommodation') {
			$sFunctionName = 'getAdditionalAccommodationCostList';
			$sFunctionNameForParent = 'getAccommodationCategoryIds';
			$sParentType = 'accommodation_category';
		} elseif($costType == 'general') {
			$sFunctionName = 'getAdditionalGeneralCostList';
			$sParentType = $costType;
			// $sFunctionNameForParent nicht nötig, da generellen Kosten kein parent haben
		} else {
			throw new \Exception('cost type "' . $costType . '" not defined!');
		}

		if($object->$key == 3) {

			// einmalig
			$this->_createDefaultAllocation('additional_' . $costType, $accountType);

		} else {

			$aSchools = $this->getSchools();

			foreach($aSchools as $oSchool) {

				if(!$oSchool->exist()) {
					continue;
				}
				
				$oPrice = new \Ext_Thebing_Price($oSchool);

				if($object->$key == '1') {
					// pro Gebühr

					$aCostList = $oPrice->$sFunctionName();

					foreach($aCostList as $aCost)
					{
						$aData = array(
							'type_id'		=> $aCost['id'],
							'type'			=> 'additional_' . $costType,
							'account_type'	=> $accountType,
						);

						$this->createAllocation($aData);
					}

				} elseif($object->$key == '2') {

					if($costType == 'general') {
						throw new Exception('general costs has no parent!');
					}

					// pro Kurs/Unterkunft
					$aParentIds = $this->$sFunctionNameForParent();

					foreach($aParentIds as $iParentId) {

						$aCostList	= $oPrice->$sFunctionName($iParentId);

						foreach($aCostList as $aCost) {
							$aData = array(
								'type_id'			=> $aCost['id'],
								'type'				=> 'additional_' . $costType,
								'parent_type'		=> $sParentType,
								'parent_type_id'	=> $iParentId,
								'account_type'		=> $accountType,
							);

							$this->createAllocation($aData);
						}
					}
				}

			}
			
		}
		
	}
		
	public function createInsuranceAllocations($type, $object, $key) {
		
		if($object->$key == '1')
		{
			// pro Versicherung
			
			$aInsuranceIds = $this->getInsuranceIds();
			
			foreach($aInsuranceIds as $iInsuranceId)
			{
				$aData = array(
					'type'				=> 'insurance',
					'type_id'			=> $iInsuranceId,
					'account_type'		=> $type,
				);

				$this->createAllocation($aData);
			}
		}
		else
		{
			// einmalig
			
			$this->_createDefaultAllocation('insurance', $type);
		}
	}
	
	public function createOtherAllocations($type) {	
		
		$data = array(
			'type' => 'transfer',
			'account_type' => $type,
		);
	
		$this->createAllocation($data);
		
		$data = array(
			'type' => 'extra_position',
			'account_type' => $type,
		);
		
		$this->createAllocation($data);
		
	}
	
	/**
	 * Alle Kurse zu den gewählten Schulen aus den Kombinationen
	 * 
	 * @return array
	 */
	public function getCourseIds()
	{
		$aCourseIds = $this->_getObjectIdsBySchool('Ext_Thebing_Tuition_Course');
		
		return $aCourseIds;
	}

	/**
	 * Alle Kurskategorien zu den gewählten Schulen aus den Kombinationen
	 * 
	 * @return array 
	 */
	public function getCourseCategoryIds()
	{
		$aCourseCategoryIds = $this->_getObjectIdsBySchool('Ext_Thebing_Tuition_Course_Category');
		
		return $aCourseCategoryIds;
	}
	
	/**
	 * Alle Unterkünfte zu den gewählten Schulen aus den Kombinationen
	 * 
	 * @return array
	 */
	public function getAccommodationIds()
	{
		$aAccommodationIds = $this->_getObjectIdsBySchool('Ext_Thebing_Accommodation');
		
		return $aAccommodationIds;
	}
	
	/**
	 * Alle Unterkunftskategorien zu den gewählten Schulen aus den Kombinationen
	 * 
	 * @return array
	 */
	public function getAccommodationCategoryIds()
	{
		$aAccommodationCategoryIds = $this->_getObjectIdsBySchool('Ext_Thebing_Accommodation_Category');
		
		return $aAccommodationCategoryIds;
	}
	
	/**
	 * Alle Versicherungen
	 * 
	 * @return array
	 */
	public function getInsuranceIds()
	{
		$oInsurance = new \Ext_Thebing_Insurance();
		
		$aInsurances = $oInsurance->getArrayList(true);
		
		$aInsuranceIds = array_keys($aInsurances);
		
		return $aInsuranceIds;
	}

	public function getActivityIds() {

		$oActivity = new \TsActivities\Entity\Activity();

		return $oActivity->getActivitiesForSelect();
	}

	/**
	 * Alle Objekte zu den gewählten Schulen aus den Kombinationen
	 * 
	 * @return array
	 */
	protected function _getObjectIdsBySchool($sObject) {
		
		$aObjectIds	= array();
		
		$oObject = new $sObject();
		
		$aSchools	= $this->getSchools();
		
		foreach($aSchools as $oSchool) {
			//Wenn Schule gesetzt wird, dann holt sich getArrayListSchool nur die Daten aus dieser Schule
			$oObject->setSchoolId($oSchool->id);
			
			$aObjectsBySchool		= $oObject->getArrayListSchool(true);

			$aObjectIdsForSchool	= array_keys($aObjectsBySchool);

			$aObjectIds				= array_merge($aObjectIds, $aObjectIdsForSchool);
		}
		
		array_unique($aObjectIds);
		
		return $aObjectIds;
	}
	
	/**
	 * Methode generiert eine einmalige Zuweisung (pro Kurs ein Konto etc.)
	 * 
	 * @param string $type
	 * @param string $accountType 
	 */
	protected function _createDefaultAllocation($type, $accountType) {
		
		$data = array(
			'type_id'		=> 0,
			'type'			=> $type,
			'account_type'	=> $accountType,
		);

		$this->createAllocation($data);
	}

	/**
	 * Uniquie-Schlüssel für die Zuweisung generieren anhand der Informationen
	 * 
	 * @param array $aAllocation
	 * @return string 
	 */
	public function generateKey(array $aAllocation) {
		
		$sKey = '';
		
		// Ertrag/Aufwand
		$sKey .= $aAllocation['account_type'];
		
		// Leistungs-Typ (Kurs etc.)
		$sKey .= $this->sKeyDelimiter . $aAllocation['type'];
		
		// Leistungs-ID (Kurs-id etc.)
		if(isset($aAllocation['type_id'])) {
			$iTypeId = (int)$aAllocation['type_id'];
		} else {
			$iTypeId = 0;
		}
		
		$sKey .= $this->sKeyDelimiter . $iTypeId;
		
		if(isset($aAllocation['parent_type_id'])) {
			$iParentId = (int)$aAllocation['parent_type_id'];
		} else {
			$iParentId = 0;
		}
		
		$sKey .= $this->sKeyDelimiter . $iParentId;
		
		if(empty($aAllocation['parent_type'])) {
			$sParentType = 'no_parent';
		} else {
			$sParentType = (string)$aAllocation['parent_type'];
		}
		
		$sKey .= $this->sKeyDelimiter . $sParentType;
		
		if(isset($aAllocation['currency_iso'])) {
			$sKey .= $this->sKeyDelimiter . $aAllocation['currency_iso'];
		}
		
		if(isset($aAllocation['vat_rate'])) {
			$sKey .= $this->sKeyDelimiter . $aAllocation['vat_rate'];
		}
		
		return $sKey;
	}
	
	/**
	 * Überprüfen ob der Key in den Zuweisungen vorhanden ist
	 * 
	 * @return bool
	 */
	public function hasKey($sKey) {
		$allocations = $this->getAllocations();
		return isset($allocations[$sKey]);
	}
	
	public function getAllocation($sKey) {
		$allocations = $this->getAllocations();
		return $allocations[$sKey];
	}
    
	public function getGroupedAllocations(array $allocations) {
		
		if($this->groupedAllocations === null) {
			$aGrouped = array(
				'income'		=> array(),
				'expense'		=> array(),
				'continuance'	=> array(),
				'clearing'	=> array(),
			);

			foreach($allocations as $sKey => $aAllocation) {

				unset($aAllocation['vat_rate'], $aAllocation['currency_iso']);

				$sKeyGroup = $this->generateKey($aAllocation);

				$sAccountType = $aAllocation['account_type'];

				$sGroupKey = $aAllocation['type'] . '_allocations';

				if(!isset($aGrouped[$sAccountType][$sGroupKey]))
				{
					$aGrouped[$sAccountType][$sGroupKey] = array();
				}

				$iIdField		= 0;
				$iTypeId		= 0;
				$iParentTypeId	= 0;

				if(isset($aAllocation['type_id']))
				{
					$iTypeId		= (int)$aAllocation['type_id'];
				}

				if(isset($aAllocation['parent_type_id']))
				{
					$iParentTypeId	= (int)$aAllocation['parent_type_id'];
				}

				if($iParentTypeId > 0)
				{
					$iIdField	= $iParentTypeId;
					$sTypeField	= $aAllocation['parent_type'];
				}
				elseif($iTypeId > 0)
				{
					$iIdField	= $iTypeId;
					$sTypeField	= $aAllocation['type'];
				}

				if($iIdField > 0)
				{
					$iSchoolId = $this->_getSchoolIdOfType($sTypeField, $iIdField);

					if($iSchoolId)
					{
						if(!isset($aGrouped[$sAccountType][$sGroupKey]['school_data']))
						{
							$aGrouped[$sAccountType][$sGroupKey]['school_data'] = array();
						}

						if(!isset($aGrouped[$sAccountType][$sGroupKey]['school_data'][$iSchoolId]))
						{
							$aGrouped[$sAccountType][$sGroupKey]['school_data'][$iSchoolId] = array();
						}

						if($iTypeId > 0 && $iParentTypeId > 0)
						{
							if(!isset($aGrouped[$sAccountType][$sGroupKey]['school_data'][$iSchoolId]['parent_data']))
							{
								$aGrouped[$sAccountType][$sGroupKey]['school_data'][$iSchoolId]['parent_data'] = array();
							}	

							if(!isset($aGrouped[$sAccountType][$sGroupKey]['school_data'][$iSchoolId]['parent_data'][$iParentTypeId]))
							{
								$aGrouped[$sAccountType][$sGroupKey]['school_data'][$iSchoolId]['parent_data'][$iParentTypeId] = array();
							}	

							$aGrouped[$sAccountType][$sGroupKey]['school_data'][$iSchoolId]['parent_data'][$iParentTypeId][$sKeyGroup][] = $sKey;
						}
						else
						{
							$aGrouped[$sAccountType][$sGroupKey]['school_data'][$iSchoolId][$sKeyGroup][] = $sKey;
						}
					}
					else
					{
						if(!isset($aGrouped[$sAccountType][$sGroupKey]['elements']))
						{
							$aGrouped[$sAccountType][$sGroupKey]['elements'] = array();
						}

						$aGrouped[$sAccountType][$sGroupKey]['elements'][$sKeyGroup][] = $sKey;
					}
				}
				else
				{
					if(!isset($aGrouped[$sAccountType][$sGroupKey]['elements']))
					{
						$aGrouped[$sAccountType][$sGroupKey]['elements'] = array();
					}

					$aGrouped[$sAccountType][$sGroupKey]['elements'][$sKeyGroup][] = $sKey;
				}
			}

			$this->groupedAllocations = $aGrouped;
		}

		return $this->groupedAllocations;
	}
	
	protected function _getSchoolIdOfType($sType, $iTypeId) {
		
		$iSchoolId		= 0;
		
		$sClass			= false;
		
		switch($sType) {
			case 'course':
				$sClass				= 'Ext_Thebing_Tuition_Course';
				break;
			case 'course_category':
				$sClass				= 'Ext_Thebing_Tuition_Course_Category';
				break;
			case 'accommodation':
				$sClass				= 'Ext_Thebing_Accommodation';
				break;
			case 'accommodation_category':
				$sClass				= 'Ext_Thebing_Accommodation_Category';
				break;
			case 'additional_course':
			case 'additional_accommodation':
			case 'additional_general':
				$sClass				= 'Ext_Thebing_School_Additionalcost';
				break;
			default:
				break;
		}
		
		if($sClass) {
			$oObject			= new $sClass();

			$sSchoolField		= $oObject->_checkSchoolIdField();

			$iSchoolId			= (int)$this->_getDataFromObjectClass($sClass, $iTypeId, $sSchoolField);
		}
		
		return $iSchoolId;
	}

	/**
	 * @param $sClass
	 * @param $iTypeId
	 * @param $sField
	 * @param bool $bByObject Wird genutzt um i18n Tabellen nutzen zu können. Indem der Name über den Magic getter geholt wird.
	 * @return bool
	 */
	protected function _getDataFromObjectClass($sClass, $iTypeId, $sField, $bByObject = false) {
		
		$mData		= false;
		
		$oObject	= new $sClass();

		if($bByObject) {
			$sName = $sClass::getInstance($iTypeId)->$sField;
			$aList[$iTypeId] = $sName;
		} else {
			$aList = $oObject->getArrayList(true, $sField);
		}

		if(isset($aList[$iTypeId])) {
			$mData = $aList[$iTypeId];
		}
		
		return $mData;
	}
	
	public function getAccountName($mKey)
	{
		$sName = '';
		
		/**
		 * 0 => account_type
		 * 1 => type
		 * 2 => type_id
		 * 3 => parent_type_id
		 * 4 => parent_type
		 * 5 => currency_iso
		 * 6 => vat_rate
		 */
		
		if(is_array($mKey))
		{
			$aInfo				= $mKey;
		}
		else
		{
			$aInfo				= explode($this->sKeyDelimiter, $mKey);
		}

		$sAccountType		= (string)$aInfo[0];
		$sType				= (string)$aInfo[1];
		$iTypeId			= (int)$aInfo[2];
		$iParentTypeId		= (int)$aInfo[3];
		$sParentType		= (string)$aInfo[4];

		switch($sType)
		{
			case 'course':
			
				if($iTypeId > 0) {
					$sName = $this->_getDataFromObjectClass('Ext_Thebing_Tuition_Course', $iTypeId, $this->_getNameField());
				} elseif($iParentTypeId > 0) {
					$sName = $this->_getDataFromObjectClass('Ext_Thebing_Tuition_Course_Category', $iParentTypeId, $this->_getNameField());
				}

				break;
				
			case 'accommodation':
			
				if($iParentTypeId > 0)
				{
					$sName = $this->_getDataFromObjectClass('Ext_Thebing_Accommodation_Category', $iParentTypeId, $this->_getNameField());
				}

				break;
				
			case 'additional_course':
			case 'additional_general':
				
				if($iTypeId > 0)
				{
					$sName = $this->_getDataFromObjectClass('Ext_Thebing_School_Additionalcost', $iTypeId, $this->_getNameField());
				}
				elseif($iParentTypeId > 0)
				{
					$sName = $this->_getDataFromObjectClass('Ext_Thebing_Tuition_Course', $iParentTypeId, $this->_getNameField());
				}
				
				break;
				
			case 'additional_accommodation':

				if($iTypeId > 0)
				{
					$sName = $this->_getDataFromObjectClass('Ext_Thebing_School_Additionalcost', $iTypeId, $this->_getNameField());
				}
				elseif($iParentTypeId > 0)
				{
					$sName = $this->_getDataFromObjectClass('Ext_Thebing_Accommodation_Category', $iParentTypeId, $this->_getNameField());
				}
				
				break;
			
			case 'insurance':
				
				$sName = $this->_getDataFromObjectClass('Ext_Thebing_Insurance', $iTypeId, $this->_getNameField());

				break;

			case 'activity':

				$sName = $this->_getDataFromObjectClass('\TsActivities\Entity\Activity', $iTypeId, $this->_getNameField(), true);

				break;
			
			case 'cancellation':
				
				if($sParentType == 'course')
				{
					$sName = $this->_t('Kurs');
				}
				elseif($sParentType == 'accommodation')
				{
					$sName = $this->_t('Unterkunft');
				}
				elseif($sParentType == 'all')
				{
					$sName = $this->_t('Alles');
				}
				else
				{
					$aInfo[1] = $sParentType;
					$aInfo[2] = $iParentTypeId;
					$aInfo[3] = 0;
					$aInfo[4] = '';
				
					$sName = $this->getAccountName($aInfo);
				}
			
				break;
				
			case 'customer_active':
				
				$sName = $this->_t('Kunde – Aktive Verbuchung');
				
				break;
			
			case 'agency_active':
				
				$sName = $this->_t('Agentur – Aktive Verbuchung');
				
				break;
			
			case 'agency_passive':
				
				$sName = $this->_t('Agentur – Passive Verbuchung');
				
				break;
			
			case 'accrual_account_active':
				
				$sName = $this->_t('Aktives Rechnungsabgrenzungskonto');
				
				break;
			
			case 'accrual_account_passive':
				
				$sName = $this->_t('Passives Rechnungsabgrenzungskonto');
				
				break;
			
			case 'vat':
				
				if(isset($this->_aVatRates[$iTypeId]))
				{
					$sName = $this->_aVatRates[$iTypeId];
				}

				break;

			case 'payment_method':

				$sName = $this->_getDataFromObjectClass('Ext_Thebing_Admin_Payment', $iTypeId, 'name');

				break;
			default:
				
				break;
		}
		
		return $sName;
	}
	
	protected function _getNameField() {
		
		$school = \Ext_Thebing_School::getSchoolFromSessionOrFirstSchool();
		
		$lang = $school->getInterfaceLanguage();
		
		return 'name_' . $lang;
	}
	
	abstract protected function getSchools();

	abstract public function createAllocation($aAllocationData);
	
	abstract public function getAllocations();

}
