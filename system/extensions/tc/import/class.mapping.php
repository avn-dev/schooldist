<?php

class Ext_TC_Import_Mapping {
	
	protected $_aMapping		= array();
	protected $_iPrimaryColumn	= null;
	protected $_sPrimaryType	= 'int';
	protected $_aChilds			= array();
	protected $_aFixFields	= array();
	protected $_aColumnNumbers = array();
	protected $_aDataFieldCache = array();
	protected $_aDataFieldRecrusiveCache = array();

	/**
	 * @param Ext_TC_Import_Abstract $oImport 
	 */
	public function setChild($oImport, $bUnique = false, $oManipulator = null){
		$oEntity = $oImport->createEntity();
		$sEntity = $oEntity->getTableName();
		$sChild  = $sEntity.'_'.$oImport->getNumber();
		$this->_aChilds[$sChild]['import']		= $oImport;
		$this->_aChilds[$sChild]['unique']		= $bUnique;
		$this->_aChilds[$sChild]['manipulator'] = $oManipulator;
	}
	
	/**
	 * gibt die Festen Spalten zurück
	 * @return type 
	 */
	public function getFixColumns(){
		return $this->_aFixFields;
	}
	
	public function getColumnNumbers(){
		return $this->_aColumnNumbers;
	}
	
	public function getChildDataManipulator($sChild){
		return $this->_aChilds[$sChild]['manipulator'];
	}
	
	/**
	 *
	 * @param string $sChild
	 * @return Ext_TC_Import_Abstract 
	 */
	public function getChildImport($sChild){
		return $this->_aChilds[$sChild]['import'];
	}
	
	/**
	 * schaut ob das Kind nur einmalig eingefügt werden muss
	 * @param type $sChild
	 * @return type 
	 */
	public function isChildUnique($sChild){
		return $this->_aChilds[$sChild]['unique'];
	}

	/**
	 * holt alle kinder
	 * @return type 
	 */
	public function getChilds(){
		return $this->_aChilds;
	}
	
	/**
	 * holt ein kind anhand der column nummer
	 * @param type $iColumnNumber
	 * @return null 
	 */
	public function getChildsForColumn($iColumnNumber){
		$aChilds = null;
		foreach($this->_aChilds as $sChild => $aChild){
			$sField = $aChild['import']->getMapping()->getDataFieldRecrusive($iColumnNumber);
			if($sField != null){
				$aChilds[] = $sChild;
			}
		}
		return $aChilds;
	}
	/**
	 *setzt eine Spalte mit information spalte - datenbank feld - transformer
	 * @param type $iColumnNumber
	 * @param type $sDataField
	 * @param Ext_TC_Import_Field_Transformer $oTransformer
	 * @throws Exception 
	 */
	public function setColumn($iColumnNumber, $sDataField, $oTransformer = null){
		if(!is_int($iColumnNumber)){
			throw new Exception('Wrong Column Number');
		}
		if(!is_string($sDataField)){
			throw new Exception('Wrong Data Field format');
		}
		if(!$oTransformer){
			$oTransformer = new Ext_TC_Import_Field_Transformer();
		}
		$this->_aMapping[$sDataField]['transformer']	= $oTransformer;
		$this->_aMapping[$sDataField]['columns'][]		= $iColumnNumber;
		$this->_aColumnNumbers[$iColumnNumber]			= $iColumnNumber;
	}
		
	/**
	 * setzt fixe spalten die immer fest befüllt werden
	 * @param type $aColumns 
	 */
	public function setFixColumns($aColumns){
		$this->_aFixFields = $aColumns;
	}

		/** 
	 * hold das datenbank feld zu einer spalte
	 * @param type $iColumnNumber
	 * @return type
	 * @throws Exception 
	 */
	public function getDataField($iColumnNumber){
		
		if(!is_int($iColumnNumber)){
			throw new Exception('Wrong Column Number');
		}
		
		$aCache = $this->_aDataFieldCache;
		
		if(!isset($aCache[$iColumnNumber])){
			foreach($this->_aMapping as $sDataField => $aColumnsNumbers){
				if(in_array($iColumnNumber, $aColumnsNumbers['columns'])){
					$this->_aDataFieldCache[$iColumnNumber] = $sDataField;
					break;
				}
			}
		}
		
		return $this->_aDataFieldCache[$iColumnNumber];
	}
	
	public function getDataFieldRecrusive($iColumnNumber){
		
		$aCache = $this->_aDataFieldRecrusiveCache;
		
		if(!isset($aCache[$iColumnNumber])){
			$sDataField = $this->getDataField($iColumnNumber);
			if($sDataField === null){
				foreach($this->_aChilds as $aChild){
					$sDataField = $aChild['import']->getMapping()->getDataField($iColumnNumber);
					if($sDataField != null){
						$this->_aDataFieldRecrusiveCache[$iColumnNumber] =  $sDataField;
						break;
					}
				}
			} else {
				$this->_aDataFieldRecrusiveCache[$iColumnNumber] =  $sDataField;
			}
		}
		
		return $this->_aDataFieldRecrusiveCache[$iColumnNumber];
	}
	
	/**
	 * holt den transformer zu einer Spalte
	 * @param type $iColumnNumber
	 * @return type
	 * @throws Exception 
	 */
	public function getTransformer($iColumnNumber){
		if(!is_int($iColumnNumber)){
			throw new Exception('Wrong Column Number');
		}
		foreach($this->_aMapping as $aColumnsNumbers){
			if(in_array($iColumnNumber, $aColumnsNumbers['columns'])){
				$oTransformer = $aColumnsNumbers['transformer'];
				return $oTransformer;
			}
		}
	}
	
	/**
	 * setzt das Primär Feld, wenn keins vorhanden muss NULL übergeben werden
	 * @param type $iColumnNumber
	 * @param type $sType
	 * @throws Exception 
	 */
	public function setPrimaryColumn($iColumnNumber, $sType = 'int'){
		if(!is_int($iColumnNumber) && $iColumnNumber !== null) {
			throw new Exception('Wrong Column Number for Primary');
		}
		if(!is_string($sType)){
			throw new Exception('Wrong Column Type for Primary');
		}
		$this->_iPrimaryColumn	= $iColumnNumber;
		$this->_sPrimaryType	= $sType;
	}
	
	/**
	 * gibt den definierten Primär Spalten Typ aus
	 * @return type 
	 */
	public function getPrimaryType(){
		return $this->_sPrimaryType;
	}
	
	/**
	 * gibt die nummer der definierten Primär Spalte aus
	 * @return type 
	 */
	public function getPrimaryNumber(){
		return $this->_iPrimaryColumn;
	}
	
	/**
	 * schaut ob die Spalte definiert wurde zum importieren
	 * @param type $iColumnNumber
	 * @return boolean 
	 */
	public function isImportable($iColumnNumber){
		$sDataField = $this->getDataField($iColumnNumber);
		if($sDataField !== null){
			return true;
		}
		return false;
	}
}