<?php

/**
 * @deprecated
 *
 * Klasse, um aus dem ISO-Code den Namen eines Landes zu erhalten
 */
class Ext_TC_Gui2_Format_Country extends Ext_TC_Placeholder_Format_Abstract {

	public function __construct($sLang = '')
	{
		$this->setDisplayLanguage($sLang);
	}
	
	public function format($mValue, &$oColumn = null, &$aResultData = null) {
		
		$aCountry = Ext_TC_Country::getCountryByIso($mValue);
		
		if(!empty($aCountry['cn_short_'.$this->_sLanguage])) {
			$sReturn = $aCountry['cn_short_'.$this->_sLanguage];
		} else {
			$sReturn = $aCountry['cn_short_en'];
		}
		
		return $sReturn;
		
	}

}
