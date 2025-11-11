<?php

namespace TsTuition\Controller\TeacherOverview;

class PageController extends \MVC_Abstract_Controller {

	/**
	 * @var string
	 */
	protected $_sAccessRight = 'thebing_tuition_teacher_overview';

	protected $_sViewClass = '\MVC_View_Smarty';

	/**
	 * @return mixed
	 */
	public function getTeacherOverview() {

		$oClient = \Ext_Thebing_Client::getFirstClient();

		$aCourseCategories = $oClient->getCourseCategories();

		$aLevels = $oClient->getLevels();

		$aCourseLanguages = $oClient->getCourseLanguages();

		$aTeachers = $oClient->getTeachers(true);

		$oSchool = \Ext_Thebing_School::getSchoolFromSession();

		$aSchools = $oClient->getSchoolListByAccess(true);

		// "planned_teachers" ist der Default
		$plannedTeachersSelectOptions = [
			'planned_and_not_planned_teachers' => '-- '.\L10N::t('Geplante und nicht geplante Lehrer').' --',
			'planned_teachers' => \L10N::t('Nur geplante Lehrer')
		];

		$tooManyOrTooLittleLessonsSelectOptions = [
			'too_many_lessons' => \L10N::t('Zu viele Lektionen'),
			'too_little_lessons' => \L10N::t('Zu wenige Lektionen')
		];

		$aViewData['aTeachers'] = \Ext_Thebing_Util::addEmptyItem($aTeachers, '-- '.\L10N::t('Alle Lehrer').' --', 'all_teachers');
		$aViewData['aSchools'] = \Ext_Thebing_Util::addEmptyItem($aSchools, '-- '.\L10N::t('Alle Schulen').' --', 'all_schools');
		$aViewData['selectedSchool'] = $oSchool->id;
		$aViewData['aCourseCategories'] = \Ext_Thebing_Util::addEmptyItem($aCourseCategories, '-- '.\L10N::t('Alle Kurskategorien').' --', 'all_course_categorys');
		$aViewData['aLevels'] = \Ext_Thebing_Util::addEmptyItem($aLevels, '-- '.\L10N::t('Alle Niveaus').' --', 'all_levels');
		$aViewData['aCourseLanguages'] = \Ext_Thebing_Util::addEmptyItem($aCourseLanguages, '-- '.\L10N::t('Alle Kurssprachen').' --', 'all_course_languages');
		$aViewData['plannedTeachers'] = $plannedTeachersSelectOptions;
		$aViewData['tooManyOrTooLittleLessons'] = \Ext_Thebing_Util::addEmptyItem($tooManyOrTooLittleLessonsSelectOptions, '-- '.\L10N::t('Zu viele oder zu wenige Lektionen').' --', 'all_amount_lessons');

		$dWeekFrom = new \DateTime();
		$dWeekUntil = new \DateTime();

		$this->prepareDates($dWeekFrom, $dWeekUntil);
		
		$aViewData['dWeekFrom'] = $dWeekFrom;
		$aViewData['dWeekUntil'] = $dWeekUntil;
		$aViewData['sSchoolDateFormatMoment'] = \Ext_Thebing_Format::getDateFormat($oSchool->id, 'backend_moment_format_long');

		return response()->view('teacher_overview', $aViewData);
	}

	/**
	 * @param \DateTime $dWeekFrom
	 * @param \DateTime $dWeekUntil
	 */
	private function prepareDates(\DateTime &$dWeekFrom, \DateTime &$dWeekUntil) {

		$iWeekday = $dWeekFrom->format('N');

		if($iWeekday != 1) {
			$dWeekFrom->modify('last monday');
		}

		$dWeekUntil->setTimestamp($dWeekFrom->getTimestamp());

		$dWeekUntil->modify('+6 days');
		$dWeekUntil->setTime(23, 59, 59);

	}

	/**
	 * @param string $sWeek
	 *
	 * @return mixed
	 */
	public function getWeekBlocks(string $sWeek, int $schoolId = null) {

		$aReturnJsonArray = [];

		$oSmarty = new \SmartyWrapper();
		$oService = new \TsTuition\Service\Block();

		$schoolFromSession = \Ext_Thebing_School::getSchoolFromSession();

		if (empty($schoolId)) {
			// Bei allen Schulen werden alle Schulen beachtet, bei denen der Nutzer Rechte zu hat.
			$schoolIds = array_flip(\Ext_Thebing_Client::getFirstClient()->getSchoolListByAccess(true));
		} else {
			$schoolIds = [$schoolId];
		}

		$sSchoolDateFormat = $schoolFromSession->date_format_short;

		$dWeekFrom = new \DateTime($sWeek);
		$dWeekUntil = new \DateTime();

		$this->prepareDates($dWeekFrom, $dWeekUntil);

		$sWeekUntil = $dWeekUntil->format('Y-m-d');

		$aBlocksData = $oService->getWeekBlocksQueryData($sWeek, $schoolIds);

		$aTeachersTargetLessons = $oService->getTargetLessons($sWeek, $sWeekUntil, $schoolIds);
		$oSmarty->assign('aTeachersTargetLessons', $aTeachersTargetLessons);

		$aDays = \Ext_TC_Util::getDays();
		$aHolidayCategories = $schoolFromSession->getHolidays($dWeekFrom->getTimestamp(), $dWeekUntil->getTimestamp(), true, true);
		$aAbsenceCategoriesList = \Ext_Thebing_Absence_Category::getList(true);

		$aAbsenceCategories = [];

		foreach($aAbsenceCategoriesList as $aAbsenceCategory) {
			$aAbsenceCategories[$aAbsenceCategory['id']] = $aAbsenceCategory;
		}

		$aDaysDates = [];
		$aDaysData = [];
		$aHolidays = [];

		foreach($aDays as $iDay => $sDayName) {

			$dDay = \Ext_Thebing_Util::getRealDateFromTuitionWeek($dWeekFrom, $iDay, $schoolFromSession->course_startday);
			$sDayDate = $dDay->format('Y-m-d');

			$aDaysDates[$iDay] = $dDay;

			$aDaysData[(int)$iDay]['day_name'] = $sDayName;
			$aDaysData[(int)$iDay]['day_date'] = strftime($sSchoolDateFormat, $dDay->getTimestamp());

			if(isset($aHolidayCategories[$sDayDate])) {

				$aHolidays[$iDay]['color'] = $aAbsenceCategories[$aHolidayCategories[$sDayDate]]['color'];
				$aHolidays[$iDay]['name'] = $aAbsenceCategories[$aHolidayCategories[$sDayDate]]['name'];

			}

		}

		$oSmarty->assign('aDaysData', $aDaysData);
		$oSmarty->assign('aHolidays', $aHolidays);

		$aTeachersAbsence = $oService->getTeachersAbsence($aDaysDates, $schoolIds);

		$oSmarty->assign('aTeachersAbsence', $aTeachersAbsence);

		$oClient = \Ext_Thebing_Client::getFirstClient();
		$aTeachers = $oClient->getTeachers();

		foreach ($aTeachers as $oTeacher) {
			$oService->addTeacher($oTeacher);
		}

		foreach($aBlocksData as $aBlockData) {

			if(
				$schoolFromSession->tuition_show_empty_classes == 0 &&
				$aBlockData['count_students'] == 0
			) {
				continue;
			}
			
			$sTimeFrom = $aBlockData['time_from'];
			$sTimeUntil = $aBlockData['time_until'];

			$iTeacherId = $aBlockData['teacher_id'];
			$oTeacher = \Ext_Thebing_Teacher::getInstance($iTeacherId);

			$iBlockDay = (int)$aBlockData['block_day'];

			if(!empty($aBlockData['substitute_teachers'])) {

				$aSubstituteTeachers = explode('{|}', $aBlockData['substitute_teachers']);
				
				$aBlockTeacher = $aBlockData;
				
				$aBlockTeacher['time_from_object'] = \Carbon\Carbon::createFromTimeString($sTimeFrom);
				$aBlockTeacher['time_until_object'] = \Carbon\Carbon::createFromTimeString($sTimeUntil);
				
				foreach($aSubstituteTeachers as $sSubstituteTeacher) {
					
					$aBlockSubTeacher = $aBlockData;
					
					list($subTeacherId, $subLessons, $subTimeFrom, $subTimeUntil) = explode('{#}', $sSubstituteTeacher);
					
					$subTeacher = \Ext_Thebing_Teacher::getInstance($subTeacherId);
					
					$subTimeFrom = \Carbon\Carbon::createFromTimeString($subTimeFrom);
					$subTimeUntil = \Carbon\Carbon::createFromTimeString($subTimeUntil);
					
					$aBlockSubTeacher['time_from'] = $subTimeFrom->format('H:i');
					$aBlockSubTeacher['time_until'] = $subTimeUntil->format('H:i');
					$aBlockSubTeacher['lessons'] = $subLessons;
					
					if($subTimeFrom == $aBlockTeacher['time_from_object']) {
						$aBlockTeacher['time_from_object'] = $subTimeUntil;
					} elseif($subTimeUntil == $aBlockTeacher['time_until_object']) {
						$aBlockTeacher['time_until_object'] = $subTimeFrom;
					}
					
					$aBlockTeacher['lessons'] = $aBlockTeacher['lessons'] - $subLessons;
					
					$oService->addBlock($subTeacher, $iBlockDay, $aBlockSubTeacher);
					
				}
				
				if($aBlockTeacher['lessons'] > 0) {
					
					$aBlockTeacher['time_from'] = $aBlockTeacher['time_from_object']->format('H:i');
					$aBlockTeacher['time_until'] = $aBlockTeacher['time_until_object']->format('H:i');
					
					$oService->addBlock($oTeacher, $iBlockDay, $aBlockTeacher);
				}
				
			} else {

				$oService->addBlock($oTeacher, $iBlockDay, $aBlockData);

			}

		}

		$aBlocks = $oService->getBlocks();

		$oSmarty->assign('aTeachersData', $aBlocks);

		$sTemplatePath = \Util::getDocumentRoot().'system/bundles/TsTuition/Resources/views/teachers.tpl';
		$sHtml = $oSmarty->fetch($sTemplatePath);

		$aReturnJsonArray['html'] = $sHtml;

		return response()->json($aReturnJsonArray);
	}

}