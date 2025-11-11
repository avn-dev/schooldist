<?php

class Ext_Thebing_Gui2_Format_Group_Accommodationstart extends Ext_Gui2_View_Format_Abstract {

	public static $iLastGroupId = 0;
	public static $iLastGroupAccStart = 0;

	// frühester Unterkunftsbeginn
	public function format($mValue, &$oColumn = null, &$aResultData = null){

		if($aResultData['id'] != self::$iLastGroupId){

			$oSchool				= Ext_Thebing_School::getSchoolFromSession();
			$oGroup					= Ext_Thebing_Inquiry_Group::getInstance($aResultData['id']);
			$aAccommodations		= $oGroup->getAccommodations();
			
			$aAccStarts = array();
			foreach((array) $aAccommodations as $aAccommodation){
				$aAccStarts[] = $aAccommodation['from'];
			}
			sort($aAccStarts);

			$iFirst = (int)reset($aAccStarts);
			if($iFirst > 0){
				$sFromDate = Ext_Thebing_Format::LocalDate($iFirst, $oSchool->id);
			}else{
				$sFromDate = '';
			}

			// Erebnis in Cash speichern
			self::$iLastGroupId = $aResultData['id'];
			self::$iLastGroupAccStart = $sFromDate;
		}

		return (string)self::$iLastGroupAccStart;

	}

}
