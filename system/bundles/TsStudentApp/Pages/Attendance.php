<?php

namespace TsStudentApp\Pages;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use TsStudentApp\AppInterface;
use TsTuition\Service\TrackingSession;

class Attendance extends AbstractPage {

	const COLOR_NEEDLE = '#808080';
	// TODO - umbenennen
	const COLOR_1 = '#af211a';
	const COLOR_2 = '#1aaf5d';

	private $appInterface;

	private $inquiry;

	private $student;

	private $school;

	private Collection $settings;

	public function __construct(AppInterface $appInterface, \Ext_TS_Inquiry $inquiry, \Ext_TS_Inquiry_Contact_Traveller $tudent, \Ext_Thebing_School $chool) {
		$this->appInterface = $appInterface;
		$this->inquiry = $inquiry;
		$this->student = $tudent;
		$this->school = $chool;
		$this->settings = collect($this->school->getMeta('student_app_attendance_settings'));
	}

	public function init(): array {
		$data = $this->refresh();
		$data['select_default'] = 'general';
		// QR-Scanner aktivieren
		$data['scan_enabled'] = true;
		$data['toast_duration'] = 3000;
		return $data;
	}

	public function refresh(): array {

		$list = [
			'items' => []
		];

		$classWeeks = \Ext_Thebing_Tuition_Class::getClassWeeksByInquiry($this->inquiry->getId());

		// @TODO Freigabe der Wochen
		$attendanceEntries = \Ext_Thebing_Tuition_Attendance::getRepository()
			->findBy(['inquiry_id' => $this->inquiry->getId()]);

		$overallAttendance = (float)\Ext_Thebing_Tuition_Attendance_Index::getAttendanceForInquiry($this->inquiry);

		$list['items'][] = [
			'key' => 'general',
			'title' => $this->appInterface->t('General Attendance'),
			'charts' => $this->buildCharts($attendanceEntries, $overallAttendance)
		];

		if (version_compare($this->appInterface->getAppVersion(), '3.0.7', '>=')) {
			$overallAttendanceExpected = (float)\Ext_Thebing_Tuition_Attendance_Index::getAttendanceForInquiry(
				$this->inquiry,
				false,
				['expected' => true]
			);

			$list['items'][] = [
				'key' => 'expected',
				'title' => $this->appInterface->t('Expected Attendance'),
				'charts' => $this->buildCharts($attendanceEntries, $overallAttendanceExpected, null, true)
			];
		}

		$classWeeks = array_reverse($classWeeks);

		foreach($classWeeks as $week) {

			$start = Carbon::parse($week);

			// Anwesenheits-Einträge dieser Woche
			$weekAttendances = array_filter($attendanceEntries, function($attendance) use($week) {
				return $attendance->week == $week;
			});

			// Klassenwochen ohne Anwesenheit überspringen
			if(empty($weekAttendances)) {
				continue;
			}

			$classBlocks = $this->buildClassBlocks($weekAttendances);

			$charts = $this->buildCharts($weekAttendances, $overallAttendance, $week);

			$title = sprintf('%s %d: %s', ucfirst($this->appInterface->t('Woche')), $start->format('W'), $this->appInterface->formatDate2($start, 'LL'));

			$list['items'][] = [
				'key' => $start->format('Y-W'),
				'title' => $title,
				'charts' => $charts,
				'class_blocks' => $classBlocks
			];
		}

		return $list;
	}

	/**
	 * Anwesenheit per Qr-Code eintragen
	 *
	 * @todo Es gibt mehrere Stellen wo die Anwesenheit gespeichert wird, vllt sollte man hier mal eine Klasse für schreiben
	 * - \Ext_Thebing_Tuition_AttendanceRepository::saveAttendance()
	 * - \TsStudentApp\Pages\Attendance::scanQrCode()
	 * - \TsTeacherLogin\Controller::saveAttendance()
	 *
	 * @param Request $request
	 * @return array
	 */
	public function scanQrCode(Request $request) {

		if($request->has('code')) {

			$session = TrackingSession::search($request->code);

			if(!empty($session)) {

				// TODO - Code immer löschen sobald das über Websockets läuft
				#TrackingSession::delete($request->code);

				if($session['block_id'] > 0) {
					$block = \Ext_Thebing_School_Tuition_Block::getInstance($session['block_id']);

					$allocation = \Ext_Thebing_School_Tuition_Allocation::getRepository()
						->findAllocationForBlock($block, $this->inquiry);

					if($allocation) {

						$attendance = \Ext_Thebing_Tuition_Attendance::getRepository()
							->getOrCreateAttendanceObject($allocation);

						$day = \Ext_TC_Util::convertWeekdayToString($session['day']);

						// Schüler als komplett anwesend eintragen (@todo richtig?)
						$attendance->$day = 0.0;

						$attendance->save();

						return [
							'success' => true,
							'message' => $this->appInterface->t('Your attendance has been confirmed.')
						];
					}
				}
			}
		}

		return [
			'success' => false,
			'error_code' => 'INVALID_TRACKING_CODE',
			'message' => $this->appInterface->t('This code seems not to be legit.')
		];

	}

	/**
	 * @param \Ext_Thebing_Tuition_Attendance[] $attendances
	 * @return array
	 */
	private function buildClassBlocks(array $attendances) {

		$blocks = [];

		foreach($attendances as $attendance) {

			$allocation = $attendance->getAllocation();
			$course = $allocation->getCourse();
			$block = $allocation->getBlock();

			$class = $block->getClass();
			$teacher = $block->getTeacher();
			$blockTemplate = $block->getTemplate();

			$buildRow = function($title, $value, $break = true) {
				if(empty($value)) return "";
				$row = '<strong>'.$title .':</strong> ' . $value;
				if($break) $row .= "<br/>";
				return $row;
			};

			$content = "";
			$content .= $buildRow($this->appInterface->t('Class'), $class->getName());
			$content .= $buildRow($this->appInterface->t('Time'),
				$this->appInterface->formatTime($blockTemplate->from). ' - '.
				$this->appInterface->formatTime($blockTemplate->until)
			);

			$content .= $buildRow($this->appInterface->t('Teacher'), $teacher->getName());

			// TODO Kann seit Anfang an nicht funktioniert haben, da $block überschrieben wurde
			/*if($block->room_id != 0) {
				$classRoom = $block->getRoom();
				$oFloor = $classRoom->getFloor();
				$oBuilding = $oFloor->getBuilding();

				$content .= $buildRow($this->appInterface->t('Building'), $oBuilding->getName());
				$content .= $buildRow($this->appInterface->t('Room'), $classRoom->getName());
			}*/

			$additionalFields = $this->getAdditionalClassBlockFields($attendance);
			$absenses = $this->getAbsencePerDay($attendance);

			foreach($additionalFields as $field) {
				$content .= $buildRow($field['name'], $field['value']);
			}

			if(!empty($absenses)) {
				$content .= "<br/>";
				$content .= "<h3>".$this->appInterface->t('Absence per day')."</h3>";

				foreach($absenses as $absense) {
					$content .= $buildRow($absense['day'], $absense['absence_formatted']);
				}

			}

			$blocks[] = [
				'title' => $course->getName(),
				'content' => $content
			];
		}

		return $blocks;
	}

	private function getAdditionalClassBlockFields(\Ext_Thebing_Tuition_Attendance $attendance) {

		$labels = [
			'score' => $this->appInterface->t('Score'),
			'comment' => $this->appInterface->t('Note'),
		];

		return $this->settings
			->filter(fn(string $field) => str_starts_with($field, 'attendance_') || str_starts_with($field, 'flex_'))
			->map(function (string $field) use ($attendance, $labels) {
				[$type, $key] = explode('_', $field, 2);

				if ($type === 'attendance') {
					$label = $labels[$key];
					$value = $attendance->$key;
				} else {
					$flexField = \Ext_TC_Flexibility::getInstance($key);
					$allocation = $attendance->getAllocation();
					$label = $flexField->getName();
					$value = $allocation->getFlexValue($flexField->id);
					$value = $flexField->formatValue($value, $this->appInterface->getLanguage());
				}

				return [
					'name' => $label,
					'value' => $value
				];
			});

	}

	/**
	 * Abwesenheit pro Tag (Tabellenzeilen) für wochenweise Übersicht der Anwesenheit
	 *
	 * @param \Ext_Thebing_Tuition_Attendance $attendance
	 * @return array
	 */
	protected function getAbsencePerDay(\Ext_Thebing_Tuition_Attendance $attendance) {
		$absenceStringDays = [];

		$allocation = $attendance->getAllocation();
		$block = $allocation->getBlock();
		$blockDays = $block->getDaysAsDateTimeObjects();
		$journeyCourse = $allocation->getJourneyCourse();
		$journeyCourseFrom = new \DateTime($journeyCourse->from);
		$journeyCourseUntil = new \DateTime($journeyCourse->until);
		$formatWeekday = new \Ext_Thebing_Gui2_Format_Day('%A');

		foreach($blockDays as $day => $dayObject) {

			if(!$dayObject->isBetween($journeyCourseFrom, $journeyCourseUntil)) {
				// Wenn der Tag des Blocks nicht mehr in den Kurszeitraum fällt, Tag ignorieren
				continue;
			}

			// Tag in Tabelle (mo, di, mi, […])
			$twoLetterDay = \Ext_TC_Util::convertWeekdayToString($day);
			$absenceValue = $attendance->$twoLetterDay;

			if(\Ext_TC_Util::compareFloat($absenceValue, 0) === 0) {
				$absenceString = $this->appInterface->t('No absence');
			} else {
				// Abwesenheit darstellen als Stunden, Minuten und in Prozent
				$lessonDurationPerDay = $allocation->lesson_duration / count($blockDays);
				$absenceValuePercent = $absenceValue / $lessonDurationPerDay * 100;
				$absenceStringPercent = $this->appInterface->formatPercent($absenceValuePercent);

				$absenceString = '';
				if($absenceValue >= 60) {
					// Stunden formatieren
					$absenceString .= sprintf('%d h ', (int)($absenceValue / 60));
				}

				// Minuten formatieren plus Prozent
				$absenceString .= sprintf('%d m (%s)', $absenceValue % 60, $absenceStringPercent);
			}

			$absenceStringDays[] = [
				'day' => $formatWeekday->format($day),
				'absence' => $absenceValue, // Benötigt für Hook
				'absence_formatted' => $absenceString
			];
		}

		return $absenceStringDays;
	}

	/**
	 * Blöcke der Anwesenheits-Zusammenfassungen rendern: Allgemein, pro Kurs und pro Lehrer
	 */
	protected function buildCharts(array $attendances, float $overallAttendance, $week = null, $expected = false): array {

		$attendancePerCourse = [];
		$attendancePerTeacher = [];

		$journeyCourses = [];
		if ($this->settings->contains('charts_course')) {
			$journeyCourses = $this->inquiry->getCourses();
		}

		// Anwesenheit pro Kurs
		foreach($journeyCourses as $journeyCourse) {
			$attendance = $journeyCourse->getAttendance($week, false, ['expected' => $expected]);

			if($attendance === null) {
				// Wenn null: Es gibt keine Zuweisung, daher diesen Kurs überspringen
				// Das hier erspart auch die Prüfung, ob der Kurs in den Zeitraum der Klassenwoche reinfällt
				continue;
			}

			$attendancePerCourse[] = $this->buildChart($journeyCourse->getCourse()->getShortName(), $attendance, 125);
		}

		// Alle Lehrer aus allen vorhandenen Anwesenheiten suchen
		$teachers = []; /** @var \Ext_Thebing_Teacher[] $aTeachers */
		if ($this->settings->contains('charts_teacher')) {
			foreach($attendances as $attendance) {
				$teacher = $attendance->getAllocation()->getBlock()->getTeacher();
				$teachers[$teacher->id] = $teacher;
			}
		}

		if (!$expected) {
			// Anwesenheit pro Lehrer
			foreach($teachers as $teacher) {
				$attendance = \Ext_Thebing_Tuition_Attendance_Index::getAttendanceForInquiryAndTeacher($this->inquiry, $teacher, ['week' => $week]);
				if($attendance === null) {
					// Bei null wurde keine Zuweisung für die Woche gefunden, daher überspringen
					continue;
				}

				$attendancePerTeacher[] = $this->buildChart($teacher->getName(), $attendance, 125);
			}
		}

		$items = [];

		if ($week === null) {
			$items['general'] = $this->buildChart("", $overallAttendance, 300);
		}

		if (!empty($attendancePerCourse)) {
			$items['per_course'] = $attendancePerCourse;
		}

		if (!empty($attendancePerTeacher)) {
			$items['per_teacher'] = $attendancePerTeacher;
		}

		return $items;
	}

	private function buildChart(string $name, float $percent, int $width) {

		// Package kommt nicht mit 0 oder 100 klar
		$criticalAttendance = (float)$this->school->critical_attendance;
		$criticalAttendance = $criticalAttendance == 100 ? 99.99 : $criticalAttendance;
		$criticalAttendance = $criticalAttendance == 0 ? 0.01 : $criticalAttendance;

		$chart = [
			'width' => $width,
			'height' => 150, // >=2.2
			'name' => $name,
			'percent' => $percent,
			'bottom_label' => $this->appInterface->formatPercent($percent),
			'delimiters' => [$criticalAttendance, 100], // >=2.2
			'delimiters_colors' => [self::COLOR_1, self::COLOR_2] // >=2.2
		];

		// Sehr spezifische Optionen für angular-gauge-chart, welches nicht mehr entwickelt wird
		if (version_compare($this->appInterface->getAppVersion(), '2.2', '<')) {
			$chart['options'] = [
				'hasNeedle' => true,
				'needleColor' => self::COLOR_NEEDLE,
				'needleUpdateSpeed' => 3000,
				'arcColors' => [
					self::COLOR_1,
					self::COLOR_2
				],
				'arcDelimiters' => [$criticalAttendance],
				'needleStartValue' => 0,
			];
		}

		return $chart;

	}

	public function getTranslations(AppInterface $appInterface): array {
		return [
			'tab.attendance.scan_button' => $appInterface->t('Scan'),
			'tab.attendance.scan' => $appInterface->t('Scan Attendance'),
			'tab.attendance.scan.error.invalid' => $appInterface->t('Please only scan codes provided by your teacher.'),
			'tab.attendance.scan.close' => $appInterface->t('Close'),
			'tab.attendance.general' => $appInterface->t('Overall attendance'),
			'tab.attendance.per_course' => $appInterface->t('Attendance per course'),
			'tab.attendance.per_teacher' => $appInterface->t('Attendance per teacher'),
			'tab.attendance.class_blocks' => $appInterface->t('Class blocks'),
		];
	}

}
