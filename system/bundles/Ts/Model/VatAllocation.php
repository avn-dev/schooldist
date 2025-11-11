<?php

namespace Ts\Model;

/**
 * Fake-Dialog für Zuweisungsdialog
 */
class VatAllocation extends \Ext_TS_Vat_Combination {
	
	protected $_sPrimaryColumn = 'country_iso';
	
	protected $_aCombinations = [];

	public function __construct($iDataID = 0, $sTable = null, $bAutoFormat = false) {

		parent::__construct($iDataID, $sTable, $bAutoFormat);
		
		$this->_aJoinedObjects = [];
		
		$aSchools = \Ext_TS_Vat_Gui2_Data::getSchools();
		$aCountries = \Ext_TS_Vat_Gui2_Data::getSchoolCountries($aSchools);
		
		foreach($aCountries as $sIso => $sValue) {
			$this->_aJoinedObjects['commission_vats_'.$sIso] = [
				'class'=>'\Ext_TS_Vat_Combination',
	 			'key'=>'country_iso',
	 			'type'=>'child',
	 			'check_active'=>true,
	 			'static_key_fields' => [
					'country_iso' => $sIso
				],
			];
		}
		
	}
	
	public function __set($sName, $mValue) {

		if(strpos($sName, 'ts_vat_') === 0) {
			\System::s($sName, $mValue);
		} else {
			parent::__set($sName, $mValue);
		}
		
	}
	
	public function __get($sName) {
		
		if(strpos($sName, 'ts_vat_') === 0) {
			return \System::d($sName);
		} else {
			return parent::__get($sName);
		}
		
	}
	
	public function getJoinedObject($sMixed, $sKey = null) {
		
		if(empty($sMixed)) {
			return $this;
		}

		list($sIso, $iVatRateId) = explode('_', $sMixed, 2);

		if(empty($iVatRateId)) {
			return parent::getJoinedObject($sMixed, $sKey);
		}

		if(!isset($this->_aJoinedObjects[$sMixed])) {
			
			$aCriteria = [
				'country_iso'=>$sIso, 
				'vat_rate_id' => (int)$iVatRateId,
				'commission_vat'=>null
			];
			
			$oObject = \Ext_TS_Vat_Combination::getRepository()->findOneBy($aCriteria);
			
			if(empty($oObject)) {
				$oObject = new \Ext_TS_Vat_Combination;
				$oObject->vat_rate_id = $iVatRateId;
				$oObject->country_iso = $sIso;
			}
			
			$this->_aJoinedObjects[$sMixed] = array(
				'class'=>'Ext_TS_Vat_Combination',
				'type'=>'parent',
				'object'=> $oObject
			);

		}
		
		return $this->_aJoinedObjects[$sMixed]['object'];
	}
	
	public function getJoinedObjectChilds($sMixed = null, $bCheckCache=false) {

		if(strpos($sMixed, 'commission_vats_') === 0) {
			
			$sIso = str_replace('commission_vats_', '', $sMixed);
			$this->country_iso = $sIso;
			
		}
		
		$aObjects = parent::getJoinedObjectChilds($sMixed, $bCheckCache);
		
		if(strpos($sMixed, 'commission_vats_') === 0) {

			$aObjects = array_filter($aObjects, function($oObject) {
				if(empty($oObject->commission_vat)) {
					return false;
				}
				return true;
			});

		}
		
		return $aObjects;
	}

	public function getJoinedObjectChild($sKey, $mJoinedObject=0, $sJoinedObjectCacheKey=null, $bSkipBidirectionalCheck=false) {

		$oObject = parent::getJoinedObjectChild($sKey, $mJoinedObject, $sJoinedObjectCacheKey, $bSkipBidirectionalCheck);
		
		if(strpos($sKey, 'commission_vats_') === 0) {

			$sIso = str_replace('commission_vats_', '', $sKey);

			$oObject->country_iso = $sIso;
			
		}
		
		return $oObject;
	}
	
	/**
	 * Verknüpfte Objekte werden gespeichert, eigentliches Objekt nicht!
	 * 
	 * @param type $bLog
	 * @return boolean
	 */
	public function save($bLog = true) {

		$this->saveJoinedObjectChilds();
		
		$this->saveParents();
		
		return true;//$bSuccess;
	}
	
}
