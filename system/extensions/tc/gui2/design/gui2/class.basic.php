<?php

abstract class Ext_TC_Gui2_Design_Gui2_Basic extends Ext_TC_Gui2 {

	protected $_bFirstDisplay = true;
	

	public function __construct($sDataClass){
		// Hash MUSS Fix bleiben
		$sHash = $this->getHash();
		
		parent::__construct($sHash, $sDataClass, '');

		$this->setConstructData();

	}
	
	/**
	 * Muss abgeleitet werden!
	 */
	public abstract function getHash();
	
	/**
	 * Muss abgeleitet werden!
	 */
	public abstract function setConstructData();
	
	/**
	 * Muss abgeleitet werden!
	 */
	protected abstract function getDialog($bNew = true);
	
	/**
	 * Muss abgeleitet werden!
	 */
	protected abstract function setDefaultBars();
	
	public function startPageOutput($bFirst = true){
		
		if($this->_bFirstDisplay){
			$this->setDefaultBars();
			$this->addDefaultColumns();
			$this->_bFirstDisplay = false;
		}
		
		return parent::startPageOutput($bFirst);
	}
	
	public function display($aOptionalData = array(), $bNoJavaScript = false) {
		
		if($this->_bFirstDisplay){
			$this->setDefaultBars();
			$this->addDefaultColumns();
			$this->_bFirstDisplay = false;
		}
		
		parent::display($aOptionalData, $bNoJavaScript);
		
	}
	
	public function addDefaultColumns($sTableAlias='', $oColumnGroup = NULL) {
				
		parent::addDefaultColumns($sTableAlias, $oColumnGroup);
	}

	public function getAccess(){
		
		$aAccess = $this->access;
		$sAccess = $aAccess[0];
		
		if(empty($sAccess)){
			$sAccess = 'core_gui2_designer';
		}
		
		return $sAccess;
	}
	
	
	
	
	/**
	 *
	 * @param Ext_Gui2_Dialog $oDialog 
	 */
	public function setAdditionalTabsByRef(&$oDialog){
		
	}
	
	/**
	 *
	 * @param Ext_Gui2_Bar_Icon $oIconBar 
	 */
	public function setAdditionalIconsByRef(&$oIconBar){
		
	}

	/**
	 *
	 * @param Ext_Gui2_Dialog_Tab $oTab
	 * @param Ext_Gui2_Dialog $oDialog 
	 */
	public function setAdditionalTabRowsByRef(&$oTab, &$oDialog){
		
	}
	
	
}