<?php

/**
 * Zentralisierung der Ext_Thebing_Gui2_Format_Url
 */
class Ext_TC_Gui2_Format_Url extends Ext_TC_Gui2_Format {

	protected $_sLinkTitle;
	protected $_sLinkTarget;

	public function __construct($sLinkTitle=false, $sLinkAddon='',  $sTarget='_blank'){
		$this->_sLinkTitle	= $sLinkTitle;
		$this->_sLinkTarget = $sTarget;
		$this->_sLinkAddon	= $sLinkAddon;
	}

	public function format($mValue, &$oColumn = null, &$aResultData = null){

		$sLinkTitle  = $this->_sLinkTitle;
		$sLinkAddon = $this->_sLinkAddon;

		$sLink = '';
		
		if(!empty($mValue)) {
			if(mb_substr($mValue,mb_strlen($mValue)-1)!='/'){
				$mValue .= '/';
			}

			if(!empty($sLinkAddon)){
				$mValue	.= $this->_sLinkAddon;
			}

			if(empty($sLinkTitle)){
				$sLinkTitle = $mValue;
			}else{
				$sLinkTitle = $aResultData[$sLinkTitle];
			}

			$sLink =  '<a href="'.$mValue.'" target="'.$this->_sLinkTarget.'">'.$sLinkTitle.'</a>';

		}
		
		return $sLink;
		
	}
	
}