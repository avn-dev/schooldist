<?php 
abstract class Ext_TC_System_Navigation_Item_Abstract implements Ext_TC_System_Navigation_Item_Interface {
	
	public $bTranslate = true;
	
	protected $_sL10NStart = "";
	
	public function __construct() {
				
	}
	
	public function generateTitlePath(){
		
		$sAddon = $this->sL10NAddon;
		
		if(empty($sAddon) && $this->bTranslate === true)
		{
			#throw new Exception("Nav Item Part Error!");
		}
		
		#$sPath = $this->_sL10NStart.$this->sL10NAddon;
		
		$sPath = $this->sL10NAddon;
		
		return $sPath;
	}
	
	public function generateTitle(){
		
		$sReturn = $this->sTitle;

		if($this->bTranslate === true) {
			$sReturn = L10N::t($sReturn, $this->generateTitlePath());
		}
		
		return $sReturn;
	}
	
	public function setL10NStart($sL10NStart){
		$this->_sL10NStart = $sL10NStart;
	}

	public function getArray(){
		$aBack = array();
		return $aBack;
	} 
	
	
}
