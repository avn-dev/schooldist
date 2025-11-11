<?php

namespace TsCompany\Gui2\Selection;

class CommissionCategorySchool extends \Ext_Gui2_View_Selection_Abstract {

	public function getOptions($aSelectedIds, $aSaveField, &$oWDBasic) {
		
		$category = \Ext_Thebing_Provision_Group::getInstance($oWDBasic->group_id);
		
		$schools = \Ext_Thebing_Client::getSchoolList(true);
		
		$agency = $oWDBasic->getAgency();
		
		if($agency->schools_limited) {
			$agency->schools;
			
			$schools = array_intersect_key($schools, array_flip($agency->schools));
			
		}
		
		$schools = \Ext_Thebing_Util::addEmptyItem($schools);
		
		if(!$category->old_structure) {

			if(!empty($category->school_id)) {
				if(isset($schools[$category->school_id])) {
					$schools = [
						$category->school_id => $schools[$category->school_id]
					];
				} else {
					$schools = [];
				}
			}
			
		}
		
		return $schools;		
	}

}
