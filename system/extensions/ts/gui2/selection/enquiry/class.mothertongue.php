<?php

class Ext_TS_Gui2_Selection_Enquiry_Mothertongue extends Ext_Gui2_View_Selection_Abstract {

	/**
	 *
	 * @param type $aSelectedIds
	 * @param type $aSaveField
	 * @param Ext_TS_Enquiry $oWDBasic
	 * @return array
	 */
    public function getOptions($aSelectedIds, $aSaveField, &$oWDBasic) {

		$oTraveller = $oWDBasic->getFirstTraveller();
		$sMothertongue = '';

		if($oTraveller->nationality != ''){
			$sMothertongue = Ext_Thebing_Nationality::getMotherTonguebyNationality($oTraveller->nationality);
		}		

		#$oSchool = Ext_Thebing_School::getSchoolFromSession();
		$sLanguage = Ext_TC_System::getInterfaceLanguage();
		
		$aLangsTemp	= Ext_Thebing_Data::getLanguageSkills(true, $sLanguage);
		$aLangs = array();
		
		foreach($aLangsTemp as $sKey => $sValue){
			$aLangs[$sKey] = array();
			$aLangs[$sKey]['text'] = $sValue;
			if(
				$sKey == $sMothertongue
			){
				$aLangs[$sKey]['selected'] = true;
				// Sprache muss auch in der Obj übernommen werden, weil sie sonst in der Korresp. Selection
				// nicht zur Verfügung steht
				$oTraveller->language = $sKey;
			}
		}
		
		return $aLangs;

	}

}