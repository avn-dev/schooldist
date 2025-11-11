<?
class Ext_Thebing_Gui2_Format_Group_Coursestart extends Ext_Gui2_View_Format_Abstract {

	public static $iLastGroupId = 0;
	public static $iLastGroupCourseStart = 0;

	// frühester Kursbeginn
	public function format($mValue, &$oColumn = null, &$aResultData = null){

		if($aResultData['id'] != self::$iLastGroupId){

			$oSchool		= Ext_Thebing_School::getSchoolFromSession();
			$oGroup			= Ext_Thebing_Inquiry_Group::getInstance($aResultData['id']);
			$aCourses		= $oGroup->getCourses();
			
			$aCourseStarts = array();
			foreach((array) $aCourses as $aCourse){				
				$aCourseStarts[] = $aCourse['from'];
			}
			sort($aCourseStarts);

			$iFirst = (int)reset($aCourseStarts);
			if($iFirst > 0){
				$sFromDate = Ext_Thebing_Format::LocalDate($iFirst, $oSchool->id);
			}else{
				$sFromDate = '';
			}

			// Erebnis in Cash speichern
			self::$iLastGroupId = $aResultData['id'];
			self::$iLastGroupCourseStart = $sFromDate;
		}

		return (string)self::$iLastGroupCourseStart;

	}

}
