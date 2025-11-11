<?php


class Ext_Thebing_School_Tuition_Block_Placeholder extends Ext_Thebing_Placeholder
{
	protected $_oBlock;
	protected $_oRoom;
	protected $_iDay;
	protected $_aCourses = array();
	protected $_iClassRoomMax;
	protected $_aStudents = array();

	public function  __construct($iBlockId = 0, $iRoomId = 0, $iDay = 0)
	{
		$this->_oBlock		= Ext_Thebing_School_Tuition_Block::getInstance($iBlockId);

		if($iRoomId > 0) {
            $this->_oRoom = Ext_Thebing_Tuition_Classroom::getInstance($iRoomId);
        }

		$this->_iDay = $iDay;

		$this->_aStudents	= $this->_oBlock->getStudents($this->_oRoom);

		$iClassroomMax	= (int)$this->_oBlock->classroom_max;

		$aCourses		= array();
		$aCourseData	= $this->_oBlock->getCourses();

		foreach($aCourseData as $aCourse) {
			
			if($iClassroomMax > 0) {
				$iClassroomMax = min((int)$iClassroomMax, (int)$aCourse['students_max']);
			} else {
				$iClassroomMax = (int)$aCourse['students_max'];
			}
			$aCourses[] = $aCourse['course'];
		}

		$this->_aCourses		= $aCourses;
		$this->_iClassRoomMax	= $iClassroomMax;
	}

	protected function _getReplaceValue($sPlaceholder, array $aPlaceholder)
	{
		switch($sPlaceholder)
		{
			case 'time':
				$oDate = new WDDate();
				$oDate->set($this->_oBlock->from, WDDate::TIMES);
				$sFrom = $oDate->get('H:I');
				$oDate->set($this->_oBlock->until, WDDate::TIMES);
				$sUntil = $oDate->get('H:I');

				$mValue		= $sFrom . ' - ' . $sUntil;
				break;
			case 'teacher':
				$sProperty = 'name';

				if(
					$aPlaceholder['modifier'] === 'firstname' ||
					$aPlaceholder['modifier'] === 'lastname'
				) {
					$sProperty = $aPlaceholder['modifier'];
				}

				$oTeacher = Ext_Thebing_Teacher::getInstance($this->_oBlock->teacher_id);
				$mValue	= $oTeacher->$sProperty;

				$aSubTeachers = $this->_oBlock->getSubstituteTeachers(0, true);

				if(!empty($aSubTeachers)) {

					foreach($aSubTeachers as $sId) {
						$oSubTeacher = Ext_Thebing_Teacher::getInstance((int)$sId);
						$mValue .= ' / '.$oSubTeacher->$sProperty;
					}

				}
				break;
			case 'teacher_by_day':
				$sProperty = 'name';

				if(
					$aPlaceholder['modifier'] === 'firstname' ||
					$aPlaceholder['modifier'] === 'lastname'
				) {
					$sProperty = $aPlaceholder['modifier'];
				}

				$oTeacher = Ext_Thebing_Teacher::getInstance($this->_oBlock->teacher_id);
				$sTeacher = $oTeacher->$sProperty;
				$mValue = $sTeacher;

				$dFrom = new DateTime($this->_oBlock->from);
				$dUntil = new DateTime($this->_oBlock->until);
				$iDifference = $dUntil->getTimestamp() - $dFrom->getTimestamp();

				$aSubTeachers	= $this->_oBlock->getSubstituteTeachers($this->_iDay, false);

				if(!empty($aSubTeachers)) {

					$aSubTeachersNames = [];

					// Differenz zwischen 'from' und 'until' in Sekunden bei allen Ersatzlehrern
					$iSubDifference = 0;

					foreach($aSubTeachers as $aSubTeacher) {

						$oSubTeacher = Ext_Thebing_Teacher::getInstance($aSubTeacher['teacher_id']);

						$dSubFrom = new DateTime($aSubTeacher['time_from']);

						$dSubUntil = new DateTime($aSubTeacher['time_until']);

						$iInterval = $dSubUntil->getTimestamp() - $dSubFrom->getTimestamp();
						$iSubDifference += $iInterval;

						$aSubTeachersNames[] = $oSubTeacher->$sProperty;

					}

					$sSubTeachers = implode(' / ', $aSubTeachersNames);

					// Wenn die Ersatzlehrer all die Stunden am Tag übernehmen, werden nur die angezeigt
					if($iDifference <= $iSubDifference) {
						$mValue = $sSubTeachers;
					} else {
						$mValue .= ' / '.$sSubTeachers;
					}

				}

				break;
			case 'courses':
				$aCourses = $this->_aCourses;

				if(
					$aPlaceholder['modifier'] === 'max' &&
					isset($aPlaceholder['parameter'])
				) {
					$aCourses = array_slice($this->_aCourses, 0, $aPlaceholder['parameter']);
				}

				$sTitle = implode(", ", $aCourses);

				if(mb_strlen($sTitle) > 40) {
					$sTitle = mb_substr($sTitle, 0, 39).'…';
				}

				$mValue = $sTitle;
				break;
			case 'students':
				$aStudents			= $this->_aStudents;
				$iCurrentAllocation = count($aStudents);
				$iClassroomMax		= $this->_iClassRoomMax;
				$mValue				= '';
				if($iCurrentAllocation <= $iClassroomMax)
				{
					$mValue .= '<span style="color: green;">';
				}
				else
				{
					$mValue .= '<span style="color: red;">';
				}
				$mValue .= $iCurrentAllocation.' / '.$iClassroomMax.'</span>';
				break;
			case 'students_list':

				$sProperty = 'name';
				$sGlue = '; ';

				if(
					$aPlaceholder['modifier'] === 'firstname' ||
					$aPlaceholder['modifier'] === 'lastname'
				) {
					$sGlue = ', ';
					$sProperty = $aPlaceholder['modifier'];
				}

				$aStudents = $this->_oBlock->getStudents($this->_oRoom);

				$aValues = [];

				foreach($aStudents as $aStudent) {
					$oContact = Ext_TS_Inquiry_Contact_Traveller::getInstance($aStudent['student_id']);
					$aValues[] = $oContact->$sProperty;
				}

				$mValue = implode($sGlue, $aValues);

				break;
			case 'level':
				$mValue = $this->_oBlock->level ?? '';
				break;
			case 'units':
				$mValue = Ext_Thebing_Format::LessonsNumber($this->_oBlock->lessons, $this->_oBlock->school_id).' '.Ext_Thebing_L10N::t('Lessons');
				break;
			case 'name':
				$mValue = $this->_oBlock->getJoinedObject('class')->name;
				break;
			case 'class_date_start':
				$format = new Ext_Thebing_Gui2_Format_Date();
				$mValue = $format->format($this->_oBlock->getJoinedObject('class')->start_week);
				break;
			case 'class_date_end':
				$class = $this->_oBlock->getJoinedObject('class');
				$format = new Ext_Thebing_Gui2_Format_Date();
				$mValue = $format->format($class->getLastDate());
				break;
			case 'blockname':
				$mValue = $this->_oBlock->name;
				break;
			case 'nationalities':
				$aNationalities = array();
				$aStudents		= (array)$this->_aStudents;
				foreach($aStudents as $aStudent) {
					$aNationalities[$aStudent['mother_tongue']]++;
				}
				ksort($aNationalities);

				$aTemp = array();
				foreach($aNationalities as $sNationality => $iNationality) {
					$aTemp[] = $iNationality.' '.strtoupper($sNationality);
				}
				$mValue = implode(", ", $aTemp);
				break;
			case 'week':
				/** @var Ext_Thebing_Tuition_Class $oClass */
				$oClass		= $this->_oBlock->getJoinedObject('class');
				$dWeek		= $this->_oBlock->week;
				$iWeeks		= $oClass->weeks;

				$mValue			= $oClass->getCurrentWeek($dWeek);
				$mValue		.= '/'.$iWeeks;
				break;
			case 'week_level':
				/** @var Ext_Thebing_Tuition_Class $oClass */
				$oClass		= $this->_oBlock->getJoinedObject('class');

				$sLatestIncreaseWeek = $this->_oBlock->getWeekRelativeToLevel();
				if(!empty($sLatestIncreaseWeek)) {
					$dLatestIncreaseWeek = new DateTime($sLatestIncreaseWeek);
					$dThisWeek = new DateTime($this->_oBlock->week);
					$aIncreaseWeeks = \Core\Helper\DateTime::getWeekPeriods($dLatestIncreaseWeek, $dThisWeek);
					$mValue = count($aIncreaseWeeks) - 1;
				} else {
					$dWeek		= $this->_oBlock->week;
					$mValue = $oClass->getCurrentWeek($dWeek);
				}
				
				break;
			case 'content':
				$mValue = $this->_oBlock->description;
				break;
			default:
				$mValue = $sPlaceholder;
				break;
		}

		return $mValue; 
	}

	public function getPlaceholders($sType='')
	{
		return array();
	}

	public function getClassRoomMax()
	{
		return $this->_iClassRoomMax;
}
}
