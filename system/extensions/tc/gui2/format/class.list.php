<?php

/**
 * Formatklasse ist dazu da, anhand eines WDBasic objects einen Wert zu holen, OHNE dass
 * die Entsprechende Tabelle selektiert werden muss
 */

class Ext_TC_GUI2_Format_List extends Ext_Gui2_View_Format_Abstract {

	protected $_sWDBasicIdField;
	protected $_sNameField;
	protected $_oWDBasic;

	public function __construct($mWDBasic, $sWDBasicIdField, $sNameField){
		
		if(
			is_object($mWDBasic) &&
			$mWDBasic instanceof WDBasic
		){
			$this->_oWDBasic			= $mWDBasic;
		}else{
			$this->_oWDBasic			= new $mWDBasic();
		}
		
		$this->_sWDBasicIdField			= $sWDBasicIdField;
		$this->_sNameField				= $sNameField;
	}

	public function format($mValue, &$oColumn = null, &$aResultData = null){
		
		$oObject = $this->_oWDBasic;

		$aArrayList = (array)$oObject->getArrayList();
				
		if(isset($aResultData[$this->_sWDBasicIdField])){
			
			$iWDBasicId = $aResultData[$this->_sWDBasicIdField];
			
			if(
				isset($aArrayList[$iWDBasicId]) &&
				isset($aArrayList[$iWDBasicId][$this->_sNameField])
			)
			{
				return $aArrayList[$iWDBasicId][$this->_sNameField];
			}
			
		}
		
		
		return false;

	}

}