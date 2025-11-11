<?php 


class Ext_Thebing_Tuition_ProgressReport
{
	protected $_oInquiry;
	protected $_dFilterFrom;
	protected $_dFilterUntil;
	protected $_sTranslationPart;

	public function __construct($iInquiryId, $dFrom, $dUntil)
	{
		$this->_oInquiry		= Ext_TS_Inquiry::getInstance($iInquiryId);
		$this->_dFilterFrom		= $dFrom;
		$this->_dFilterUntil	= $dUntil;
	}

	public function setTranslationPart($sTranslationPart)
	{
		$this->_sTranslationPart = $sTranslationPart;
	}

	protected function _addRows(&$oTr,$sTitle='',$sData='')
	{
		$sData = (string)$sData;
		
		if(strlen($sData)<=0)
		{
			$sData = '&nbsp;';
		}
		
		$oTh	= new Ext_Gui2_Html_Table_Tr_Th();
		if(empty($sTitle))
		{
			$oTh->setElement('&nbsp;');
		}
		else
		{
			$oTh->setElement($this->t($sTitle).':');
		}
		
		$oTd	= new Ext_Gui2_Html_Table_Tr_Td();
		$oTd->setElement($sData);
		$oTr->setElement($oTh);
		$oTr->setElement($oTd);
	}

	protected function _addAttendanceInfo(&$oTr, Ext_TS_Inquiry_Journey_Course $oInquiryCourse, \TsTuition\Entity\Course\Program\Service $oProgramService)
	{
		$fAttendance = Ext_Thebing_Tuition_Attendance::getAttendanceForInquiryCourseProgramService($oInquiryCourse, $oProgramService);
		
		$oFormat	 = new Ext_Thebing_Gui2_Format_Tuition_Attendance_Percent();
		
		$sAttendance = $oFormat->format($fAttendance);

		$this->_addRows($oTr, 'Anwesenheit', $sAttendance);
	}

	protected function _addWeeksAtSchoolInfo(&$oTr, $iWeeks)
	{
		$this->_addRows($oTr,'Wochen',$iWeeks);
	}

	protected function _addStartLevelInfo(&$oTr, Ext_TS_Inquiry_Journey_Course $oInquiryCourse, \TsTuition\Entity\Course\Program\Service $oProgramService)
	{
		$sStartLevel = Ext_Thebing_Tuition_Progress::getStartLevel($oInquiryCourse->getId(), $oProgramService->getId());

		$this->_addRows($oTr,'Startlevel',$sStartLevel);
	}

	protected function _addCurrentLevelInfo(&$oTr, Ext_TS_Inquiry_Journey_Course $oInquiryCourse, \TsTuition\Entity\Course\Program\Service $oProgramService)
	{
		$sCurrentLevel = Ext_Thebing_Tuition_Progress::getCurrentLevel($oInquiryCourse->getId(), $oProgramService->getId());

		$this->_addRows($oTr,'Aktuelles Level',$sCurrentLevel);
	}

	protected function _addWeeksAtCurrentLevelInfo(&$oTr, Ext_TS_Inquiry_Journey_Course $oInquiryCourse, \TsTuition\Entity\Course\Program\Service $oProgramService)
	{
		$iCountLevel = Ext_Thebing_Tuition_Progress::getCurrentLevelCount($oInquiryCourse, $oProgramService);
		
		$this->_addRows($oTr,'Wochen akt. Level',$iCountLevel);
	}

	protected function _addNextExaminationInfo(&$oTr, Ext_TS_Inquiry_Journey_Course $oInquiryCourse, \TsTuition\Entity\Course\Program\Service $oProgramService) {
		$dNextExamination = Ext_Thebing_Examination::getNextExaminationDate($oInquiryCourse, $oProgramService);
		$oDateFormat = new Ext_Thebing_Gui2_Format_Date();
		$dNextExamination = $oDateFormat->formatByValue($dNextExamination);

		$this->_addRows($oTr,'nächstes Examen',$dNextExamination);
	}

	protected function _addTeacherCountInfo(&$oTr, Ext_TS_Inquiry_Journey_Course $oInquiryCourse, \TsTuition\Entity\Course\Program\Service $oProgramService, $bSubstitute=false)
	{
		if(!$bSubstitute)
		{
			$sTitle = 'Lehreranzahl';
		}
		else
		{
			$sTitle = 'Ersatzlehrer';
		}

		$oTuitionAllocation = new Ext_Thebing_School_Tuition_Allocation();
		$iTeacherCount		= $oTuitionAllocation->getTeachersForInquiryCourse($oInquiryCourse, $oProgramService, true,$bSubstitute);

		$this->_addRows($oTr,$sTitle,$iTeacherCount);
	}

	protected function _addMatchingInfo(&$oTr, Ext_TS_Inquiry_Journey_Course $oInquiryCourse, \TsTuition\Entity\Course\Program\Service $oProgramService, $sCustomerField)
	{
		if($sCustomerField == 'nationality')
		{
			$sTitle			= 'Nationalität';
		}
		else
		{
			$sTitle			= 'Sprachen';
		}

		$oTuitionAllocation = new Ext_Thebing_School_Tuition_Allocation();
		$fPercent			= $oTuitionAllocation->getMatchingPercent($oInquiryCourse, $oProgramService, $sCustomerField);
		$fPercent			= Ext_Thebing_Format::Number($fPercent).'%';

		$this->_addRows($oTr,$sTitle,$fPercent);
	}

	protected function _getCourseInfosHtml()
	{
		$sHtmlCourseInfos = '';

		$oFieldset	= new Ext_Gui2_Html_Fieldset();
		$oLegend	= new Ext_Gui2_Html_Fieldset_Legend();

		$oLegend->setElement($this->t('Kurse'));

		$aInquiriesCourses = (array)$this->_oInquiry->getCourses();
		foreach($aInquiriesCourses as $aInquiriesCourse)
		{
			$oInquiryCourse				= new Ext_TS_Inquiry_Journey_Course();
			$oInquiryCourse->units		= $aInquiriesCourse['units'];
			$oInquiryCourse->weeks		= $aInquiriesCourse['weeks'];
			$oInquiryCourse->from		= $aInquiriesCourse['from'];
			$oInquiryCourse->until		= $aInquiriesCourse['until'];
			$oInquiryCourse->course_id	= $aInquiriesCourse['course_id'];

			$oDiv = $this->_getDiv();
			$oDiv->setElement((string)$oInquiryCourse->getInfo());
			$oFieldset->setElement($oDiv);
		}

		$oFieldset->setElement($oLegend);
		$sHtmlCourseInfos .= $oFieldset->generateHTML();

		return $sHtmlCourseInfos;
	}

	protected function _getDiv($sClassName='',$sId=false)
	{
		$oDiv = new Ext_Gui2_Html_Div();
		$oDiv->class = (string)$sClassName;
		if($sId)
		{
			$oDiv->id = (string)$sId;
		}

		return $oDiv;
	}

	protected function _addInnerDiv(&$oSecondDiv, $mElement, $mAdditionalOptions=array(), $sAdditionalInnerClass='')
	{
		$sInnerClass = 'inner';

		if(!empty($sAdditionalInnerClass))
		{
			$sInnerClass .= ' '.$sAdditionalInnerClass;
		}

		$oDiv			= $this->_getDiv($sInnerClass);
		
		$mAdditionalOptions = (array)$mAdditionalOptions;
		foreach($mAdditionalOptions as $sSetter => $mValue)
		{
			$oDiv->$sSetter = $mValue;
		}
		
		if(is_string($mElement))
		{
			$oLabel	= new Ext_Gui2_Html_Label();
			$oLabel->setElement($mElement);
			$oAdd	= $oLabel;
		}
		elseif(is_array($mElement))
		{
			$oAdd = null;
			foreach($mElement as $oTemp)
			{
				$oDiv->setElement($oTemp);
			}
		}
		else
		{
			$oAdd	= $mElement;
		}

		if(!empty($oAdd))
		{
			$oDiv->setElement($oAdd);
		}

		$oSecondDiv->setElement($oDiv);

		return $oDiv;
	}

	protected function structurizeData($data) {
		
		$aAllocationData = [];
		foreach($data as $aRowData) {

			$sWeek = $aRowData['block_week'];
			$sClassName	= $aRowData['class_name'];
			$sCourseName = $aRowData['course_name'];

			$sCombine = $aRowData['class_id'].'#'.$aRowData['course_id'];

			$aAllocationData[$sWeek]['courses'][$sCombine]['class_name'] = $sClassName;
			$aAllocationData[$sWeek]['courses'][$sCombine]['course_name'] = $sCourseName;
			$aAllocationData[$sWeek]['courses'][$sCombine]['class_level'] = $aRowData['class_level'];
			$aAllocationData[$sWeek]['courses'][$sCombine]['class_weeks'] = $aRowData['class_weeks'];
			$aAllocationData[$sWeek]['courses'][$sCombine]['class_start_week'] = $aRowData['start_week'];
			$aAllocationData[$sWeek]['courses'][$sCombine]['block_level'] = $aRowData['block_level'];
			$aAllocationData[$sWeek]['courses'][$sCombine]['progress_level'] = $aRowData['progress_level'];
			$aAllocationData[$sWeek]['courses'][$sCombine]['days'][$aRowData['day']] = 1;
			$aAllocationData[$sWeek]['courses'][$sCombine]['course_lessons'] = $aRowData['course_lessons'];

			if($aRowData['per_unit'] == 0 || $aRowData['units'] == 0) {

				if(!isset($aAllocationData[$sWeek]['courses'][$sCombine]['allocated_lessons'])) {
					$aAllocationData[$sWeek]['courses'][$sCombine]['allocated_lessons'] = 0;
				}

				if(!isset($aAllocationData[$sWeek]['remaining_lessons'][$sCourseName])) {
					$aAllocationData[$sWeek]['remaining_lessons'][$sCourseName]	= $aRowData['course_lessons'];
				}

				$aAllocationData[$sWeek]['courses'][$sCombine]['allocated_lessons'] += $aRowData['allocated_lessons'];
				$aAllocationData[$sWeek]['remaining_lessons'][$sCourseName] -= $aRowData['allocated_lessons'];

			} else {
				$aAllocationData[$sWeek]['courses'][$sCombine]['allocated_lessons']	= $aRowData['allocated_units'];
				$aAllocationData[$sWeek]['remaining_lessons'][$sCourseName]	= $aRowData['course_lessons'] - $aRowData['allocated_units'];
			}

			$aAllocationData[$sWeek]['inquiry_course_from']	= $aRowData['inquiry_course_from'];
			$aAllocationData[$sWeek]['inquiry_course_weeks'] = $aRowData['inquiry_course_weeks'];

			$iBlockId = (int)$aRowData['block_id'];
			$iDay = (int)$aRowData['day'];
			$sCombine2	= $iBlockId.'_'.$iDay;

			$aAllocationData[$sWeek]['courses'][$sCombine]['blocks'][$sCombine2] = array(
				'day' => $iDay,
				'from' => $aRowData['block_from'],
				'until' => $aRowData['block_until'],
				'lessons' => $aRowData['allocated_lessons'],
				'room_name' => $aRowData['classroom'],
				'room_id' => $aRowData['classroom_id'],
				'teacher_firstname' => $aRowData['teacher_firstname'],
				'teacher_lastname' => $aRowData['teacher_lastname'],
				'teacher_name' => $aRowData['teacher_name'],
				'description' => $aRowData['block_description'],
				'lessons_attended' => $aRowData['lessons_attended'],
				'lessons_percent' => $aRowData['lessons_percent'],
				'lessons_duration' => $aRowData['lessons_duration'],
				'lessons_duration_absent' => $aRowData['lessons_duration_absent'],
				'lessons_duration_percent' => $aRowData['lessons_duration_percent'],
				'automatic' => $aRowData['automatic'],
			);

		}
		
		return $aAllocationData;
	}


	/**
	 * @return string
	 * @throws Exception
	 */
	protected function _getAllocationHtml() {

		$sHtml = '';

		// AttendanceReport arbeitet mit mehreren Buchungen, hier gibt es aber nur eine.
		// Durch diese Lösung braucht man keine extra Umstellung.
		$dummyInquiryArray = [$this->_oInquiry];

		$oService = new \TsTuition\Generator\AttendanceReport($dummyInquiryArray, Closure::fromCallable([$this, 't']));
		$oService->reverseWeekSorting(true);
		list($aAllocationData, $sums) = $oService->generateData();

		// AttendanceReport arbeitet mit mehreren Buchungen, hier gibt es aber nur eine.
		// Durch diese Lösung braucht man keine extra Umstellung.
		$aAllocationData = reset($aAllocationData);
		$sums = reset($sums);

		$aAllocation = $this->structurizeData($aAllocationData);

		$school = $this->_oInquiry->getSchool();
		
		$aDays = Ext_Thebing_Util::getDays('%a', null, $school->course_startday);
		
		$oWdDate = new WDDate();

		$oH3 = new Ext_Gui2_Html_H4();
		$oH3->setElement($this->t('Zuweisungen'));
		$sHtml .= $oH3->generateHTML();

		if(empty($aAllocation)) {

			$oMessage = new Ext_Gui2_Html_Div;
			$oMessage->style = 'padding: 10px 0 8px 5px;';
			$oMessage->setElement($this->t('Der Kunde hat keine aktive Klassenzuweisung.'));
			$sHtml .= $oMessage->generateHTML();

		} else {

			$levels = $school->getLevelList(true, null, 'internal', false);

			foreach($aAllocation as $dDate => $aAllocationWeekData) {

				$oDivMain = $this->_getDiv('allocation');

				$oWdDate->set($dDate, WDDate::DB_DATE);
				$iWeekNum = $oWdDate->get(WDDate::WEEK);
				$aWeek = Ext_Thebing_Util::getWeekTimestamps($oWdDate->get(WDDate::TIMESTAMP));
				$sWeekStart = $this->_applyFormat($aWeek['start'], 'Ext_Thebing_Gui2_Format_Date');
				$sWeekEnd = $this->_applyFormat($aWeek['end'], 'Ext_Thebing_Gui2_Format_Date');

				$oWdDate->set($aAllocationWeekData['inquiry_course_from'], WDDate::DB_DATE);
				$oWdDate->set(1, WDDate::WEEKDAY);
				$iInquiryCourseFrom = $oWdDate->get(WDDate::TIMESTAMP);

				$sText = $this->t('Woche').' '.$iWeekNum.' ('.$sWeekStart.' - '.$sWeekEnd.')';

				$oFieldset = new Ext_Gui2_Html_Fieldset();
				$oLegend = new Ext_Gui2_Html_Fieldset_Legend();
				$oLegend->setElement($sText);
				$oFieldset->setElement($oLegend);

				foreach($aAllocationWeekData['courses'] as $aCourseBlockData) {

					$sCourseName = $aCourseBlockData['course_name'];
					$sClassName = $aCourseBlockData['class_name'];

					$oDivSecond = $this->_getDiv('second');

					$oDivHighlight = $this->_getDiv('ui-state-highlight');
					$oDivToggleIcon = $this->_getDiv('ui-icon ui-icon-carat-2-n-s');
					$oDivToggleIcon->title = $this->t('Einblenden');

					$oDivHighlight->setElement($oDivToggleIcon);
					$this->_addInnerDiv($oDivSecond, $oDivHighlight, array(),'inner-toggle');

					$oWdDate->set($dDate, WDDate::DB_DATE);
					$iWeekFrom = (int)$oWdDate->getDiff(WDDate::WEEK,$aCourseBlockData['class_start_week'],WDDate::DB_DATE) + 1;
					$iWeekFromInquiry = (int)$oWdDate->getDiff(WDDate::WEEK,$iInquiryCourseFrom,WDDate::TIMESTAMP) + 1;

					$sText = $sClassName;
					$sText .= ' ('.$iWeekFrom.'/'.$aCourseBlockData['class_weeks'];

					if(!empty($aCourseBlockData['block_level'])) {
						$sText .= ' | '.$aCourseBlockData['block_level'].'';
					}

					$sText .= ')';
					$oLabel = new Ext_Gui2_Html_Label();
					$oLabel->setElement($sText);
					$this->_addInnerDiv($oDivSecond, $oLabel);

					$sText = $sCourseName;
					$sText .= ' ('.$iWeekFromInquiry.'/'.$aAllocationWeekData['inquiry_course_weeks'].')';

					$sText .= '<br />'.$this->t('GL').': '.Ext_Thebing_Format::Number($aCourseBlockData['course_lessons'], null, null, false);
					$sText .= ', '.$this->t('VL').': '.Ext_Thebing_Format::Number($aAllocationWeekData['remaining_lessons'][$sCourseName], null, null, false);
					$sText .= ', '.$this->t('ZL').': '.Ext_Thebing_Format::Number($aCourseBlockData['allocated_lessons'], null, null, false);
					$this->_addInnerDiv($oDivSecond, $sText, array(), true);


					$sText = $this->t('Tage').': ';
					$sTextAddon = implode(',',  array_keys($aCourseBlockData['days']));
					$sText .= (string)$this->_applyFormat($sTextAddon, 'Ext_Thebing_Gui2_Format_Multiselect', array($aDays,', '));
					if(!empty($aCourseBlockData['progress_level'])) {
						$sText .= '<br>'.$this->t('Niveau').': '.$levels[$aCourseBlockData['progress_level']];
					}
					$this->_addInnerDiv($oDivSecond, $sText);

					$oDivCleaner = $this->_getDiv('clearfix');
					$oDivSecond->setElement($oDivCleaner);

					$oDivMain->setElement($oDivSecond);

					$oDivHide = $this->_getDiv('toggle');
					$oDivHide->style = 'display: none;';
					$aBlocks = (array)$aCourseBlockData['blocks'];

					foreach($aBlocks as $sKey => $aHiddenData) {

						$oLabelDay = new Ext_Gui2_Html_Label();
						$oLabelDay->class = 'day';
						$oLabelDay->setElement((string)$this->_applyFormat($aHiddenData['day'], 'Ext_Thebing_Gui2_Format_Select', array($aDays)));

						$oTmpDiv = $this->_addInnerDiv($oDivHide, $this->_getDiv('ui-icon ui-icon-carat-2-n-s'), [], 'inner-toggle inner-toggle2');
						if(empty($aHiddenData['description'])) {
							$oTmpDiv->style = 'visibility: hidden;';
						}

						$sText = $aHiddenData['from']->format('H:i').' - '.$aHiddenData['until']->format('H:i');
						$sText .= ' ('.$this->t('ZL').': '.Ext_Thebing_Format::Number($aHiddenData['lessons'], null, null, false).'';

						if($aHiddenData['lessons_duration_percent'] !== null) {
							$sText .= '; <span title="'.\Util::convertHtmlEntities($this->t('Anwesenheit')).'">'.Ext_Thebing_Format::Number($aHiddenData['lessons_duration_percent']).'&thinsp;%</span>';
						}
						
						$sText .= ')';
						
						$oLabelTemplate = new Ext_Gui2_Html_Label();
						$oLabelTemplate->setElement($sText);
						$oTmpDiv = $this->_addInnerDiv($oDivHide, array($oLabelDay,$oLabelTemplate),array('class' => 'firstInner'));
						$oTmpDiv->__set('data-block-id', $sKey);

						if($aHiddenData['room_id'] == 0) {
							$sRoomName = $this->t('Raumlos');
						} elseif($aHiddenData['room_id'] == -1) {
							$sRoomName = $this->t('Virtuell');
						} else {
							$sRoomName = $aHiddenData['room_name'];
						}
		
						$this->_addInnerDiv($oDivHide,(string)$sRoomName);
						$this->_addInnerDiv($oDivHide,$aHiddenData['teacher_name']);
						$this->_addInnerDiv($oDivHide,($aHiddenData['automatic']?$this->t('Automatisch zugewiesen'):''));

						$oDivCleaner = $this->_getDiv('clearfix');
						$oDivHide->setElement($oDivCleaner);

						if(!empty($aHiddenData['description'])) {
							$oFormat = new Ext_Thebing_Gui2_Format_School_Tuition_Block_Description(true);
							$sDescription = $oFormat->format($aHiddenData['description']);
							$oTmpDiv = $this->_addInnerDiv($oDivHide, '<em>'.$this->t('Inhalt').'</em>: '.$sDescription, [], 'inner2');
							$oTmpDiv->style = 'display: none;';
							$oDivHide->setElement($this->_getDiv('clearfix'));
						}
	
					}
					
					$oDivMain->setElement($oDivHide);

				}

				$oFieldset->setElement($oDivMain);
				$sHtml .= $oFieldset->generateHTML();

			}
			
		}

		return $sHtml;
	}

	protected function _applyFormat($mValue,$sFormatClass,$aParams=array(),$aResult=array())
	{
		$oReflection	= new ReflectionClass($sFormatClass);
		$oFormat		= $oReflection->newInstanceArgs($aParams);

		$oDummy			= null;
		return $oFormat->format($mValue,$oDummy,$aResult);
	}

	protected function _getGeneralInfosHtml() {
		$sHtmlGeneral = '';

		$aInquiriesCourses = $this->_oInquiry->getCourses();

		foreach($aInquiriesCourses as $oInquiryCourse) {

			$aProgramServices = $oInquiryCourse->getProgram()->getServices(\TsTuition\Entity\Course\Program\Service::TYPE_COURSE);

			foreach($aProgramServices as $oProgramService) {

				if($oProgramService->getService()->isEmployment()) {
					continue;
				}

				$sTitle = $oInquiryCourse->getInfo(false, false, $oProgramService);

				$oFieldset = new Ext_Gui2_Html_Fieldset();
				$oLegend = new Ext_Gui2_Html_Fieldset_Legend();

				$oLegend->setElement($sTitle);
				$oFieldset->setElement($oLegend);

				$oTable = new Ext_Gui2_Html_Table();
				$oTable->class = 'data';
				$oTable->cellpadding = 0;
				$oTable->cellspacing = 0;

				$oTr = new Ext_Gui2_Html_Table_tr();
				$this->_addAttendanceInfo($oTr, $oInquiryCourse, $oProgramService);
				$this->_addStartLevelInfo($oTr, $oInquiryCourse, $oProgramService);
				$this->_addNextExaminationInfo($oTr, $oInquiryCourse, $oProgramService);
				$this->_addMatchingInfo($oTr, $oInquiryCourse, $oProgramService, 'nationality');
				$oTable->setElement($oTr);

				$iWeeks = $oInquiryCourse->weeks;
				if($oProgramService->hasDates()) {
					$iWeeks = $oProgramService->getWeeks();
				}

				$oTr = new Ext_Gui2_Html_Table_tr();
				$this->_addWeeksAtSchoolInfo($oTr, $iWeeks);
				$this->_addCurrentLevelInfo($oTr,$oInquiryCourse, $oProgramService);
				$this->_addTeacherCountInfo($oTr, $oInquiryCourse, $oProgramService);
				$this->_addMatchingInfo($oTr, $oInquiryCourse, $oProgramService, 'language');
				$oTable->setElement($oTr);

				$oTr = new Ext_Gui2_Html_Table_tr();
				$this->_addRows($oTr);
				$this->_addWeeksAtCurrentLevelInfo($oTr, $oInquiryCourse, $oProgramService);
				$this->_addTeacherCountInfo($oTr, $oInquiryCourse, $oProgramService, true);
				$this->_addRows($oTr);
				$oTable->setElement($oTr);

				$oFieldset->setElement($oTable);

				$sHtmlGeneral .= $oFieldset->generateHTML();

			}

		}

		return $sHtmlGeneral;
	}

	public function getDialogHtml()
	{
		$sHtml = '';

		//Kurse
		#$sHtmlCourse = $this->_getCourseInfosHtml();
		#$sHtml .= $sHtmlCourse;

		// Drucken Icon	
		$oIconContainer = new Ext_Gui2_Html_Button();
		$oIconContainer->class = 'btn btn-default sr_progressreport_print';
		$oIconContainer->id = 'sr_progressreport_print';

		$oIcon = new Ext_Gui2_Html_I();
		$oIcon->title = $oIcon->label;
		$oIcon->class = 'fa '.Ext_Thebing_Util::getIcon('print');

		$oIconContainer->setElement($oIcon);
		$oIconContainer->setElement(L10N::t('Drucken'));

		$sHtml .= $oIconContainer->generateHTML();
		
		//Allgemeine Infos
		$sHtml .= $this->_getGeneralInfosHtml();

		//Zuweisungen
		$sHtml .= $this->_getAllocationHtml();

		$aHookData = ['html' => &$sHtml, 'inquiry' => $this->_oInquiry];
		\System::wd()->executeHook('ts_tuition_inquiry_progress_report_html', $aHookData);

		return $sHtml;
	}

	public function t($sTranslate)
	{
		return L10N::t($sTranslate, $this->_sTranslationPart);
	}
}
