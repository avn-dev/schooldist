<?php
/**
 * UML - https://redmine.thebing.com/redmine/issues/272
 */
class Ext_TC_Frontend_Mapping_Field extends Ext_TC_Mapping_Field {
		
	protected $_oSelection;
	protected $_bSelectionEmptyEntry = false;

	/**
	 *
	 * @param array $aConfig 
	 */
	public function __construct(array $aConfig, $bOriginal=false) {
		$sType = $this->_getTypeFromDbType($aConfig['Type']);

		$this->addConfig('type', $sType);
		
		$this->_bIsOriginal = $bOriginal;
	}
	
	/**
	 * Von den Describe Informationen über SQL rausfinden, welcher IndexTyp benutzt werden muss
	 * @param type $sDbType
	 * @return string 
	 */
	protected function _getTypeFromDbType($sDbType) {

		switch(true) {
			case strpos($sDbType, 'text') !== false:
				$sType = 'text';
				break;
			default:
				$sType = parent::_getTypeFromDbType($sDbType);
		}
		
		return $sType;
	}
	
	/**
	 * set a selection class
	 * @param Ext_Gui2_View_Selection_Abstract $oSelection 
	 */
	public function setSelection(Ext_Gui2_View_Selection_Abstract $oSelection, $bEmptyEntry = false){
		$this->_oSelection = $oSelection;
		$this->_bSelectionEmptyEntry = $bEmptyEntry;
	}
	
	/**
	 * return the Selection Class
	 * @return Ext_Gui2_View_Selection_Abstract 
	 */
	public function getSelection(){
		$oSelection = $this->_oSelection;
		if(empty($oSelection)) {
			$oSelection = false;
		}
		return $oSelection;
	}
	
	/**
	 * @return boolean 
	 */
	public function checkSelectionEmptyEntry(){
		return $this->_bSelectionEmptyEntry;
	}
	
	/**
	 * no config key check!
	 * @param string $sConfig
	 * @param mixed $mValue 
	 */
	public function addConfig($sConfig, $mValue) {
		$this->_aValues[$sConfig] = $mValue;
	}
	
	/**
	 * no config key check!
	 * @param string $sConfig
	 * @param mixed $mValue 
	 */
	public function changeConfig($sConfig, $mValue) {
		$this->_aValues[$sConfig] = $mValue;
	}
	
	/**
	 *
	 * @param string $sConfig
	 * @return mixed 
	 */
	public function getConfig($sConfig) {
		if($this->hasConfig($sConfig)) {
			return $this->_aValues[$sConfig];
		}
		
		return '';
	}
	
	public function hasConfig($sConfig) {
		if(isset($this->_aValues[$sConfig])) {
			return true;
		}
		
		return false;
	}
}
