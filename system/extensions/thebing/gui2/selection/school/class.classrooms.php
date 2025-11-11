<?php

class Ext_Thebing_Gui2_Selection_School_Classrooms extends Ext_Gui2_View_Selection_Abstract{

    public function getOptions($aSelectedIds, $aSaveField, &$oWDBasic) {

		$aSchools = Ext_Thebing_Client::getSchoolList(false, 0, true);

		$aResult = [];
		foreach($aSchools as $oSchool) {

			if($oSchool->id == $oWDBasic->id) {
				continue;
			}

			$aClassrooms = $oSchool->getClassRooms(true, null, false);
			foreach($aClassrooms as $iId => $sName) {
				$aResult[$iId] = $oSchool->short.' - '.$sName;
			}

		}

		return $aResult;

	}

}
