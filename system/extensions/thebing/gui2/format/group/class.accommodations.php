<?php

class Ext_Thebing_Gui2_Format_Group_Accommodations extends Ext_Gui2_View_Format_Abstract {

	public static $iLastGroupId = 0;
	public static $aLastGroupAccommodations = array();

	// Liefert die Anzahl der Gruppenführer anhand der ID
	public function format($mValue, &$oColumn = null, &$aResultData = null){

		if(!isset(self::$aLastGroupAccommodations[$aResultData['id']])) {

			$oSchool				= Ext_Thebing_School::getSchoolFromSession();
			$oGroup					= Ext_Thebing_Inquiry_Group::getInstance($aResultData['id']);

			$aAccommodationList		= $oSchool->getAccommodationList();
			$aAccommodations		= $oGroup->getAccommodations();
			
			$aAccNames = array();
			foreach((array) $aAccommodations as $aAccommodation){
				
				$aAccNames[] = $aAccommodationList[$aAccommodation['accommodation_id']];

			}

			// Erebnis in Cash speichern
			self::$aLastGroupAccommodations[$aResultData['id']] = implode('<br/>', $aAccNames);

		}

		return (string)self::$aLastGroupAccommodations[$aResultData['id']];

	}

}
