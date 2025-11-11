<?php

class Ext_TC_Gui2_Selection_Gender extends Ext_Gui2_View_Selection_Abstract {

	
    public function getOptions($aSelectedIds, $aSaveField, &$oWDBasic)
    {

		$sLanguage = System::getInterfaceLanguage();

		if(System::getInterface() === 'frontend') {
			$oLanguageObject = new \Tc\Service\Language\Frontend($sLanguage);
		} else {
			$oLanguageObject = new \Tc\Service\Language\Backend($sLanguage);
		}

		$aSelection = Ext_TC_Util::getGenders(true, '', $oLanguageObject);

		return $aSelection;

	}
	
}
