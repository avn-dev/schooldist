<?php

/**
 * PaymentTerms für Anzahlung/Restzahlung der Version generieren
 *
 * Modi:
 * $oPaymentCondition nicht gesetzt, $aPaymentTerms nicht gesetzt: Final Payment generieren (neu, keine Bedingung gefunden)
 * $oPaymentCondition nicht gesetzt, $aPaymentTerms gesetzt: $aPaymentTerms übernehmen (edit, individuell)
 * $oPaymentCondition gesetzt, $aPaymentTerms nicht gesetzt: Komplett neu generieren (neu, Bedingung gefunden)
 * $oPaymentCondition gesetzt, $aPaymentTerms gesetzt: Komplett neu generieren, Datumsangaben übernehmen (edit, Bedingung gesetzt)
 */
class Ext_TS_Document_PaymentCondition {

	/**
	 * @var Ext_TS_Inquiry_Abstract
	 */
	private $oInquiry;

	/**
	 * Beim Erstellen ohne vorhandene Terms keine Beträge berechnen, da dies im Dialog vom JS
	 * übernommen wird und dort mit den tatsächlichen Beträgen (netto) gerechnet wird. Die
	 * Berechnungsmethode in dieser Klasse wird nur für die automatische Generierung benötigt!
	 *
	 * @var bool
	 */
	private $bCalculateAmounts;

	/**
	 * @var Ext_TS_Payment_Condition|null
	 */
	private $oPaymentCondition = null;

	/**
	 * @var Ext_TS_Document_Version_PaymentTerm[]
	 */
	private $aPaymentTerms = null;

	/**
	 * @var DateTime
	 */
	private $dDocumentDate;

	/**
	 * @var bool
	 */
	private $bPartialInvoice = false;

	private $depositAmount = 0;

	/**
	 * @param Ext_TS_Inquiry_Abstract $oInquiry
	 * @param bool $bCalculateAmounts
	 */
	public function __construct(Ext_TS_Inquiry_Abstract $oInquiry, $bCalculateAmounts = false) {
		$this->oInquiry = $oInquiry;
		$this->bCalculateAmounts = $bCalculateAmounts;
	}

	/**
	 * @param Ext_TS_Document_Version_PaymentTerm[] $aPaymentTerms
	 */
	public function setPaymentTerms(array $aPaymentTerms) {
		$this->aPaymentTerms = $aPaymentTerms;
	}

	/**
	 * Sobald eine Zahlbedingung gesetzt ist, werden immer Items aus dieser generiert!
	 *
	 * @param Ext_TS_Payment_Condition $oPaymentCondition
	 */
	public function setPaymentCondition(Ext_TS_Payment_Condition $oPaymentCondition) {
		$this->oPaymentCondition = $oPaymentCondition;
	}

	/**
	 * @return Ext_TS_Payment_Condition|null
	 */
	public function getPaymentCondition() {
		if(
			// Nur Standardbedingung, wenn Zahlungsbedingung weder gesetzt noch vorhandene Terms gesetzt
			$this->oPaymentCondition === null &&
			empty($this->aPaymentTerms) // Notwendig fürs Editieren
		) {
			$this->oPaymentCondition = $this->oInquiry->getPaymentCondition();
		}

		return $this->oPaymentCondition;
	}

	/**
	 * @param string $sDate
	 */
	public function setDocumentDate(string $sDate=null) {
		$this->dDocumentDate = new DateTime();
		if(\Core\Helper\DateTime::isDate($sDate, 'Y-m-d')) {
			$this->dDocumentDate = new DateTime($sDate);
		}
	}

	public function setPartialInvoice() {
		$this->bPartialInvoice = true;
	}

	public function getCalculateAmounts() {
		return $this->bCalculateAmounts;
	}

	public function setCalculateAmounts(bool $bCalculateAmounts) {
		$this->bCalculateAmounts = $bCalculateAmounts;
	}

	/**
	 * @param array $aItems
	 * @return Ext_TS_Document_PaymentCondition_Row[]
	 */
	public function generateRows(array $aItems) {

		// Terms vorhanden und keine Zahlungsbedingung gesetzt: Vorhandene setzen (komplett individuell)
		if(
			$this->oPaymentCondition === null &&
			!empty($this->aPaymentTerms)
		) {
			return $this->generateIndividualRows();
		}

		$aRows = []; /** @var Ext_TS_Document_PaymentCondition_Row[] $aRows */
		$fAmount = $fAmountWithoutPeriods = $fAmountOpen = $fAmountAdditionalFees = 0;
		
		// Items bei denen der Gesamtbetrag auf Zeiträume aufgeteilt ist
		$aItemsWithPeriods = [];

		if($this->bCalculateAmounts) {
			foreach($aItems as $aItem) {

				if(isset($aItem['amount_with_tax'])) {
					// Kommt direkt vom Frontend, mögliche externe Steuer bereits berechnet
					$fAmount = $aItem['amount_with_tax'];
				} else {
					// Keine externe Steuer!
					$fAmount = $aItem['amount'];
				}
				
				if(
					strpos($aItem['type'], 'additional_') === 0 ||
					$aItem['type'] === 'payment_surcharge'
				) {
					$fAmountAdditionalFees += $fAmount;
				}
				
				// Wenn der Betrag einer Position auf einzelne Zeiträume aufgeteilt vorhanden ist, dann gesondert speichern
				if(
					!empty($aItem['additional_info']) &&
					!empty($aItem['additional_info']['periods'])
				) {
					$aItemsWithPeriods[] = $aItem;
				} else {
					$fAmountWithoutPeriods += $fAmount;
				}
				
				$fAmountOpen += $fAmount;
				
			}
		}
		unset($fAmount);

		$oPaymentCondition = $this->getPaymentCondition();

		// Keine Zahlungsbedingung gefunden oder leeres Objekt: Final Setting generieren
		if(
			$oPaymentCondition === null ||
			!$oPaymentCondition->exist()
		) {
			$oSetting = new Ext_TS_Payment_Condition_Setting();
			$oSetting->type = 'final';
			$oSetting->due_days = 0;
			$oSetting->due_direction = 'before';
			$oSetting->due_type = 'document_date';
			$aSettings = [$oSetting];
		} else {
			$aSettings = $oPaymentCondition->getSettings();
		}

		// Bei Teilzahlung nur Restzahlung, da die Ratenzahlung die einzelnen Rechnungen sind
		if($this->bPartialInvoice) {
			if(!$oPaymentCondition->isEligibleForPartialInvoice()) {
				/*
				 * @todo Es kann wohl passieren, dass man eine Teilrechnung ohne korrekte Zahlungsbedingung erstellt. 
				 * Dann kann man mit dieser Exception die nicht mehr bearbeiten. Das muss abgefangen werden.
				 */
				#throw new RuntimeException('Invalid payment condition for partial invoice!');
			}
			$oSetting = reset($aSettings);
			$oSetting->type = 'final';
			$oSetting->due_type = 'document_date';
			$aSettings = [$oSetting];
		}

		$this->depositAmount = 0;

		// Settings durchlaufen
		foreach($aSettings as $oSetting) {
			if($oSetting->type === 'deposit') {
				$oRow = $this->getDepositPaymentRow($oSetting, $aItems);
				if($this->bCalculateAmounts) {
					$this->depositAmount += $oRow->fAmount;
				}
				$aRows[] = $oRow;
			} elseif($oSetting->type === 'installment') {
				$aInstallmentRows = $this->getInstallmentPaymentRows($oSetting, $aItemsWithPeriods, $fAmountOpen, $fAmountWithoutPeriods, $fAmountAdditionalFees);
				if($this->bCalculateAmounts) {
					foreach($aInstallmentRows as $oRow) {
						$fAmountOpen -= $oRow->fAmount;
					}
				}
				$aRows = array_merge($aRows, $aInstallmentRows);
			} else {
				$oRow = $this->getFinalPaymentRow($oSetting, $fAmountOpen);
				if($this->bCalculateAmounts) {
					$fAmountOpen -= $oRow->fAmount;
				}
				$aRows[] = $oRow;
			}

		}

		if($this->depositAmount > 0) {
			foreach($aRows as $oRow) {
				if($oRow->sType !== 'deposit') {
					$oRow->fAmount -= $this->depositAmount;
					$this->depositAmount = 0;
					break;
				}
			}
		}

		if(bccomp(sprintf('%F', $fAmountOpen), 0, 2) !== 0) {
			throw new RuntimeException('The payment terms could not be calculated correctly. ('.$fAmountOpen.')');
		}
		
		$this->adjustPaymentRowsDueDates($aRows);

		return $aRows;
	}

	/**
	 * Terms vorhanden und keine Zahlungsbedingung gesetzt: Vorhandene setzen
	 *
	 * @return array
	 */
	private function generateIndividualRows() {

		$aRows = [];

		foreach($this->aPaymentTerms as $oPaymentTerm) {
			$oRow = new Ext_TS_Document_PaymentCondition_Row();
			$oRow->sType = $oPaymentTerm->type;
			$oRow->dDate = $oPaymentTerm->date;
			$oRow->fAmount = $oPaymentTerm->amount;
			$oRow->sLabel = $this->getLabel($oPaymentTerm->type);
			$oRow->iSettingId = $oPaymentTerm->setting_id;
			$aRows[] = $oRow;
		}

		return $aRows;

	}

	/**
	 * @param Ext_TS_Payment_Condition_Setting $oSetting
	 * @param array $aItems
	 * @return Ext_TS_Document_PaymentCondition_Row
	 */
	private function getDepositPaymentRow(Ext_TS_Payment_Condition_Setting $oSetting, array $aItems) {

		$fAmount = 0;

		if($this->bCalculateAmounts) {

			// Gleiche Implementierung in studentlists.js::calculatePaymentTermAmounts()
			foreach($oSetting->amounts as $aAmount) {

				if($aAmount['setting'] === 'amount') {
					// Fixbetrag (Währung)

					if($aAmount['type_id'] == $this->oInquiry->getCurrency()) {
						$fAmount = (float)$aAmount['amount'];
					}

				} elseif($aAmount['setting'] === 'percent') {
					// Prozente

					foreach($aItems as $aItem) {

						$sType = str_replace('_', '', $aItem['type']);

						if(
							$aAmount['type'] === 'all' || (
								$aAmount['type'] === $sType && (
									$aAmount['type_id'] == $aItem['type_id'] ||
									$aAmount['type_id'] == 0
								)
							)
						) {
							// TODO Aktuell nur gross, da nur im Frontend (Form) benutzt
							$fAmount += $aItem['amount'] / 100 * (float)$aAmount['amount'];
						}
					}

				} else {
					throw new RuntimeException('Unknown amount setting: '.$aAmount['setting']);
				}

			}

		}

		$oRow = new Ext_TS_Document_PaymentCondition_Row();
		$oRow->sType = 'deposit';
		$oRow->dDate = $this->getDueDate($oSetting, $this->dDocumentDate);
		$oRow->fAmount = $fAmount;
		$oRow->sLabel = $this->getLabel('deposit');
		$oRow->iSettingId = $oSetting->id;

		$aSettingsData = [];
		foreach($oSetting->amounts as $aAmount) {
			$aSettingsData[] = [
				'setting' => $aAmount['setting'],
				'type' => $aAmount['type'],
				'type_id' => (int)$aAmount['type_id'], // === im JS
				'amount' => (float)$aAmount['amount'],
			];
		}

		$oRow->aSettingData = $aSettingsData;

		return $oRow;

	}

	/**
	 * Zeilen für Ratenzahlung generieren
	 *
	 * @param Ext_TS_Payment_Condition_Setting $oSetting
	 * @param int $fAmountOpen
	 * @return Ext_TS_Document_PaymentCondition_Row[]
	 */
	private function getInstallmentPaymentRows(Ext_TS_Payment_Condition_Setting $oSetting, array $aItemsWithPeriods, float $fAmountOpen, float $fAmountWithoutPeriods = 0, $fAmountAdditionalFees = 0) {

		$aRows = [];
		$oInquiryPeriod = $this->getInstallmentServicePeriod();

		$aPeriods = $this->getInstallmentPaymentPeriods($oSetting, $oInquiryPeriod);

		// iTotalDays können auch Monate sein
		$iTotalDays = $iTotalDays2 = 0;
		$dLastUntil = null;

		if($oSetting->installment_split === 'service_period') {
			$iTotalDays = $oInquiryPeriod->from->diff($oInquiryPeriod->until)->days;
		} elseif($oSetting->installment_split === 'quarterly_month') {
			$iTotalDays = Ext_TS_Inquiry_Journey_Service::getMonthCount($oInquiryPeriod);
		}
		
		// Damit Datumsangaben wieder korrekt befüllt werden, müssen die Anzahlungszeilen übersprungen werden
		$iKeyShift = 0;
		if(!empty($this->aPaymentTerms)) {
			$iKeyShift = count(array_filter($this->aPaymentTerms, function(Ext_TS_Document_Version_PaymentTerm $oPaymentTerm) {
				return $oPaymentTerm->type === 'deposit';
			}));
		}

		if($oSetting->additional_fees_in_first_installment) {
			$fAmountOpen -= $fAmountAdditionalFees;
			$fAmountWithoutPeriods -= $fAmountAdditionalFees;
		}

		foreach($aPeriods as $iKey => $oDateRange) {

			$bLast = $iKey === count($aPeriods) - 1;

			// Eventuell wird der Betrag direkt ausgerechnet und nicht per Faktor bestimmt
			$fAmountPeriod = null;
			
			// Aufteilung nach Leistungszeitraum
			if($oSetting->installment_split === 'service_period') {

				if($dLastUntil === null) {
					$dLastUntil = $oDateRange->from;
				}

				$iDays = $dLastUntil->diff($oDateRange->until)->days;

				$fPercent = $iDays / $iTotalDays;

				$iTotalDays2 += $iDays;
				$dLastUntil = $oDateRange->until;

			} elseif($oSetting->installment_split === 'quarterly_month') {
				
				if(!empty($aItemsWithPeriods)) {
					$fAmountPeriod = 0;
					foreach($aItemsWithPeriods as $aItem) {
						foreach($aItem['additional_info']['periods'] as $sPeriodDate=>$fPeriodAmount) {
							$oPeriodDate = new \Carbon\Carbon($sPeriodDate);
							if($oPeriodDate->betweenIncluded($oDateRange->from, $oDateRange->until)) {
								if($aItem['type'] === 'special') {
									$fAmountPeriod -= $fPeriodAmount;
								} else {
									$fAmountPeriod += $fPeriodAmount;
								}
							}
						}
					}
				}
				
				$fMonth = Ext_TS_Inquiry_Journey_Service::getMonthCount($oDateRange);

				$fPercent = $fMonth / $iTotalDays;

				$iTotalDays2 += $fMonth;
				$dLastUntil = $oDateRange->until;
				
			} else { // percentage oder wöchentlich

				$fPercent = 1 / count($aPeriods);

			}

			$fAmount = 0;
			if($this->bCalculateAmounts) {

				if($fAmountPeriod !== null) {
					$fAmount += $fAmountPeriod;
					$fAmount += $fAmountWithoutPeriods * $fPercent;
				} else {
					$fAmount += $fAmountOpen * $fPercent;
				}

			}

			if($oSetting->due_type === 'start_of_month') {
				$dDate = clone $oDateRange->from;
				if($iKey !== 0) {
					$dDate->modify('first day of this month');
				}
			} elseif($oSetting->due_type === 'begin') {
				$dDate = clone $oDateRange->from;
			} else {
				$dDate = clone $oDateRange->until;			
			}

			if(
				$iKey == 0 &&
				$oSetting->additional_fees_in_first_installment
			) {
				$fAmount += $fAmountAdditionalFees;
			}
			
			$oRow = new Ext_TS_Document_PaymentCondition_Row();
			$oRow->sType = !$bLast ? 'installment' : 'final'; // Letzte Zeile MUSS final sein
			$oRow->dDate = $this->getDueDate($oSetting, $dDate, $iKey + $iKeyShift);
			$oRow->fAmount = $fAmount;
			$oRow->sLabel = $this->getLabel(!$bLast ? 'installment' : 'final');
			$oRow->iSettingId = $oSetting->id;
			$oRow->aSettingData = [
				'percent' => $fPercent,
				'from' => $oDateRange->from->format('Y-m-d'), // Debug
				'until' => $oDateRange->until->format('Y-m-d') // Debug
			];

			$aRows[] = $oRow;

		}

		if(
			$iTotalDays !== $iTotalDays2 &&
			(
				$oSetting->installment_split === 'service_period' ||
				$oSetting->installment_split === 'quarterly_month'
			)
		) {
			throw new LogicException('Payment condition error: $iTotalDays !== $iTotalDays2 ('.$iTotalDays.' !== '.$iTotalDays2.')');
		}

		return $aRows;
	}

	/**
	 * Perioden (= Rows) für Ratenzahlung ermitteln
	 *
	 * @param Ext_TS_Payment_Condition_Setting $oSetting
	 * @param \Core\DTO\DateRange $oInquiryPeriod
	 * @return array|\Core\DTO\DateRange[]
	 */
	public function getInstallmentPaymentPeriods(Ext_TS_Payment_Condition_Setting $oSetting, Core\DTO\DateRange $oInquiryPeriod) {

		if($oSetting->installment_type === 'fixed_number') {

			if ($oSetting->installment_split === 'monthly') {
				$now = Carbon\Carbon::now();
				$from = $now->getTimestamp();
				$until = $now->addMonths($oSetting->installment_charging)->getTimestamp();
			} else {
				$from = $oInquiryPeriod->from->getTimestamp();
				$until = $oInquiryPeriod->until->getTimestamp();
			}
			$fSecondsPerPeriod = floor(($until - $from) / $oSetting->installment_charging);

			$oFrom = Carbon\Carbon::instance($oInquiryPeriod->from);

			$aPeriods = [];
			for($i=0; $i<$oSetting->installment_charging; $i++) {
				$oUntil = $oFrom->clone();
				$oUntil->addSeconds($fSecondsPerPeriod);
				$aPeriods[] = new \Core\DTO\DateRange($oFrom, $oUntil);
				$oFrom = $oUntil;
			}
			
			return $aPeriods;
		}
		
		// Perioden monatlich oder wöchentlich
		if($oSetting->installment_type === 'weekly') {
			$aPeriods = Core\Helper\DateTime::getWeekPeriods($oInquiryPeriod->from, $oInquiryPeriod->until, false);
		} else { // monthly
			$aPeriods = Core\Helper\DateTime::getMonthPeriods($oInquiryPeriod->from, $oInquiryPeriod->until, false);
		}
				
		// Alle X Monate/Wochen anwenden
		if($oSetting->installment_charging > 1) {

			$aNewPeriods = [];

			// Anzahl zu generierender Rows
			$iRows = ceil(count($aPeriods) / $oSetting->installment_charging);

			for($iRow = 0; $iRow < $iRows; $iRow++) {

				$iKey = $iRow * $oSetting->installment_charging;
				$iKey2 = $iKey + $oSetting->installment_charging - 1;

				$dFrom = $aPeriods[$iKey]->from;

				if(isset($aPeriods[$iKey2]->until)) {
					$dUntil = $aPeriods[$iKey2]->until;
				} else {
					// Wenn nächste Periode nicht existiert: Letzte auf das aktuelle Enddatum setzen
					// Bspw. 3 Monate eingestellt, aber nur 2 Monate vorhanden
					$dUntil = $aPeriods[count($aPeriods) - 1]->until;
				}

				$newPeriod = new \Core\DTO\DateRange($dFrom, $dUntil);
				
				if(
					$aPeriods[$iKey]->partial || 
					$aPeriods[$iKey2]->partial
				) {
					$newPeriod->partial = true;
				}
				
				$aNewPeriods[] = $newPeriod;

			}

			$aPeriods = $aNewPeriods;

		}

		// Perioden zusammenführen am Anfang und Ende, falls optional definiert
		$aTmp = $aPeriods;
		$aPeriods = [];
		$bAddFirstToSecond = false;
		$oLatestDateRange = null;

		foreach($aTmp as $iKey => $oDateRange) {

			$bLast = $iKey === count($aTmp) - 1;

			if($bAddFirstToSecond === true) {
				$oDateRange->from = $oLatestDateRange->from;
				$oDateRange->partial = true;
			}

			// Optional erste Teilrate und zweite Rate zusammenfassen
			if(
				$oSetting->installment_split === 'quarterly_month' &&
				$oSetting->combine_partial_instalments == 1 &&
				$iKey === 0 &&
				$oDateRange->partial === true
			) {
				
				$bAddFirstToSecond = true;
				
			} elseif(
				$oSetting->installment_split === 'quarterly_month' &&
				$oSetting->combine_last_partial_instalments == 1 &&
				$bLast === true &&
				$oDateRange->partial === true
			) {
				
				$oLatestDateRange->until = $oDateRange->until;
				$oLatestDateRange->partial = true;

			} else {
				
				$aPeriods[] = $oDateRange;
				$bAddFirstToSecond = false;
				
			}

			$oLatestDateRange = $oDateRange;
			
		}
		
		return $aPeriods;

	}

	/**
	 * Leistungszeitaum für Ratenzahlung oder Teilrechnung
	 *
	 * @return Core\DTO\DateRange
	 */
	public function getInstallmentServicePeriod() {

		$oInquiryPeriod = $this->oInquiry->getCompleteServiceTimeframe();

		if($oInquiryPeriod === null) {
			throw new RuntimeException('Inquiry service period is null');
		}

		$oInquiryPeriod->end->setTime(23, 59, 59); // Wichtig für Date-Diff

		return new Core\DTO\DateRange($oInquiryPeriod->start, $oInquiryPeriod->end);

	}

	/**
	 * @param Ext_TS_Payment_Condition_Setting|null $oSetting
	 * @param $fAmountOpen
	 * @return Ext_TS_Document_PaymentCondition_Row
	 */
	private function getFinalPaymentRow(Ext_TS_Payment_Condition_Setting $oSetting = null, $fAmountOpen = 0) {

		if($oSetting === null) {
			$oSetting = new Ext_TS_Payment_Condition_Setting();
			$oSetting->due_days = 0;
			$oSetting->due_direction = 'before';
			$oSetting->due_type = 'document_date';
		}

		$oRow = new Ext_TS_Document_PaymentCondition_Row();
		$oRow->sType = 'final';
		$oRow->dDate = $this->getDueDate($oSetting, $this->dDocumentDate);
		$oRow->fAmount = $this->bCalculateAmounts ? $fAmountOpen : 0; // Immer Restbetrag
		$oRow->sLabel = $this->getLabel('final').' *';
		$oRow->iSettingId = $oSetting ? $oSetting->id : 0;

		return $oRow;

	}

	/**
	 * @param Ext_TS_Payment_Condition_Setting $oSetting
	 * @param DateTime $dInputDate
	 * @param int $iIndex Nummerischen Index mitgeben, da bei Ratenzahlung die Zeilen unterschieden werden müssen
	 * @return DateTime
	 * @throws Exception
	 */
	private function getDueDate(Ext_TS_Payment_Condition_Setting $oSetting, DateTime $dInputDate, $iIndex = null) {

		// Terms vorhanden und Zahlungsbedingung gesetzt: Datumsangaben übernehmen
		if(
			$this->aPaymentTerms !== null &&
			!empty($this->oPaymentCondition)
		) {
			foreach($this->aPaymentTerms as $iKey => $oPaymentTerm) {
				if(
					$oSetting->id == $oPaymentTerm->setting_id && (
						// Benötigt für Ratenzahlungen, da es hier mehrere Zeilen mit gleicher setting_id gibt
						// Wenn das so nicht funktioniert, muss ein Feld position eingebaut werden
						$iIndex === null ||
						$iIndex === $iKey
					)
				) {
					return new DateTime($oPaymentTerm->date);
				}
			}
		}

		return $this->calculateDueDate($oSetting, $dInputDate);

	}

	/**
	 * @param Ext_TS_Payment_Condition_Setting $oSetting
	 * @param DateTime $dInputDate
	 * @return DateTime
	 * @throws Exception
	 */
	private function calculateDueDate(Ext_TS_Payment_Condition_Setting $oSetting, DateTime $dInputDate) {

		if(
			$oSetting->due_type === 'course_start_date' ||
			$oSetting->due_type === 'course_start_date_month_end'
		) {

			// Fallback gab es so schon in den alten Zahlungsbedingungen
			$sFirstStart = $this->oInquiry->getFirstCourseStart(false);
			if($sFirstStart === null) {
				$sFirstStart = $this->oInquiry->getFirstAccommodationStart(false);
			}

			if(\Core\Helper\DateTime::isDate($sFirstStart, 'Y-m-d')) {
				$dDate = new DateTime($sFirstStart);
			} else {
				// Sollte eigentlich nicht vorkommen
				$dDate = new DateTime();
			}

			if($oSetting->due_type === 'course_start_date_month_end') {
				$dDate->modify('last day of this month');
			}

		}
//		elseif(
//			// Ratenzahlung
//			$oSetting->due_type === 'begin' ||
//			$oSetting->due_type === 'end'
//		) {
//			$dDate = clone $dInputDate;
//			if($oSetting->due_type === 'begin') {
//				$dDate->modify($oSetting->installment_type === 'weekly' ? 'monday this week' : 'first day of this month');
//			} else {
//				$dDate->modify($oSetting->installment_type === 'weekly' ? 'sunday this week' : 'last day of this month');
//			}
//		}
		else {
			// Rechnungsdatum
			$dDate = clone $dInputDate;
		}

		$oDateInterval = new DateInterval('P'.(int)$oSetting->due_days.'D');
		if($oSetting->due_direction === 'after') {
			$dDate->add($oDateInterval);
		} else {
			$dDate->sub($oDateInterval);
		}

//		// Datum darf nicht vor Rechnungsdatum liegen
//		// Bei Teilrechnung wird nach Zeiträumen abgerechnet, daher nicht beachten
//		if(
//			$dDate < $this->dDocumentDate &&
//			!$this->bPartialInvoice
//		) {
//			$dDate = clone $dInputDate;
//		}

		return $dDate;

	}

	/**
	 * Datumsangaben so korrigieren, dass diese chronologisch sind (sonst gibt es eine Fehlermeldung)
	 *
	 * @param Ext_TS_Document_PaymentCondition_Row[] $aRows
	 */
	private function adjustPaymentRowsDueDates(array $aRows) {

		// Bei Teilrechnung wird nach Zeiträumen abgerechnet, daher nicht beachten
		if($this->bPartialInvoice) {
			return;
		}

		$dDueDate = null;
		foreach(array_reverse($aRows) as $oRow) {

			// Datum darf nicht vor Rechnungsdatum liegen
			if($oRow->dDate < $this->dDocumentDate) {
				$oRow->dDate = clone $this->dDocumentDate;
			}

			if($dDueDate === null) {
				$dDueDate = $oRow->dDate;
			}

			// Datum darf nicht nach Folgedatum (Restzahlung) liegen
			if($oRow->dDate > $dDueDate) {
				$oRow->dDate = clone $dDueDate;
			} else {
				// Ansonsten Datum setzen für weitere Anzahlungen
				$dDueDate = $oRow->dDate;
			}

		}

	}

	/**
	 * @param string $sType
	 * @return string
	 */
	public function getLabel($sType) {
		switch($sType) {
			case 'deposit':
				return L10N::t('Anzahlung', Ext_Thebing_Document::$sL10NDescription);
			case 'final':
				return L10N::t('Restzahlung', Ext_Thebing_Document::$sL10NDescription);
			case 'installment':
				return L10N::t('Ratenzahlung', Ext_Thebing_Document::$sL10NDescription);
			default:
				throw new InvalidArgumentException('Invalid Type: '.$sType);
		}
	}

	/**
	 * Params aus dem Dokumentendialog zu Objekten umwandeln (keine Validierung)
	 *
	 * Optional $oVersion übergeben, damit die Terms direkt als Child gesetzt werden.
	 * Die WDBasic scheint keine Methode anzubieten, um ein Kind nachträglich zu setzen…
	 *
	 * @param MVC_Request $oRequest
	 * @param Ext_Thebing_Inquiry_Document_Version|null $oVersion
	 * @return array|null
	 */
	public static function convertRequestPaymentTerms(MVC_Request $oRequest, Ext_Thebing_Inquiry_Document_Version $oVersion = null) {

		$aPaymentTerms = [];
		$oDateFormat = new Ext_Thebing_Gui2_Format_Date();
		$aData = $oRequest->input('paymentterm', []);

		foreach((array)$aData['type'] as $iKey => $sType) {

			if($oVersion !== null) {
				$oPaymentTerm = $oVersion->getJoinedObjectChild('paymentterms');

				if($oVersion->payment_condition_id) {
					// Nur setzen wenn Zahlungsbedingung auch vorhanden
					$oPaymentTerm->setting_id = $aData['setting_id'][$iKey];
				}
			} else {
				$oPaymentTerm = new Ext_TS_Document_Version_PaymentTerm();
			}

			$oPaymentTerm->type = $sType;
			$oPaymentTerm->date = $oDateFormat->convert($aData['date'][$iKey]);
			$oPaymentTerm->amount = Ext_Thebing_Format::convertFloat($aData['amount'][$iKey]);

			// Leere Zeilen ignorieren
			if(
				$sType !== 'final' &&
				round($oPaymentTerm->amount, 2) == 0
			) {
				continue;
			}

			$aPaymentTerms[] = $oPaymentTerm;

		}

		return $aPaymentTerms;

	}

}
