<?
class Ext_Thebing_Gui2_Format_Group extends Ext_Gui2_View_Format_Abstract {

	/**
	 * @todo In Formatklassen dürfen im Normalfall keine Queries abgefeuert werden!
	 * @param type $mValue
	 * @param type $oColumn
	 * @param type $aResultData
	 * @return type 
	 */	
	public function format($mValue, &$oColumn = null, &$aResultData = null){

		$oSchool					= Ext_Thebing_School::getSchoolFromSession();
		$aGroups					= $oSchool->getAllGroups(true);

		return $aGroups[$mValue];
	}

}
