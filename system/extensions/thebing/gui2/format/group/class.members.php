<?php

class Ext_Thebing_Gui2_Format_Group_Members extends Ext_Gui2_View_Format_Abstract {

	public static $iLastGroupId = 0;
	public static $iLastGroupMembers = 0;
	// Liefert die Anzahl der Gruppenmitglieder anhand der ID
	public function format($mValue, &$oColumn = null, &$aResultData = null){

		if($aResultData['id'] != self::$iLastGroupId){
			
			$aInquirys = Ext_Thebing_Inquiry_Group::getInquiriesOfGroup($aResultData['id']);

			// Erebnis in Cash speichern
			self::$iLastGroupId = $aResultData['id'];
			self::$iLastGroupMembers = count($aInquirys);
		}

		return (string)self::$iLastGroupMembers;

	}

}