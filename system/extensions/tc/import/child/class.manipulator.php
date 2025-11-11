<?php
class Ext_TC_Import_Child_Manipulator {
	
	protected $_aManipulators = array();


	public function setManipulator($oFunction){
		$this->_aManipulators[] = $oFunction;
	}
	
	public function manipulate($aData, $sChild){
		foreach($this->_aManipulators as $oFunction){
			$aData = $oFunction($aData, $sChild);
		}
		return $aData;
	}
}