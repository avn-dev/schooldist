<?php

use \Core\Helper\DateTime;
use TsTuition\Entity\Course\Program\Service;
use Ts\Entity\Inquiry\Journey\Course\LessonsContingent;

class Ext_TS_Inquiry_TuitionIndex {

	/** @var int Neuer Schüler */
	const STATE_NEW = 1;

	/** @var int Schüler ohne Veränderung */
	const STATE_CONTINUOUS = 2;

	/** @var int Letze Woche */
	const STATE_LAST = 4;

	/** @var int Schüler hat Ferien */
	const STATE_VACATION = 8;

	/** @var int Schüler hatte Ferien */
	const STATE_VACATION_RETURN = 16;

	/** @var int Schüler hat Klassenwechsel */
	const STATE_CLASS_CHANGE = 32;

	/** @var int Maximale Anzahl an Wochen, alles darüber wird als nicht valide angesehen und nicht verarbeitet */
	const MAX_WEEKS_COURSE = 156;

	/** @var int Maximale Anzahl an Wochen für den kompletten Kurs-Leistungszeitraum der Buchung */
	const MAX_WEEKS_SERVICE_TIME = 520;

	/** @var Ext_TS_Inquiry */
	protected $oInquiry	= null;

	/** @var \Ext_Thebing_School */
	protected $school;

	/** @var array */
	protected $aPeriods	= array();

	/** @var \Core\Helper\DateTime */
	protected $dServiceFrom;

	/** @var \Core\Helper\DateTime */
	protected $dServiceUntil;

	/** @var Ext_TS_Inquiry_TuitionIndex_Week[] */
	protected $aInquiryWeeks = [];

	/** @var Ext_TS_Inquiry_Journey_Course_TuitionIndex_Week[] */
	protected $aJourneyCourseWeeks = [];

	/** @var Ext_TS_Inquiry_Journey_Course[] */
	protected $aInquiriesCourses = array();

	/** @var array */
	protected $aPeriodWeeks = array();

	/** @var array */
	protected $aJourneyCourseWeeksClasses = array();

	/** @var array */
	private static $aSplittingCache = array();

	/** @var int */
	private $iTotalCourseWeeks = 0;

	protected $schoolHolidayWeeks = [];

	/**
	 * @param Ext_TS_Inquiry $oInquiry
	 */
	public function __construct(Ext_TS_Inquiry $oInquiry) {
		$this->oInquiry	= $oInquiry;
		$this->school = $this->oInquiry->getSchool();
	}

	/**
	 * Aktualisiert den Wochenindex für die Unterrichtsplanung
	 */
	public function update() {

		// Muss wegen Race Conditions zwischen Benutzern (duplicate entry) immer in einer Transaktion ausgeführt werden
		if(DB::getLastTransactionPoint() === null) {
			DB::begin(__CLASS__);
		}

		$this->prepareDates();
		$this->prepareAllocationData();
		$this->prepareData();
		$this->delete();
		$this->save();

		$this->updateLessonContingents();

		if(DB::getLastTransactionPoint() === __CLASS__) {
			DB::commit(__CLASS__);
		}

	}

	/**
	 * Alte Einträge in beiden Tabellen löschen (wird alles neu berechnet)
	 */
	protected function delete() {

		DB::executePreparedQuery("
			DELETE FROM
				`ts_inquiries_tuition_index`
			WHERE
				`inquiry_id` = :inquiry_id
		", ['inquiry_id' => $this->oInquiry->id]);


		$sSql = "
			SELECT
				`ts_ijc`.`id`
			FROM
				`ts_inquiries_journeys` `ts_ij` INNER JOIN
				`ts_inquiries_journeys_courses` `ts_ijc` ON
					`ts_ijc`.`journey_id` = `ts_ij`.`id`
			 WHERE
				`ts_ij`.`inquiry_id` = :inquiry_id
		";

		// SELECT separat ausführen, da das ansonsten mit DELETE für massig Deadlocks sorgt
		$aJourneyCourseIds = DB::getQueryCol($sSql, ['inquiry_id' => $this->oInquiry->id]);

		$sSql = "
			DELETE FROM
				`ts_inquiries_journeys_courses_tuition_index`
			WHERE
				`journey_course_id` IN ( :journey_course_ids )
		";
		DB::executePreparedQuery($sSql, ['journey_course_ids' => $aJourneyCourseIds]);

	}

	/**
	 * Speichern (Konstruktor initiiert nur)
	 */
	protected function save() {

		foreach($this->aInquiryWeeks as $oWeek) {
			$oWeek->save();
		}

		foreach($this->aJourneyCourseWeeks as $oWeek) {
			$oWeek->save();
		}

	}
	
	/**
	 * Ermittelt den Startzeitpunkt einer Woche
	 *
	 * @TODO Sollte auf \Ext_Thebing_Util::getPreviousCourseStartDay() umgestellt werden
	 *
	 * @param \Core\Helper\DateTime $dDate
	 * @return \Core\Helper\DateTime
	 */
	protected function getWeekStart(\DateTime $dDate) {

		$oSchool = $this->oInquiry->getSchool();

		// Woche ist immer die mit den meisten Tagen
		if($oSchool->course_startday < 5) {
			// Starttag Montag-Donnerstag: Montag ist in dieser Woche
			$dDate->modify('monday this week');
		} else {
			// Starttag Freitag-Sonntag: Montag ist in der nächsten Woche
			$dDate->modify('next monday');
		}

		return $dDate;
	}
	
	/**
	 * Ermittelt den Endzeitpunkt einer Woche
	 *
	 * @param \Core\Helper\DateTime $dDate
	 * @return \Core\Helper\DateTime
	 */
	protected function getWeekEnd(\DateTime $dDate) {

		$dDate->modify('sunday this week');
		$dDate->setTime(23, 59, 59);

		return $dDate;
	}	
	
	/**
	 * Bereitet die Perioden auf
	 */
	protected function prepareDates() {

		$this->aInquiriesCourses = (array)$this->oInquiry->getCourses(true);
		$aInquiriesHolidays = $this->getInquiryStudentHolidays();
		$aPeriods = $this->mergePeriods($this->aInquiriesCourses, $aInquiriesHolidays);
		
		$aNewPeriods = array();

		foreach($aPeriods as $iKey => $aPeriod) {

			if(!DateTime::isDate($aPeriod['from'], 'Y-m-d')) {
				// Keine Ahnung, was man damit anfangen soll
				continue;
			}

			$dPeriodFrom = new DateTime($aPeriod['from']);

			// Uhrzeit setzen damit Enddatum inkludiert wird (DateTime exkludiert Enddatum ansonsten)
			// Beispiel 1: 5 Wochen Kurs gebucht, aber in letzter Woche wurde Starttag auf Montag gesetzt (#9668)
			// Beispiel 2: 1 Woche Ferien ab Sonntag, aber ohne Uhrzeit würde die Folgewochen mit 6 Tagen ignoriert werden (#12339)
			// TODO Wenn das Probleme macht muss das ggf. nur für Ferien gemacht werden und das untere wieder einkommentiert werden (oder #9675 für Ferien)
			$dPeriodUntil = new DateTime($aPeriod['until'].' 23:59:59');

			/*
			 * Da der Kunde lustig an Datumsangaben und Wochen rumspielen kann,
			 * und das System mal Wochen und mal die Datumsangaben nutzt,
			 * muss hier geprüft werden, ob die Anzahl der Wochen vom Wochen-Feld
			 * mit denen der Datumsangaben übereinstimmt. Wenn der Kunde bspw.
			 * das Enddatum von Freitag auf Montag (in der selben Woche) setzt,
			 * würde die letzte Woche fehlen, da DatePeriod die letzte Woche nicht
			 * mehr wahrnimmt. #9668
			 */
//			$oDiff = $dPeriodFrom->diff($dPeriodUntil);
//			if($oDiff->days < ($aPeriod['weeks'] * 7) - 6) {
//				// Erst einmal nur einen Tag, aber wer weiß, was die Kunden noch so zustande bringen
//				$dPeriodUntil->add(new DateInterval('P1D'));
//			}

			$this->setServicePeriod($dPeriodFrom, $dPeriodUntil);

			// Wenn Kursbuchung nicht Montag anfängt und Kursdauer < 1 Woche ist, dann müssen zwei Einträge angelegt werden.
			$dPeriodIteratorFrom = clone $dPeriodFrom;
			if($dPeriodIteratorFrom->format('N') > 1) {
				$dPeriodIteratorFrom->modify('monday this week');
			}

			$oPeriodIterator = new DatePeriod($dPeriodIteratorFrom, new DateInterval('P1W'), $dPeriodUntil);

			// Perioden ohne korrekten Zeitraum entfernen (aus Import o.ä.)
			if(
				$dPeriodFrom->diff($dPeriodUntil)->days / 7 > self::MAX_WEEKS_COURSE ||
				empty($aPeriod['from']) ||
				empty($aPeriod['until']) ||
				$aPeriod['from'] == '0000-00-00' ||
				$aPeriod['until'] == '0000-00-00'
			) {
				unset($aPeriods[$iKey]);
				continue;
			}

			$aNewPeriods[$dPeriodFrom->getTimestamp()][$aPeriod['id']] = $aPeriod;

			$iWeek = 1;
			foreach($oPeriodIterator as $dWeek) {
				$dMonday = $this->getWeekStart($dWeek);

				$this->aPeriodWeeks[$dMonday->format('Y-m-d')][$aPeriod['type']][$aPeriod['id']] = [
					'from' => $aPeriod['from'],
					'until' => $aPeriod['until'],
					'weeks' => $aPeriod['weeks'],
					'week' => $iWeek
				];

				// Nur bei Kurssplittung mit V markieren
				if($aPeriod['type'] === 'holiday') {
					$this->aPeriodWeeks[$dMonday->format('Y-m-d')][$aPeriod['type']][$aPeriod['id']]['course_splitting'] = $aPeriod['course_splitting'];
					$journeyCourseIds = explode(',', $aPeriod['journey_course_ids']);
					foreach($journeyCourseIds as $journeyCourseId) {
						$this->aPeriodWeeks[$dMonday->format('Y-m-d')]['course'][$journeyCourseId] = $this->aPeriodWeeks[$dMonday->format('Y-m-d')][$aPeriod['type']][$aPeriod['id']];
						$this->aPeriodWeeks[$dMonday->format('Y-m-d')]['course'][$journeyCourseId]['holiday'] = true;
					}
				}

				$iWeek++;
			}
		}

		ksort($aNewPeriods); 
		$this->aPeriods = $aNewPeriods;

	}

	/**
	 * Kompletten Kurs-Leistungszeitraum ermitteln
	 *
	 * @param \Core\Helper\DateTime $dFrom
	 * @param \Core\Helper\DateTime $dUntil
	 */
	protected function setServicePeriod(\DateTime $dFrom, \DateTime $dUntil) {

		if($this->dServiceFrom === null) {
			$this->dServiceFrom = $dFrom;
		}

		if($this->dServiceUntil === null) {
			$this->dServiceUntil = $dUntil;
		}

		$this->dServiceFrom = min($dFrom, $this->dServiceFrom);
		$this->dServiceUntil = max($dUntil, $this->dServiceUntil);

	}

	/**
	 * Ermittelt die Daten der einzelnen Wochen
	 */
	protected function prepareData() {

		if(
			$this->dServiceFrom === null ||
			$this->dServiceUntil === null
		) {
			return false;
		}

		$dFrom = $this->dServiceFrom;
		$dUntil = $this->dServiceUntil;
		$dWeekEnd = $this->getWeekEnd($dUntil);

		$oPeriodIterator = new DatePeriod($dFrom, new DateInterval('P1W'), $dWeekEnd);

		$schoolHolidays = $this->school->getSchoolHolidays($dFrom, $dWeekEnd);

		$this->schoolHolidayWeeks = $this->getWeeksFromPeriods($schoolHolidays);

		// Wochen ausrechnen
		$iInquiryTotalWeeks = ceil($dFrom->diff($dUntil)->days / 7);

		// Kurs-Leistungszeitraum über 10 Jahre ist nicht plausibel
		if($iInquiryTotalWeeks > self::MAX_WEEKS_SERVICE_TIME) {
			return false;
		}

		$bLastWeekHoliday = false;
		$iTotalCourseDuration = 0;
		$iInquiryWeek = 1;

		// Wochen der Buchung durchlaufen: $dWeek ist erstes Startdatum eines Kurses (muss nicht Montag sein)
		foreach($oPeriodIterator as $dWeek) {
			$dMonday = $this->getWeekStart($dWeek);
			$sWeekDate = $dMonday->format('Y-m-d');

			$oInquiryWeek = new Ext_TS_Inquiry_TuitionIndex_Week($this->oInquiry, clone $dMonday);
			$oInquiryWeek->setCurrentWeek($iInquiryWeek);
			$oInquiryWeek->setTotalWeeks($iInquiryTotalWeeks);
			$oInquiryWeek->setFrom($dFrom->format('Y-m-d'));
			$oInquiryWeek->setUntil($dUntil->format('Y-m-d'));

			// Ist in dieser Woche Urlaub der einen Kurs splittet?
			if(isset($this->aPeriodWeeks[$sWeekDate]['holiday'])) {
				foreach($this->aPeriodWeeks[$sWeekDate]['holiday'] as $iHolidayId => $aHoliday) {
					if($aHoliday['course_splitting']) {
						$oInquiryWeek->setStateBit(Ext_TS_Inquiry_TuitionIndex::STATE_VACATION);
					}
				}
			}

			// Status dieser Woche / Buchung
			$this->aInquiryWeeks[$sWeekDate] = $oInquiryWeek;

			$bCourseExists = $this->prepareCourseWeeks($dMonday);

			// Kursstatus setzen, falls Kurs in dieser Woche
			if($bCourseExists === true) {
				$oInquiryWeek->updateState();

				if($bLastWeekHoliday === true) {
					$oInquiryWeek->setStateBit(Ext_TS_Inquiry_TuitionIndex::STATE_VACATION_RETURN);
				}

				$iTotalCourseDuration++;

			}

			// Wenn Urlaub
			if($oInquiryWeek->checkStateBit(Ext_TS_Inquiry_TuitionIndex::STATE_VACATION)) {
				$bLastWeekHoliday = true;
			} else {
				$bLastWeekHoliday = false;
			}

			// Woche addieren
			$iInquiryWeek++;

		}

		// In alle Buchungsdaten noch die Wochen und Duration ergänzen
		foreach($this->aInquiryWeeks as $oWeek) {
			$oWeek->setTotalCourseWeeks($this->iTotalCourseWeeks); // total
			$oWeek->setTotalCourseDuration($iTotalCourseDuration); // relative
		}

		return true;

	}

	/**
	 * Daten für Kurse vorbereiten
	 *
	 * @param \Core\Helper\DateTime $dWeekStart
	 * @return bool
	 */
	protected function prepareCourseWeeks(\DateTime $dWeekStart) {

		$sWeekDate = $dWeekStart->format('Y-m-d');

		// Gibt es einen Kurs in dieser Woche?
		$bCourseExists = false;

		// Status dieser Woche / Kurse
		foreach ($this->aInquiriesCourses as $oInquiryCourse) {

			if (
				isset($this->aPeriodWeeks[$sWeekDate]['course'][$oInquiryCourse->id]) &&
				!isset($this->aPeriodWeeks[$sWeekDate]['course'][$oInquiryCourse->id]['holiday'])
			) {
				$oProgram = $oInquiryCourse->getProgram();

				// Alle Kursleistungen aus dem Programm holen
				$aCourseServices = $oProgram->getServices(\TsTuition\Entity\Course\Program\Service::TYPE_COURSE);

				// Für die totalen Kurswochen sind nur die tatsächlichen Kursbuchungen relevant
				$this->iTotalCourseWeeks++;

				foreach ($aCourseServices as $oProgramService) {

					/* @var Ext_Thebing_Tuition_Course $oCourse */
					$oCourse = $oProgramService->getService();

					// Es ist möglich das kein Kurs gültiger gesetzt ist (#9854)
					if (!$oCourse->exist()) {
						continue;
					}

					$aWeek = $this->aPeriodWeeks[$sWeekDate]['course'][$oInquiryCourse->id];
					$oCourseWeek = new Ext_TS_Inquiry_Journey_Course_TuitionIndex_Week($oInquiryCourse, clone $dWeekStart);
					$oCourseWeek->setProgramService($oProgramService);
					$oCourseWeek->setCourse($oCourse);
					$oCourseWeek->setCurrentWeek($aWeek['week']);
					$oCourseWeek->setTotalWeeks($aWeek['weeks']);
					$oCourseWeek->setTotalCourseWeeks($aWeek['weeks']);
					$oCourseWeek->setTotalCourseDuration($aWeek['weeks']);
					$oCourseWeek->setFrom($oInquiryCourse->from);
					$oCourseWeek->setUntil($oInquiryCourse->until);

					// Keine Splittung, aber auch keine Zuweisung während Schulferien
					if(
						$oCourse->schoolholiday == 2 &&
						isset($this->schoolHolidayWeeks[$sWeekDate])
					) {
						$oCourseWeek->setStateBit(Ext_TS_Inquiry_TuitionIndex::STATE_VACATION);
					} else {
						$oCourseWeek->updateState();
					}

					// Status dieser Woche / Kursbuchung + Kurs
					$this->aJourneyCourseWeeks[$oInquiryCourse->id.'_'.$oCourse->id.'_'.$sWeekDate] = $oCourseWeek;

					$bCourseExists = true;

					// Klassenwechsel erkennen
					$sCurrentWeekClassCombinationKey = null;
					$sWeekBeforeClassCombinationKey = null;

					// Array enthält nur dann Werte für eine Woche, wenn Zuweisungen auch da sind
					if(!empty($this->aJourneyCourseWeeksClasses[$oInquiryCourse->id][$sWeekDate])) {
						$sCurrentWeekClassCombinationKey = $this->aJourneyCourseWeeksClasses[$oInquiryCourse->id][$sWeekDate];
					}

					// Wochen rückwärts durchlaufen, um letzte Zuweisung zu finden
					$bSkipWeek = true;
					$aJourneyCourseWeeksClasses = array_reverse($this->aJourneyCourseWeeksClasses[$oInquiryCourse->id]);
					foreach($aJourneyCourseWeeksClasses as $sJourneyCourseWeek => $sClassCombinationKey) {

						// Neuere Wochen ignorieren
						if(!$bSkipWeek) {
							$sWeekBeforeClassCombinationKey = $sClassCombinationKey;
							break;
						}

						// Aktuelle Woche? Danach folgen die älteren
						if($sJourneyCourseWeek === $sWeekDate) {
							$bSkipWeek = false;
						}
					}

					// Auf Klassenwechsel prüfen mit den ermittelten Klassen
					if(
						!empty($sCurrentWeekClassCombinationKey) &&
						!empty($sWeekBeforeClassCombinationKey) &&
						$sCurrentWeekClassCombinationKey !== $sWeekBeforeClassCombinationKey
					) {
						// Klassenwechsel hat stattgefunden
						$oCourseWeek->setStateBit(Ext_TS_Inquiry_TuitionIndex::STATE_CLASS_CHANGE);
					}

					$fAllocatedLessonsWithoutCancelled = (float)DB::getQueryOne(Ext_Thebing_School_Tuition_Allocation::getSumOfLessonsSubSql($oInquiryCourse->id, $oCourse->id, "'".$sWeekDate."'", false));
					$fAllocatedLessonsWithCancelled = (float)DB::getQueryOne(Ext_Thebing_School_Tuition_Allocation::getSumOfLessonsSubSql($oInquiryCourse->id, $oCourse->id, "'".$sWeekDate."'", true));

					if (
						$oCourse->catch_up_on_cancelled_lessons &&
						(
							$oCourse->automatic_renewal == 0 ||
							// Bei automatischen Kursverlängerungen auf die Kündigung warten
							($oCourse->automatic_renewal == 1 && $oInquiryCourse->automatic_renewal_cancellation !== null)
						)
					) {
						// Ausgefallene Lektionen zählen NICHT als "Zugewiesen" und können nachgeholt werden
						$oCourseWeek->setAllocatedLessons($fAllocatedLessonsWithoutCancelled);
					} else {
						// Auch ausgefallene Lektionen zählen als "Zugewiesen"
						$oCourseWeek->setAllocatedLessons($fAllocatedLessonsWithCancelled);
					}

					// Ausgefallene Lektionen speichern
					$oCourseWeek->setCancelledLessons($fAllocatedLessonsWithCancelled - $fAllocatedLessonsWithoutCancelled);

				}

			}

		}

		return $bCourseExists;

	}

	/**
	 * Array aufbauen mit allen Blöcken, die den Kursbuchungen zugewiesen sind
	 */
	protected function prepareAllocationData() {

		foreach($this->aInquiriesCourses as $oJourneyCourse) {
			/** @var Ext_Thebing_School_Tuition_Allocation[] $aAllocations */
			$aAllocations = $oJourneyCourse->getJoinedObjectChilds('tuition_blocks');
			$aClassesPerWeek = array();
			$aWeekClasses = array();

			// Alle Zuweisungen durchlaufen und jede Klasse je Woche sammeln
			foreach($aAllocations as $oAllocation) {
				$oBlock = $oAllocation->getBlock();
				$aClassesPerWeek[$oBlock->week][] = $oBlock->class_id;
			}

			// Kombinations-Keys aufbauen für jede Woche, bestehend aus der Klasse
			foreach($aClassesPerWeek as $sWeek => $aClassIds) {
				sort($aClassIds);
				$aWeekClasses[$sWeek] = join('_', $aClassIds);
			}

			// Zur Sicherheit Wochen aufsteigend sortieren, da Quelle kein ORDER BY hat (wird nachher iteriert)
			uksort($aWeekClasses, function($sWeek1, $sWeek2) {
				$dWeek1 = new DateTime($sWeek1);
				$dWeek2 = new DateTime($sWeek2);

				if($dWeek1 == $dWeek2) {
					return 0;
				}

				return $dWeek1 > $dWeek2 ? 1 : -1;
			});

			$this->aJourneyCourseWeeksClasses[$oJourneyCourse->id] = $aWeekClasses;
		}

	}

	/**
	 * @param $aCourses
	 * @param $aHolidays
	 * @return array
	 */
	protected function mergePeriods($aCourses, $aHolidays) {

		$aMerge = [];
		
		foreach($aCourses as $oCourse) {
			$aCourse = $oCourse->getData();
			$aCourse['type'] = 'course';
			$aMerge[] = $aCourse;
		}

		foreach($aHolidays as $aHoliday) {
			$aHoliday['type'] = 'holiday';
			$aMerge[] = $aHoliday;
		}

		return $aMerge;
	}

	/**
	 * Alle Schülerferien der Buchung, für V-Status und Perioden(?)
	 *
	 * @return array
	 */
	protected function getInquiryStudentHolidays() {

		$sSql = "
			SELECT
				`ts_ih`.`id`,
				`ts_ih`.`weeks`,
				`ts_ih`.`from`,
				`ts_ih`.`until`,
				IF(`ts_ihs`.`journey_course_id` IS NULL, 0, 1) `course_splitting`,
				GROUP_CONCAT(CONCAT_WS(',', journey_course_id, journey_split_course_id)) `journey_course_ids`
			FROM
				`ts_inquiries_holidays` `ts_ih` LEFT JOIN
				`ts_inquiries_holidays_splitting` `ts_ihs` ON
					`ts_ihs`.`holiday_id` = `ts_ih`.`id` AND
					`ts_ihs`.`journey_course_id` IS NOT NULL AND
					`ts_ihs`.`active` = 1
			WHERE
				`ts_ih`.`inquiry_id` = :inquiry_id /* AND
				`ts_ih`.`type` = 'student' */ AND
				`ts_ih`.`active` = 1
			GROUP BY
				`ts_ih`.`id`
		";

		return (array)DB::getQueryRows($sSql, [
			'inquiry_id' => $this->oInquiry->id
		]);

	}

	/**
	 * Wert aus Woche holen
	 *
	 * Anmerkung: Die Methode achtet beim Kurs-Index NICHT auf den Kurs (relevant bei Kombinationskursen).
	 * Statische Methode, da einzelne Woche eine Woche hat, hier es aber mal wieder auch das Erstbeste gibt.
	 *
	 * @param string $sKey
	 * @param Ext_Thebing_Basic $oEntity
	 * @param \DateTimeInterface|null $dDate
	 * @return null|string
	 */
	public static function getWeekValue($sKey, Ext_Thebing_Basic $oEntity, \DateTimeInterface $dDate=null) {

		$aWeeks = (array)$oEntity->tuition_index;
		$mValue = null;

		if($sKey === 'allocated_lessons') {
			// Vorsorglich abfangen, da es Wert bei Buchung nicht gibt und bei Kursbuchung der Kurs beachtet werden müsste
			throw new \InvalidArgumentException('Not implemented');
		}

		foreach($aWeeks as $aWeek) {
			// Wenn kein Datum, dann ersten Eintrag nehmen
			if($dDate === null) {
				$mValue = $aWeek[$sKey];
				break;
			} elseif($aWeek['week'] == $dDate->format('Y-m-d')) {
				$mValue = $aWeek[$sKey];
				break;
			}
		}

		// Status formatieren
		if(
			!empty($mValue) &&
			$sKey == 'state'
		) {
			$oFormat = new Ext_Thebing_Gui2_Format_School_Tuition_State();
			$mValue = $oFormat->format($mValue);
		}

		return $mValue;

	}

	protected function updateLessonContingents()
	{
		$journeyCourses = $this->oInquiry->getCourses();
		foreach ($journeyCourses as $journeyCourse) {
			$programServices = $journeyCourse->getProgram()->getServices()->filter(fn (Service $service ) => $service->isCourse());

			foreach ($programServices as $programService) {
				$contingent = $journeyCourse->getLessonsContingent($programService);
				$contingent->refresh(LessonsContingent::USED | LessonsContingent::CANCELLED)
					->lock()->save();
			}
		}
	}
	
	protected function getWeeksFromPeriods(array $periods): array {
		$weeks = [];

		foreach ($periods as $p) {
			for ($d = (new DateTime($p->from))->modify('monday this week'); $d <= new DateTime($p->until); $d->modify('+1 week')) {
				$weeks[$d->format('Y-m-d')] = $p;
			}
		}

		return $weeks;
	}
	
}
