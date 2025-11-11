<?php

namespace TsMobile\Generator\Pages;

use Core\Helper\DateTime;
use TsMobile\Generator\AbstractPage;
use TsMobile\Service\AbstractApp;

class Attendance extends AbstractPage {

	/** @var \SmartyWrapper */
	protected $oSmarty;

	public function __construct(AbstractApp $oApp) {
		parent::__construct($oApp);

		$this->oSmarty = new \SmartyWrapper;
		$this->oSmarty->assign('aTranslations', [
			'course' => $this->t('Course'),
			'time' => $this->t('Time'),
			'teacher' => $this->t('Teacher'),
			'building' => $this->t('Building'),
			'room' => $this->t('Room'),
			'score' => $this->t('Score'),
			'note' => $this->t('Note'),
			'absence_per_day' => $this->t('Absence per day'),
			'overall_attendance' => $this->t('Overall attendance'),
			'attendance_per_course' => $this->t('Attendance per course'),
			'attendance_per_teacher' => $this->t('Attendance per teacher'),
			'current_overall_attendance' => $this->t('Current overall attendance'),
			'overall_attendance_all_bookings' => $this->t('Attendance across all bookings'),
		]);
	}

	public function render(array $aData = array()) {
		return $this->generatePageHeading($this->oApp->t('Attendance'));
	}

	public function getStorageData() {

		$aList = [
			'select_default' => 'general',
			'items' => []
		];

		$oInquiry = $this->oApp->getInquiry();
		$aJourneyCourses = $oInquiry->getCourses();
		$aClassWeeks = \Ext_Thebing_Tuition_Class::getClassWeeksByInquiry($oInquiry->id);
		$oAttendanceRepo = \Ext_Thebing_Tuition_Attendance::getRepository();

		// @TODO Freigabe der Wochen
		$aAttendanceEntries = $oAttendanceRepo->findBy(['inquiry_id' => $oInquiry->id]);

		$aList['items'][] = [
			'key' => 'general',
			'title' => $this->t('General'),
			'html' => $this->renderOverviewBlocks($aAttendanceEntries, $aJourneyCourses)
		];

		foreach($aClassWeeks as $sWeek) {

			$dMonday = new DateTime($sWeek);

			// Anwesenheits-Einträge dieser Woche
			$aWeekAttendances = array_filter($aAttendanceEntries, function($oAttendance) use($sWeek) {
				return $oAttendance->week == $sWeek;
			});

			// Klassenwochen ohne Anwesenheit überspringen
			if(empty($aWeekAttendances)) {
				continue;
			}

			$sHtml = $this->renderOverviewBlocks($aWeekAttendances, $aJourneyCourses, $sWeek);

			$sHtml .= '<h3>'.$this->t('Class blocks').'</h3>';

			foreach($aWeekAttendances as $oAttendance) {
				$sHtml .= $this->renderClassBlock($oAttendance);
			}

			$aList['items'][] = [
				'key' => $dMonday->format('Y-W'),
				'title' => $this->formatWeekTitle($dMonday),
				'html' => $sHtml
			];
		}

		return $aList;

	}

	/**
	 * Blöcke der Anwesenheits-Zusammenfassungen rendern: Allgemein, pro Kurs und pro Lehrer
	 *
	 * @param \Ext_Thebing_Tuition_Attendance[] $aAttendances
	 * @param \Ext_TS_Inquiry_Journey_Course[] $aJourneyCourses
	 * @param string $sWeek
	 * @return string
	 */
	protected function renderOverviewBlocks(array $aAttendances, array $aJourneyCourses, $sWeek=null) {

		$oInquiry = $this->oApp->getInquiry();

		$sOverallAttendance = $sOverallAttendanceGlobal = '';
		$aAttendancePerCourse = [];
		$aAttendancePerTeacher = [];

		// Globale Anwesenheit
		if($sWeek === null) {
			$fAttendance = \Ext_Thebing_Tuition_Attendance_Index::getAttendanceForInquiry($oInquiry);
			$sAttendancePercent = \Ext_Thebing_Format::Number($fAttendance, null, $this->oApp->getSchool()->id);
			$sOverallAttendance = $sAttendancePercent.'&thinsp;%';

			// Globale Anwesenheit über alle Buchungen
			if($this->oApp->getInquiryCount() > 1) {
				$fAttendance = \Ext_Thebing_Tuition_Attendance_Index::getAttendanceForInquiryContact($this->oApp->getUser());
				$sAttendancePercent = \Ext_Thebing_Format::Number($fAttendance, null, $this->oApp->getSchool()->id);
				$sOverallAttendanceGlobal = $sAttendancePercent.'&thinsp;%';
			}
		}

		// Anwesenheit pro Kurs
		foreach($aJourneyCourses as $oJourneyCourse) {
			$fAttendance = $oJourneyCourse->getAttendance($sWeek);

			if($fAttendance === null) {
				// Wenn null: Es gibt keine Zuweisung, daher diesen Kurs überspringen
				// Das hier erspart auch die Prüfung, ob der Kurs in den Zeitraum der Klassenwoche reinfällt
				continue;
			}

			$sAttendancePercent = \Ext_Thebing_Format::Number($fAttendance, null, $this->oApp->getSchool()->id);

			$aAttendancePerCourse[] = [
				'name' => $oJourneyCourse->getCourse()->getName($this->_sInterfaceLanguage),
				'attendance' => $sAttendancePercent.'&thinsp;%'
			];
		}

		// Alle Lehrer aus allen vorhandenen Anwesenheiten suchen
		$aTeachers = []; /** @var \Ext_Thebing_School_Tuition_Teacher[] $aTeachers */
		foreach($aAttendances as $oAttendance) {
			$oTeacher = $oAttendance->getAllocation()->getBlock()->getTeacher();
			$aTeachers[$oTeacher->id] = $oTeacher;
		}

		// Anwesenheit pro Lehrer
		foreach($aTeachers as $oTeacher) {
			$fAttendance = \Ext_Thebing_Tuition_Attendance_Index::getAttendanceForInquiryAndTeacher($oInquiry, $oTeacher, ['week' => $sWeek]);
			if($fAttendance === null) {
				// Bei null wurde keine Zuweisung für die Woche gefunden, daher überspringen
				continue;
			}

			$sAttendancePercent = \Ext_Thebing_Format::Number($fAttendance, null, $this->oApp->getSchool()->id);

			$aAttendancePerTeacher[] = [
				'name' => $oTeacher->getName(),
				'attendance' => $sAttendancePercent.'&thinsp;%'
			];
		}

		$this->oSmarty->assign('sOverallAttendance', $sOverallAttendance);
		$this->oSmarty->assign('sOverallAttendanceGlobal', $sOverallAttendanceGlobal);
		$this->oSmarty->assign('aAttendancePerCourse', $aAttendancePerCourse);
		$this->oSmarty->assign('aAttendancePerTeacher', $aAttendancePerTeacher);

		return $this->oSmarty->fetch(self::getTemplatePath().'attendance/overview_blocks.tpl');

	}

	/**
	 * Zugewiesene Klassenblöcke rendern: Info über Klasse und Abwesenheit pro Tag
	 *
	 * @param \Ext_Thebing_Tuition_Attendance $oAttendance
	 * @return string
	 */
	protected function renderClassBlock(\Ext_Thebing_Tuition_Attendance $oAttendance) {

		$oFormatTime = new \Ext_Thebing_Gui2_Format_Time();

		$oAllocation = $oAttendance->getAllocation();
		$oCourse = $oAllocation->getCourse();
		$oBlock = $oAllocation->getBlock();
		$oClass = $oBlock->getClass();
		$oTeacher = $oBlock->getTeacher();
		$oBlockTemplate = $oBlock->getTemplate();

		$this->oSmarty->assign('sClassName', $oClass->getName());
		$this->oSmarty->assign('sCourse', $oCourse->getName());
		$this->oSmarty->assign('sTime', $oFormatTime->format($oBlockTemplate->from).' – '.$oFormatTime->format($oBlockTemplate->until));
		$this->oSmarty->assign('sTeacher', $oTeacher->getName());

//		$this->setAttendanceFieldsForClassBlocks($oAttendance);

		$aRoomIds = $oBlock->getRoomIds();

		// Raum und Gebäude
		if(!empty($aRoomIds)) {
			$oClassRoom = $oAllocation->getRoom();
			$oFloor = $oClassRoom->getFloor();
			$oBuilding = $oFloor->getBuilding();

			$sClassRoom = $oClassRoom->getName();
			$sBuilding = $oBuilding->getName();
		} else {
			$sClassRoom = '';
			$sBuilding = '';
		}

		$this->oSmarty->assign('sBuilding', $sBuilding);
		$this->oSmarty->assign('sClassRoom', $sClassRoom);
		$this->oSmarty->assign('aAbsenceDays', $this->getAbsencePerDay($oAttendance));

		return $this->oSmarty->fetch(self::getTemplatePath().'attendance/class_block.tpl');

	}

	/**
	 * Zusätzliche Anwesenheitsdaten für Klassenblöcke
	 *
	 * @param \Ext_Thebing_Tuition_Attendance $oAttendance
	 */
	protected function setAttendanceFieldsForClassBlocks(\Ext_Thebing_Tuition_Attendance $oAttendance) {

		$oSchoolAppConfig = $this->oApp->getSchool()->getAppSettingsConfig();

		// Closure für statische Felder
		$oSetStaticField = function($sField, $sConfigKey, $sSmartyVariable) use($oAttendance, $oSchoolAppConfig) {
			$sValue = '';
			if($oSchoolAppConfig->getValue('enabled_field', $sConfigKey, true)) {
				$sValue = $oAttendance->$sField;
			}

			$this->oSmarty->assign($sSmartyVariable, $sValue);
		};

		// Statische Felder in fester Reihenfolge
		$oSetStaticField('score', 'static_attendance_score', 'sAttendanceScore');
		$oSetStaticField('comment', 'static_attendance_note', 'sAttendanceNote');

		// Aktivierte flexible Felder
		$aFlexFields = [];
		$aEnabledFields = $oSchoolAppConfig->getValue('enabled_field');
		foreach($aEnabledFields as $aEnabledField) {
			$aAdditional = explode('_', $aEnabledField['additional'], 2);
			if($aAdditional[0] === 'flex') {
				$oFlexField = \Ext_TC_Flexibility::getInstance($aAdditional[1]);

				// Flex-Felder der Anwesenheit sind der Zuweisung zugewiesen, nicht der Anwesenheit…
				$oAllocation = $oAttendance->getAllocation();

				// Nicht über getFormattedValue, da dort jedes Mal ein Query abgefeuert wird…
				$sValue = $oAllocation->getFlexValue($oFlexField->id);
				$sValue = $oFlexField->formatValue($sValue, $this->_sInterfaceLanguage);

				if($sValue != '') {
					$aFlexFields[] = [
						'name' => $oFlexField->getName(),
						'value' => $sValue,
						'position' => $oFlexField->position
					];
				}
			}
		}

		// Nach GUI-Position sortieren
		usort($aFlexFields, function($aField1, $aField2) {
			return (int)($aField1['position'] > $aField2['position']);
		});

		$this->oSmarty->assign('aAttendanceFlexFields', $aFlexFields);

	}

	/**
	 * Abwesenheit pro Tag (Tabellenzeilen) für wochenweise Übersicht der Anwesenheit
	 *
	 * @param \Ext_Thebing_Tuition_Attendance $oAttendance
	 * @return array
	 */
	protected function getAbsencePerDay(\Ext_Thebing_Tuition_Attendance $oAttendance) {
		$aAbsenceDays = [];

		$oAllocation = $oAttendance->getAllocation();
		$oBlock = $oAllocation->getBlock();
		$aBlockDays = $oBlock->getDaysAsDateTimeObjects();
		$oJourneyCourse = $oAllocation->getJourneyCourse();
		$dJourneyCourseFrom = new DateTime($oJourneyCourse->from);
		$dJourneyCourseUntil = new DateTime($oJourneyCourse->until);
		$oFormatWeekday = new \Ext_Thebing_Gui2_Format_Day('%A');

		foreach($aBlockDays as $iDay => $dDay) {

			if(!$dDay->isBetween($dJourneyCourseFrom, $dJourneyCourseUntil)) {
				// Wenn der Tag des Blocks nicht mehr in den Kurszeitraum fällt, Tag ignorieren
				continue;
			}

			// Tag in Tabelle (mo, di, mi, […])
			$sTwoLetterDay = \Ext_TC_Util::convertWeekdayToString($iDay);
			$fAbsence = $oAttendance->$sTwoLetterDay;

			if(\Ext_TC_Util::compareFloat($fAbsence, 0) === 0) {
				$sAbsence = $this->t('No absence');
			} else {
				// Abwesenheit darstellen als Stunden, Minuten und in Prozent
				$fLessonDurationPerDay = $oAllocation->lesson_duration / count($aBlockDays);
				$fAbsencePercent = $fAbsence / $fLessonDurationPerDay * 100;
				$sAbsencePercent = \Ext_Thebing_Format::Number($fAbsencePercent, null, $this->oApp->getSchool()->id);

				$sAbsence = '';
				if($fAbsence >= 60) {
					// Stunden formatieren
					$sAbsence .= sprintf('%d&thinsp;h ', (int)($fAbsence / 60));
				}

				// Minuten formatieren plus Prozent
				$sAbsence .= sprintf('%d&thinsp;m (%s&thinsp;%%)', $fAbsence % 60, $sAbsencePercent);
			}

			$aAbsenceDays[] = [
				'name' => $oFormatWeekday->format($iDay),
				'absence' => $fAbsence, // Benötigt für Hook
				'absence_formatted' => $sAbsence
			];
		}

		return $aAbsenceDays;
	}
}
