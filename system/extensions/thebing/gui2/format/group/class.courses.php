<?
class Ext_Thebing_Gui2_Format_Group_Courses extends Ext_Gui2_View_Format_Abstract {

	public static $iLastGroupId = 0;
	public static $iLastGroupCourses = 0;

	// Liefert die Anzahl der Gruppenführer anhand der ID
	public function format($mValue, &$oColumn = null, &$aResultData = null){

		if($aResultData['id'] != self::$iLastGroupId){

            $oSchool        = Ext_Thebing_School::getSchoolFromSession();
			$oGroup			= Ext_Thebing_Inquiry_Group::getInstance($aResultData['id']);
			$aCourses		= $oGroup->getCourses();
			$aLevels 		= $oSchool->getCourseLevelList();
			$aCoursesList	= $oSchool->getCourseList();
			
			$aCourseNames = array();
			foreach((array) $aCourses as $aCourse){
				/* Zuviel Tabelleninhalt unübesichtlich mit "vollem namen"
				$sName = Ext_Thebing_Inquiry_Course::getCourseNameForEditData(
															$aCoursesList[$aCourse['course_id']],
															$aLevels[$aCourse['level_id']],
															$aCourse['from'],
															$aCourse['until'],
															$aCourse['weeks'],
															$_SESSION['sid']
														);

				$aCourseNames[] = $sName;
				 */
				
				$aCourseNames[] = $aCoursesList[$aCourse['course_id']];

			}

			// Erebnis in Cash speichern
			self::$iLastGroupId = $aResultData['id'];
			self::$iLastGroupCourses = implode('<br/>', $aCourseNames);
		}

		return (string)self::$iLastGroupCourses;

	}

}
