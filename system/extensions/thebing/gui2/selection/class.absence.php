<?php

class Ext_Thebing_Gui2_Selection_Absence extends Ext_Gui2_View_Selection_Abstract {

	public function getOptions($aSelectedIds, $aSaveField, &$oWDBasic) {
		global $_VARS, $user_data;

		if($oWDBasic->item === 'accommodation') {
			$iId = reset($_VARS['parent_gui_id']);
			$oAccommodation = Ext_Thebing_Accommodation::getInstance((int)$iId);
			$aItems = $oAccommodation->getRoomList(true);
		} elseif($oWDBasic->item === 'holiday') {
			$oClient = Ext_Thebing_Client::getInstance($user_data['client']);
			$aItems = $oClient->getSchools(true);
		} else {
			$oSchool = Ext_Thebing_School::getSchoolFromSession();
			$aItems = $oSchool->getTeacherList(true);
		}

		return $aItems;
	}

}