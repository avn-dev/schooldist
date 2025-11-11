<?php

namespace TsScreen\Gui2\Selection;

class School extends \Ext_Gui2_View_Selection_Abstract {
	
	public function getOptions($aSelectedIds, $aSaveField, &$oWDBasic) {
		
		$aSchools = \Ext_Thebing_Client::getSchoolList(true);
		
		$aSchools = \Ext_Thebing_Util::addEmptyItem($aSchools);
		
		return $aSchools;
	}
	
}
