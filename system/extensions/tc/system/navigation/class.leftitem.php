<?php 
/**
 * 
 */
class Ext_TC_System_Navigation_LeftItem extends Ext_TC_System_Navigation_Item_Abstract {
	
	/**
	 * The Startstring for the L10N
	 * @var String
	 */
	public $sL10NStart  = "";
	public $mAccess		= "";
	public $sTitle		= "";
	public $sL10NAddon	= "";
	public $sUrl		= "";
	public $sFontawesomeKey = "fa-angle-right";
	public $sKey = "";
	public $sType = "url";
	public $iSubpoint = 0;

	/**
	 * Get an Array for the CMS Navigation
	 * @return array
	 */
	public function getArray(){
		$aBack = array();
		$aBack[0]	= $this->generateTitle();
		$aBack[1]	= $this->sUrl;//link
		$aBack[2]	= $this->iSubpoint;// subpoint
		$aBack[3]	= $this->mAccess;//right
		$aBack[4]	= $this->sFontawesomeKey;
		$aBack[5]	= $this->sKey;
		$aBack[6]	= $this->sType;

		return $aBack;
	}
	
}