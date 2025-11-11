<?php

namespace TsStatistic\Service;

use Core\DTO\DateRange;
use Core\Helper\DateTime;

/**
 * Service zum Berechnen des Item-Betrags (Discount, Special, Tax)
 */
class DocumentItemAmount {

	/**
	 * Typ des Betrags berechnen
	 *
	 * null: brutto oder netto (je nach Dokument)
	 * gross: brutto
	 * net: netto
	 * commission: Provision
	 *
	 * @var string
	 */
	public $sAmountType;

	/**
	 * Betrag nach Leistungszeitraum splitten
	 *
	 * @var bool
	 */
	public $bSplitByServicePeriod = false;

	/**
	 * Kalkulation von Steuern
	 *
	 * 0: Keine Steuern (inkl. Steuern abziehen, Standard bei Statistiken)
	 * 1: Steuern addieren (exkl. Steuern addieren)
	 * 2: Nur Steuerbetrag
	 *
	 * @var int
	 */
	public $iTaxMode = 0;

	/**
	 * Betrag nach Leistungszeitraum splitten: Zeitraum
	 *
	 * @var DateRange
	 */
	public $oServicePeriodSplitDateRange;

	/**
	 * Feld-Mapping
	 *
	 * @var array
	 */
	private $aFields = [
		'item_type' => 'kidvi.type',
		'item_amount' => 'kidvi.amount',
		'item_amount_net' => 'kidvi.amount_net',
		'item_amount_commission' => 'kidvi.amount_provision',
		'item_amount_discount' => 'kidvi.amount_discount',
		'item_tax' => 'kidvi.tax',
		'item_tax_type' => 'kidv.tax',
		'item_from' => 'kidvi.index_from',
		'item_until' => 'kidvi.index_until',
		'item_index_special_amount_gross' => 'kidvi.index_special_amount_gross',
		'item_index_special_amount_net' => 'kidvi.index_special_amount_net',
		'item_index_special_amount_gross_vat' => 'kidvi.index_special_amount_gross_vat',
		'item_index_special_amount_net_vat' => 'kidvi.index_special_amount_net_vat',
		'item_additional_info' => 'kidvi.additional_info',
		'item_costs_calculation' => 'kc.calculate',
		'item_costs_booking_timepoint' => 'kc.timepoint',
		'document_type' => 'kid.type',
		'course_startday' => 'cdb2.course_startday'
	];

	/**
	 * Item-Betrag berechnen
	 *
	 * Ursprüngliche Methode:
	 * @see \Ext_Thebing_Management_Statistic_Static_Abstract::getItemAmount()
	 *
	 * @param array $aItem
	 * @return float|int|mixed
	 */
	public function calculate(array $aItem) {

		$this->checkNeededKeys($aItem);

		// $this->sAmountType nicht gesetzt: Amount-Typ abhängig vom Dokument-Typ
		$sAmountType = $this->sAmountType;
		if($sAmountType === null) {
			$sAmountType = 'gross';
			if(strpos($aItem['document_type'], 'netto') !== false) {
				$sAmountType = 'net';
			}
		}

		// Specials immer ignorieren, da diese über die entsprechenden Index-Spalten abgezogen werden
		// Außer bei Provisionswert, da es die Index-Spalten dafür nicht gibt!
		if(
			$sAmountType !== 'commission' &&
			$aItem['item_type'] === 'special'
		) {
			return 0;
		}

		// Auf Leistungszeitraum splitten
		$bSplitAmount = false;
		$dItemFrom = $dItemUntil = null;
		if($this->bSplitByServicePeriod) {
			$bSplitAmount = true;

			$dItemFrom = new DateTime($aItem['item_from']);
			$dItemUntil = new DateTime($aItem['item_until']);

//			$this->setCourseServicePeriod($aItem, $dItemFrom, $dItemUntil);
//			$this->setAccommodationServicePeriod($aItem, $dItemFrom, $dItemUntil);

			// Einmalige Gebühren werden auf den ersten/letzten Tag gebucht
			if(
				$aItem['item_costs_calculation'] != \Ext_Thebing_School_Additionalcost::CALCULATION_PER_WEEK &&
				in_array($aItem['item_type'], ['additional_general', 'additional_course', 'additional_accommodation'])
			) {
				$bSplitAmount = false;
				if($aItem['item_costs_booking_timepoint'] == 2) {
					$dItemFrom = $dItemUntil; // Letzter Tag
				} else {
					$dItemUntil = $dItemFrom; // Erster Tag
				}
			}

			if(!DateTime::checkDateRangeOverlap($dItemFrom, $dItemUntil, $this->oServicePeriodSplitDateRange->from, $this->oServicePeriodSplitDateRange->until)) {
				// Item fällt nicht in den Zeitraum
				return 0;
			}
		}

		if($sAmountType === 'commission') {
			$fAmount = (float)$aItem['item_amount_commission'];
			$fAmountSpecial = 0; // TODO Keine Ahnung, was hier gemacht werden müsste
			$fAmountSpecialVat = 0;
		} elseif($sAmountType === 'net') {
			$fAmount = (float)$aItem['item_amount_net'];
			$fAmountSpecial = (float)$aItem['item_index_special_amount_net'];
			$fAmountSpecialVat = (float)$aItem['item_index_special_amount_net_vat'];
		} else {
			$fAmount = (float)$aItem['item_amount'];
			$fAmountSpecial = (float)$aItem['item_index_special_amount_gross'];
			$fAmountSpecialVat = (float)$aItem['item_index_special_amount_gross_vat'];
		}

		// Discount steht im Item als Prozentwert
		if(
			//$sAmountType !== 'commission' && // Auch bei Provision, da der Wert ebenso ohne einberechneten Discount gespeichert wird
			$aItem['item_amount_discount'] > 0
		) {
			$fAmount -= $fAmount / 100 * $aItem['item_amount_discount'];
		}

		$fAmountWithDiscount = $fAmount;

		// Option 0: Steuer abziehen
		if($this->iTaxMode === 0) {
			// Bei »Steuern inklusive« den Steuerbetrag abziehen
			// Grund: Bei dieser Option steht im Amount der Steuer-Bruttobetrag
			if($aItem['item_tax_type'] == 1) {
				$fAmount -= $fAmount - ($fAmount / ($aItem['item_tax'] / 100 + 1));
			}
		// Optionen 1 und 2: Steuer draufrechnen
		} else {
			// Nur bei »Steuer exklusive«, da bei »Steuern inklusive« Amount bereits der Steuer-Bruttobetrag ist
			if($aItem['item_tax_type'] == 2) {
				$fAmount += $fAmount * ($aItem['item_tax'] / 100);
			}
		}

		// Bei nur Steuer berechnen: Originalwert abziehen
		if($this->iTaxMode === 2) {

			// Bei »Steuer inklusive« muss der Steuerbetrag addiert werden, da danach subtrahiert wird
			if($aItem['item_tax_type'] == 1) {
				$fAmount += $fAmount - ($fAmount / ($aItem['item_tax'] / 100 + 1));
			}

			$fAmount -= $fAmountWithDiscount;
		}

		// Special abziehen
		if(abs(round($fAmountSpecial, 5)) > 0) {
			// Wenn nur Steuer berechnet werden soll, darf der Special-Betrag gar nicht erst addiert werden (in der Spalte steht Nettobetrag)
			if($this->iTaxMode !== 2) {
				$fAmount += $fAmountSpecial;
			}

			if($this->iTaxMode !== 0) {
				// Steuer des Specials (Steuer-Nettobetrag hier!) ebenso abziehen (Wert ist minus), wenn Steuern addiert werden sollen
				$fAmount += $fAmountSpecialVat;
			}
		}

		// Sonderfall Transfer als Paket: Nicht splitten und Preis muss halbiert werden, wenn Paket nicht komplett in Filterzeitraum fällt
		// Anmerkung: Transfere ohne 2-Wege-Paket oder individuelle Transfer haben nur einen Tag in index_from/index_until
		if($aItem['item_type'] === 'transfer') {
			$aAdditionalInfo = json_decode($aItem['item_additional_info'], true);
			if(!empty($aAdditionalInfo['transfer_package_id'])) {
				$bSplitAmount = false;

				if(
					// Transfer-Start/Ende fällt überhaupt nicht in den Zeitraum (z.B. 3 Monate Leistungszeitraum und der mittlere Monat ist der Filterzeitraum)
					!$dItemFrom->isBetween($this->oServicePeriodSplitDateRange->from, $this->oServicePeriodSplitDateRange->until) &&
					!$dItemUntil->isBetween($this->oServicePeriodSplitDateRange->from, $this->oServicePeriodSplitDateRange->until)
				) {
					$fAmount = 0;
				} elseif(
					// Paket fällt nur teilweise in den Zeitraum: Start oder Ende müssen Filterzeitraum schneiden
					!$dItemFrom->isBetween($this->oServicePeriodSplitDateRange->from, $this->oServicePeriodSplitDateRange->until) ||
					!$dItemUntil->isBetween($this->oServicePeriodSplitDateRange->from, $this->oServicePeriodSplitDateRange->until)
				) {
					$fAmount /= 2;
				}
			}
		}

		// Auf Leistungszeitraum splitten
		if($bSplitAmount) {
			$fAmount = \Ext_TC_Util::getSplittedAmountByDates($fAmount, $this->oServicePeriodSplitDateRange->from, $this->oServicePeriodSplitDateRange->until, $dItemFrom, $dItemUntil);
		}

		return $fAmount;

	}

	/**
	 * Benötigte DB-Felder für die Kalkulation
	 *
	 * @return array
	 */
	private function getFields() {

		$aFields = [
			'item_type',
			'item_amount',
			'item_amount_net',
			'item_amount_discount',
			'item_tax',
			'item_tax_type',
			'item_index_special_amount_gross',
			'item_index_special_amount_net'
		];

		if($this->sAmountType === null) {
			$aFields[] = 'document_type';
		}

		// Wenn nur Provision berechnet werden soll, muss die entsprechende Spalte da sein
		if($this->sAmountType === 'commission') {
			$aFields[] = 'item_amount_commission';
		}

		// Bei Steuerkalkulation müssen die Werte auch vorhanden sein
		if($this->iTaxMode > 0) {
			$aFields[] = 'item_index_special_amount_gross_vat';
			$aFields[] = 'item_index_special_amount_net_vat';
		}

		// Wenn gesplittet werden soll, müssen Zeitraum und weitere Infos von Gebühren vorhanden sein
		if($this->bSplitByServicePeriod) {
			$aFields[] = 'item_from';
			$aFields[] = 'item_until';
			$aFields[] = 'item_additional_info';
			$aFields[] = 'item_costs_calculation';
			$aFields[] = 'item_costs_booking_timepoint';
			$aFields[] = 'course_startday';
		}

		return $aFields;

	}

	/**
	 * @return array|false
	 */
	public function getFieldsSqlSelect() {

		$aFields = $this->getFields();
		$aLines = [];

		foreach($aFields as $sField) {
			$aLines[] = $this->aFields[$sField].' `'.$sField.'`';
		}

		return join(",\n", $aLines);

	}

	public function getSelectForBuilder(): array {

		return array_map(function (string $alias) {
			return sprintf('%s as %s', $this->aFields[$alias], $alias);
		}, $this->getFields());

	}

	/**
	 * Prüfen, ob alle Werte im $aItem vorhanden sind
	 *
	 * Die Prüfung ist dafür eingebaut, damit man beim Schreiben der SELECTs keine Werte vergisst,
	 * was schnell passieren kann. Die Fehler in der Preisberechnung würden dann erst nicht auffallen.
	 *
	 * @param array $aItem
	 */
	private function checkNeededKeys(array $aItem) {

		// Wenn gesplittet werden soll, müssen Zeitraum und weitere Infos von Gebühren vorhanden sein
		if($this->bSplitByServicePeriod) {
			if(!$this->oServicePeriodSplitDateRange instanceof DateRange) {
				throw new \RuntimeException('Filter date range missing for amount splitting');
			}
		}

		$aNeededKeys = $this->getFields();

		// Validieren, ob Array alle benötigten Werte hat
		if(count(array_intersect_key(array_flip($aNeededKeys), $aItem)) !== count($aNeededKeys)) {
			throw new \InvalidArgumentException('Not all needed item keys are set for '.__METHOD__);
		}

	}

	/**
	 * #7511: Leistungszeitraum auf volle Kurswochen korrigieren
	 *
	 * Starttag Montag: Da der Kurs i.d.R. von Montag-Freitag geht, fällt die letzte Woche
	 * beim üblichen Splitten nur mit 5 Tagen in die Woche, alle anderen Wochen aber mit 7 Tagen.
	 * Das ergibt in jedem Fall komische Beträge, die auch kein Kunde nachvollziehen kann.
	 *
	 * In dieser Methode wird der Kurszeitraum ermittelt und auf volle Wochen gerundet, das
	 * Enddatum wird dann auf den Endtag der letzten Woche gesetzt (demnach Sonntag). Startet
	 * der Kurs nicht an einem Montag, wird auf den letzten Montag gesprungen.
	 *
	 * Tatsächlich eingestellte Wochen in der Kursbuchung werden nicht beachtet, obwohl diese
	 * für die Preisberechnung relevant sind. Das Item hat allerdings in der Hinsicht nichts mehr
	 * mit der Kursbuchung zu tun, da diese beliebig verändert werden kann.
	 *
	 * Wenn nun also Montag-Montag (danach) eingestellt ist, während aber nur eine Woche eingestellt ist,
	 * wird der Kurs auf zwei Wochen gesplittet, da das eben 2 Wochen sind. Die Klassenplanung würde
	 * so auch nicht funktionieren, da es für die zweite Woche keinen Tuition-Index gibt.
	 * @TODO Damit das hier korrekt funktioniert, muss #9675 eingebaut werden.
	 *
	 * @param array $aItem
	 * @param \DateTime $dItemFrom
	 * @param \DateTime $dItemUntil
	 */
	public function setCourseServicePeriod(array $aItem, \DateTime &$dItemFrom, \DateTime &$dItemUntil) {

//		if ($aItem['item_type'] !== 'course') {
		// Wird nach #18682 nicht mehr ausgeführt
			return;
//		}

		// TODO Nach wie vor ungetestet
//		if((int)$aItem['course_startday'] !== 1) {
//			throw new \UnexpectedValueException('Implementation of course_startday !== 1 not tested!');
//		}

		$oDiff = $dItemFrom->diff($dItemUntil);
		$iWeeks = ceil(($oDiff->days + 1) / 7); // +1, da Tage gezählt werden (nicht Nächte) / 1 Tag Kurs darf auch nicht 0 sein

		$dFrom = \Ext_Thebing_Util::getPreviousCourseStartDay($dItemFrom, $aItem['course_startday']);
		$dUntil = clone $dFrom;
		$dUntil->add(new \DateInterval('P'.$iWeeks.'W'));
		$dUntil->sub(new \DateInterval('PT1S'));

		$dItemFrom = $dFrom;
		$dItemUntil = $dUntil;

	}

	public function setAccommodationNightServicePeriod(array $aItem, \DateTime &$dItemFrom, \DateTime &$dItemUntil) {

		if (!in_array($aItem['item_type'], ['accommodation', 'extra_nights', 'extra_weeks'])) {
			return;
		}

		if ($dItemFrom->diff($dItemUntil)->days > 0) {
			$dItemFrom->add(new \DateInterval('P1D'));
		}

	}

}
