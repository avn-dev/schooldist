<?php

namespace Core\Helper;

use \Core\DTO\DateRange;

class DateTime extends \DateTime {
	
	/**
	 * Prüft, ob das Datum innerhalb eines Zeitraumes liegt
	 * 
	 * @param \DateTime $oDatePeriodFrom
	 * @param \DateTime $oDatePeriodUntil
	 * @return bool
	 */
	public function isBetween(\DateTime $oDatePeriodFrom, \DateTime $oDatePeriodUntil) {
		
		if(
			$this >= $oDatePeriodFrom &&
			$this <= $oDatePeriodUntil
		) {
			return true;
		}
		
		return false;
	}

	/**
	 * Prüft, ob ein String ein gültiges Datum nach dem angegebenen Format darstellt
	 *
	 * @param string $sDate
	 * @param string $sFormat
	 * @return bool
	 */
	public static function isDate($sDate, $sFormat) {

		$dDate = DateTime::createFromFormat($sFormat, $sDate);

		if(
			$dDate &&
			$dDate->format($sFormat) == $sDate
		) {
			return true;
		}

		return false;

	}

	/**
	 * DateTime erzeugen aus Timestamps, wie sie die Software benutzt
	 *
	 * Die Software hat (besonders WDDate) einen großen Fehler: Alle Timestamps sind lokal,
	 * dabei sollten diese alle UTC sein. Wenn man DateTime mit einem Timestamp füttert,
	 * geht auch PHP davon aus, dass ein Timestamp UTC sein sollte, und setzt hier automatisch
	 * UTC als Zeitzone, und nicht die Default-Timezone. Dass das zu Problemen führt,
	 * kann man sich ja denken…
	 *
	 * @param int $iTimestamp
	 * @return DateTime
	 */
	public static function createFromLocalTimestamp($iTimestamp) {

		$dDate = new static('@'.$iTimestamp);

		// setTimezone() setzt NUR die Timezone im Objekt, und konvertiert nichts
		$dDate->setTimezone(new \DateTimeZone(date_default_timezone_get()));

		return $dDate;

	}

	/**
	 * Datumsangabe aus Benutzereingabe konvertieren (auf Basis übergebener Format-Klasse)
	 *
	 * @param string $sDate
	 * @param \Ext_Gui2_View_Format_Date $oFormat
	 * @return bool|DateTime
	 */
	public static function createDateFromInput($sDate, \Ext_Gui2_View_Format_Date $oFormat) {

		// Kann ein String (konvertiert oder nicht konvertiert) oder false sein
		$mDate = $oFormat->convert($sDate);

		if(static::isDate($mDate, 'Y-m-d')) {
			$dDate = static::createFromFormat('Y-m-d', $mDate);
			$dDate->setTime(0, 0, 0);
			return $dDate;
		}

		return false;

	}

	/**
	 * Datumseingaben aus Filter überprüfen, konvertieren und zusätzlich Plausibilität prüfen
	 *
	 * @param string $sFrom
	 * @param string $sUntil
	 * @param \Ext_Gui2_View_Format_Date $oFormat
	 * @return DateRange|string
	 */
	public static function createDatesFromTimefilterInput($sFrom, $sUntil, \Ext_Gui2_View_Format_Date $oFormat) {

		if(
			empty($sFrom) ||
			empty($sUntil)
		) {
			return 'EMPTY';
		}

		$mFrom = static::createDateFromInput($sFrom, $oFormat);
		if(!$mFrom instanceof \DateTime) {
			return 'FROM_NOT_VALID';
		}

		$mUntil = static::createDateFromInput($sUntil, $oFormat);
		if(!$mUntil instanceof \DateTime) {
			return 'UNTIL_NOT_VALID';
		}

		if($mFrom > $mUntil) {
			return 'FROM_GREATER_THAN_UNTIL';
		}

		return new DateRange($mFrom, $mUntil);

	}

	/**
	 * Prüfen, ob sich zwei Datumszeiträume überschneiden
	 *
	 * @param \DateTime $dStart1
	 * @param \DateTime $dEnd1
	 * @param \DateTime $dStart2
	 * @param \DateTime $dEnd2
	 * @return bool
	 */
	public static function checkDateRangeOverlap(\DateTime $dStart1, \DateTime $dEnd1, \DateTime $dStart2, \DateTime $dEnd2) {

		if(
			$dStart1 <= $dEnd2 &&
			$dStart2 <= $dEnd1
		) {
			return true;
		}

		return false;

	}

	/**
	 * Gibt die Schnittmenge zweier Zeiträume zurück
	 *
	 * @param \DateTime $dStart1
	 * @param \DateTime $dEnd1
	 * @param \DateTime $dStart2
	 * @param \DateTime $dEnd2
	 * @return null|array
	 */
	public static function getDateRangeIntersection(\DateTime $dStart1, \DateTime $dEnd1, \DateTime $dStart2, \DateTime $dEnd2) {

		// Keine Berührung
		if(
			$dStart1 > $dEnd2 || 
			$dStart2 > $dEnd1
		) {
			return null;
		}

		$aReturn = [
			'start' => null,
			'end' => null
		];
		
		if(
			$dStart1 > $dStart2
		) {
			$aReturn['start'] = $dStart1;
		} else {
			$aReturn['start'] = $dStart2;
		}
		
		if(
			$dEnd1 < $dEnd2
		) {
			$aReturn['end'] = $dEnd1;
		} else {
			$aReturn['end'] = $dEnd2;
		}

		return $aReturn;
	}

	
	
	/**
	 * @TODO In getNightsInPeriodIntersection umbenennen
	 *
	 * Schnittmenge ausrechnen, wie viele Tage einer Periode in eine andere fallen
	 *
	 * Das Enddatum wird nicht mit einbezogen, daher werden hier quasi Nächte gezählt! (Das ist das übliche + 1)
	 *
	 * @param \DateTime $dPeriodFrom
	 * @param \DateTime $dPeriodUntil
	 * @param \DateTime $dEntryFrom
	 * @param \DateTime $dEntryUntil
	 * @return int|bool
	 */
	public static function getDaysInPeriodIntersection(\DateTime $dPeriodFrom, \DateTime $dPeriodUntil, \DateTime $dEntryFrom, \DateTime $dEntryUntil) {

		if(
			$dPeriodFrom >= $dEntryFrom &&
			$dPeriodUntil <= $dEntryUntil
		) {

			// Period-Zeitraum fällt komplett in Entry-Zeitraum
			// Period:	  +-+
			// Entry:	+-----+
			$iDays = $dPeriodUntil->diff($dPeriodFrom)->days;

		} elseif(
			$dPeriodFrom <= $dEntryFrom &&
			$dPeriodUntil >= $dEntryUntil
		) {

			// Entry-Zeitraum fällt komplett in Period-Zeitraum
			// Period:	+-----+
			// Entry:	  +-+
			$iDays = $dEntryUntil->diff($dEntryFrom)->days;

		} elseif(
			$dPeriodFrom <= $dEntryFrom &&
			$dPeriodUntil >= $dEntryFrom
		) {

			// Period-Zeitraum überschneidet sich nur rechts (until) mit Entry-Zeitraum
			// Period:	+-+
			// Entry:	 +-----+
			$iDays = $dPeriodUntil->diff($dEntryFrom)->days;

		} elseif(
			$dPeriodFrom <= $dEntryUntil &&
			$dPeriodUntil >= $dEntryUntil
		) {

			// Period-Zeitraum überschneidet sich nur links (from) mit Entry-Zeitraum
			// Period:	     +-+
			// Entry:	+-----+
			$iDays = $dEntryUntil->diff($dPeriodFrom)->days;

		} else {

			// Beide Zeiträume überschneiden sich nicht
			// Period:	+-+
			// Entry:	    +-+
			$iDays = 0;

		}

		return $iDays;

	}

	/**
	 * @deprecated
	 * @see \Ext_TC_Util::generateDatePeriods
	 *
	 * Alle Jahre zurückliefern, welche zwischen den beiden Datumsangaben liegen
	 *
	 * @param \DateTime $dStart Startdatum
	 * @param \DateTime $dEnd Enddatum
	 * @param bool $bCompleteYears Komplette Jahre (inklusive) oder nur ab/bis Start-/Enddatum (exklusive)
	 * @return DateRange[]
	 */
	public static function getYearPeriods(\DateTime $dStart, \DateTime $dEnd, $bCompleteYears = true) {

		$aYears = [];

		$dStartClone = clone $dStart;
		$dStartClone->modify('first day of january');

		$oPeriod = new \DatePeriod($dStartClone, new \DateInterval('P1Y'), $dEnd);
		foreach($oPeriod as $dDate) {
			/** @var DateTime $dDate */

			$dYearEnd = clone $dDate;
			$dYearEnd->modify('last day of december');
			$dYearEnd->setTime(23, 59, 59);

			$aYears[] = new DateRange($dDate, $dYearEnd);
		}

		if(!$bCompleteYears) {
			$aYears[0]->from = clone $dStart;
			$aYears[count($aYears) - 1]->until = clone $dEnd;
		}

		return $aYears;

	}

	/**
	 * @deprecated
	 * @see \Ext_TC_Util::generateDatePeriods
	 *
	 * Alle Monate zurückliefern, welche zwischen den beiden Datumsangaben liegen
	 *
	 * Hier kommt min. immer ein Eintrag, nämlich der gleiche Monat!
	 *
	 * @param \DateTime $dStart Startdatum
	 * @param \DateTime $dEnd Enddatum
	 * @param bool $bCompleteMonths Komplette Monate (inklusive) oder nur ab/bis Start-/Enddatum (exklusive)
	 * @return DateRange[]
	 */
	public static function getMonthPeriods(\DateTime $dStart, \DateTime $dEnd, $bCompleteMonths = true) {

		$cCreateMonthEnd = function (\DateTime $dDate) {
			$dDate = clone $dDate;
			$dDate->modify('last day of this month');
			$dDate->setTime(23, 59, 59);
			return $dDate;
		};

		if ($dStart > $dEnd) {
			$dEnd = $dStart;
		}

		$aMonths = array();

		$dStartClone = clone $dStart;
		$dStartClone->modify('first day of this month');
		$dStartClone->setTime(0, 0, 0);

		$oPeriod = new \DatePeriod($dStartClone, new \DateInterval('P1M'), $dEnd);
		foreach($oPeriod as $dMonth) {
			$aMonths[] = new DateRange($dMonth, $cCreateMonthEnd($dMonth));
		}

		// Immer einen Monat zurückliefern
		// Das war bei $bCompleteMonths=false schon immer so (und Code baut darauf auf), da die Bedingung unten dann ein leeres Objekt erzeugt hat
		if (empty($aMonths)) {
			$aMonths[] = new DateRange($dStartClone, $cCreateMonthEnd($dEnd));
		}

		if(!$bCompleteMonths) {
			
			if($aMonths[0]->from != $dStart) {
				$aMonths[0]->partial = true;
			}
			$aMonths[0]->from = clone $dStart;
			
			if($aMonths[count($aMonths) - 1]->until != $dEnd) {
				$aMonths[count($aMonths) - 1]->partial = true;
			}
			$aMonths[count($aMonths) - 1]->until = clone $dEnd;
			
		}

		return $aMonths;

	}

	/**
	 * @deprecated
	 * @see \Ext_TC_Util::generateDatePeriods
	 *
	 * Alle Wochen zurückliefern, welche zwischen den beiden Datumsangaben liegen
	 *
	 * Diese Methode ersetzt die Methode WDDate::getWeekLimits() und erweitert um die Möglichkeit der Exklusivität.
	 * @see WDDate::getWeekLimits()
	 *
	 * @param \DateTime $dStart Startdatum
	 * @param \DateTime $dEnd Enddatum
	 * @param bool $bCompleteWeeks Komplette Wochen (inklusive) oder nur ab/bis Start-/Enddatum (exklusive)
	 * @return DateRange[]
	 */
	public static function getWeekPeriods(\DateTime $dStart, \DateTime $dEnd, $bCompleteWeeks = true) {
		$aWeeks = array();

		$dStartClone = clone $dStart;
		$dEndClone = clone $dEnd;

		/*
		 * Nicht »monday this week« benutzen, da PHP dann Sonntag als Wochenstart benutzt
		 * https://bugs.php.net/bug.php?id=68603
		 */
		if($dStartClone->format('N') != 1) {
			$dStartClone->modify('last monday');
		}

		if($dEndClone->format('N') != 7) {
			$dEndClone->modify('next sunday');
		}

		$dStartClone->setTime(0, 0, 0);
		$dEndClone->setTime(23, 59, 59);

		$oPeriod = new \DatePeriod($dStartClone, new \DateInterval('P1W'), $dEndClone);
		foreach($oPeriod as $dWeek) {
			/** @var DateTime $dWeek */

			$dWeekEnd = clone $dWeek;
			$dWeekEnd->modify('next sunday');
			$dWeekEnd->setTime(23, 59, 59);

			$aWeeks[] = new DateRange($dWeek, $dWeekEnd);
		}

		// Erste und letzte Woche jeweils kürzen, wenn nicht volle Wochen zurückgeliefert werden sollen
		if(!$bCompleteWeeks) {
			$aWeeks[0]->from = clone $dStart;
			$aWeeks[count($aWeeks) - 1]->until = clone $dEnd;
		}

		return $aWeeks;
	}

}
