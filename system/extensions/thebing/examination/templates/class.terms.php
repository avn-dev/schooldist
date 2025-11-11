<?php

use \Core\Helper\DateTime;

class Ext_Thebing_Examination_Templates_Terms extends Ext_Thebing_Basic {

	protected $_sTable = 'ts_examinations_templates_terms';

	protected $_aFormat = array(
		// @TODO Funktioniert als JoinedObject-Container irgendwie nicht, wird aber richtig gesetzt
		/*'template_id' => array(
			'required' => true
		),*/
//		'period_length' => array(
//			'required' => true
//		),
		'period' => array(
			'required' => true
		),
		'period_unit' => array(
			'required' => true
		)
	);

	public function save($bLog=true) {

		if(
			$this->period === 'recurring' &&
			$this->period_length == 0
		) {
			// Bei regelmäßig und 0: Auf 1 Tag ändern (sonst Endlosschleife?)
			$this->period_length = 1;
			$this->period_unit = 'days';
		} elseif(
			$this->period === 'one_time' &&
			$this->type === 'fix'
		) {
			// Bei einmalig und fix: Immer 0 setzen (Felder werden ausgeblendet)
			$this->period_length = 0;
			$this->period_unit = 'days';
		}

		parent::save($bLog);
	}

	/**
	 * Prüfungstermine berechnen, basierend auf der übergebenen Kursbuchung
	 *
	 * Da Start und Ende mit dem Leistungszeitraum der Kursbuchung übereinstimmen,
	 * werden diese einfach als Start und Ende benutzt. Die Berechnung ist aufgrund
	 * dem Select start_from ohnehin vom Leistungszeitraum der Kursbuchubg abhängig.
	 *
	 * @param \Core\Helper\DateTime $dFrom
	 * @param \Core\Helper\DateTime $dUntil
	 * @return DateTime[]
	 */
	public function getExaminationDates(DateTime $dFrom, DateTime $dUntil) {

		/** @var DateTime[] $aDates */
		$aDates = [];

		if($this->type === 'fix') {
			// Feste Termine

			if($this->start_date !== '0000-00-00') {
				$dStartFrom = new DateTime($this->start_date);

				if($this->period === 'one_time') {
					// Fix und einmalig: Nur dieses eine Datum
					$aDates[] = $dStartFrom;
				} elseif($this->period === 'recurring') {
					// Regelmäßig, basierend auf Startdatum: Zeitperioden berechnen bis zum Kursende
					$oDatePeriod = new DatePeriod($dStartFrom, $this->getDateInterval(), $dUntil);
					foreach($oDatePeriod as $dDate) {

						// @TODO Workaround für Steinzeit-PHP, irgendwann entfernen (läuft mit PHP 5.6)
						if(!$dDate instanceof \Core\Helper\DateTime) {
							$dDate = \Core\Helper\DateTime::createFromLocalTimestamp($dDate->getTimestamp());
						}

						$aDates[] = $dDate;
					}
				}
			}

		} elseif($this->type === 'individual') {
			// Individuelle Termine (auf Leistungszeitraum basierend)

			if($this->period === 'one_time') {
				// Fix und einmalig: Nur dieses eine Datum

				if($this->start_from === 'after_course_start') {
					// Nach Kursstart: Eingestellte Periode auf Kursstart addieren
					$aDates[] = $dFrom->add($this->getDateInterval());
				} elseif($this->start_from === 'before_course_end') {
					// Vor KursEnde: Eingestellt Periode vom Kursende subtrahieren
					$aDates[] = $dUntil->sub($this->getDateInterval());
				}

			} elseif($this->period === 'recurring') {
				// Regelmäßig, basierend auf Start oder Ende der Kursbuchung

				if($this->start_from === 'after_course_start') {
					// Nach Kursstart: Zeitperioden berechnen, vom Kursstart bis zum Kursende
					$oDatePeriod = new DatePeriod($dFrom, $this->getDateInterval(), $dUntil, DatePeriod::EXCLUDE_START_DATE);
					foreach($oDatePeriod as $dDate) {

						// @TODO Workaround für Steinzeit-PHP, irgendwann entfernen (läuft mit PHP 5.6)
						if(!$dDate instanceof \Core\Helper\DateTime) {
							$dDate = \Core\Helper\DateTime::createFromLocalTimestamp($dDate->getTimestamp());
						}

						$aDates[] = $dDate;
					}

				} elseif($this->start_from === 'before_course_end') {
					// Vor Kursende: Zeitperioden berechnen, vom Kursende bis zum Kursstart
					$dCourseUntil2 = clone $dUntil;

					// DatePeriod kann weder mit negativen Intervallen noch umgedrehten Datumsangaben negativ rechnen
					// http://stackoverflow.com/questions/6350778/negative-dateinterval
					while($dCourseUntil2 >= $dFrom) {
						$aDates[] = clone $dCourseUntil2;
						$dCourseUntil2->sub($this->getDateInterval());
					}
				}

			}

		}

		foreach($aDates as $iKey => &$dDate) {
			if(!$dDate->isBetween($dFrom, $dUntil)) {
				// Datumsangaben rauswerfen, die nicht im Kurszeitraum liegen
				unset($aDates[$iKey]);
				continue;
			}

			$dDate->setTime(0, 0, 0);
		}

		return $aDates;
	}

	/**
	 * DateInterval-Objekt aus Periodenangaben erzeugen
	 *
	 * @return DateInterval
	 */
	public function getDateInterval() {
		$sStr = 'P'.(int)$this->period_length;

		if($this->period_unit === 'days') {
			$sStr .= 'D';
		} elseif($this->period_unit === 'weeks') {
			$sStr .= 'W';
		}

		return new DateInterval($sStr);
	}
	
}
