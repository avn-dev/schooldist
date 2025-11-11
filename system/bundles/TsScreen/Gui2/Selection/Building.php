<?php

namespace TsScreen\Gui2\Selection;

class Building extends \Ext_Gui2_View_Selection_Abstract {
	
	public function getOptions($aSelectedIds, $aSaveField, &$oWDBasic) {
		
		$aReturn = [];
		
		$school = \Ext_Thebing_School::getInstance($oWDBasic->school_id);
		
		$dummyBuilding = \Ext_Thebing_Tuition_Buildings::getInstance();
		$dummyBuilding->school_id = $school->id;
		$buildings = $dummyBuilding->getArrayListSchool(true, 'title');
		
		#$buildings = \Ext_Thebing_Util::addEmptyItem($buildings);		
		
		return $buildings;
	}
	
}
