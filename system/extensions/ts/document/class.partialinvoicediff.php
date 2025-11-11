<?php

class Ext_TS_Document_PartialInvoiceDiff {

	/**
	 * @var Ext_TS_Inquiry_Abstract
	 */
	private $oInquiry;

	/**
	 * @var array
	 */
	private $aItems;

	/**
	 * @var array
	 */
	private $aCompareVersionItems = [];

	/**
	 * 
	 * @var Ext_Thebing_Inquiry_Document_Version[]
	 */
	private array $compareVersions = [];
	
	/**
	 * @var Ext_TS_Document_PaymentCondition
	 */
	private $oPaymentConditionService;

	/**
	 * @var bool
	 */
	private $bInvoiceCompleteRemainder = false;

	/**
	 * 
	 * @var bool
	 */
	private $billingPeriodFromPartialInvoiceList = false;
	
	/**
	 * @var Core\DTO\DateRange
	 */
	private $oBillingPeriod;
	
	/**
	 * @var array
	 */
	private $aBillingPeriods = [];

	/**
	 * @var Ext_Thebing_Inquiry_Document_Version
	 */
	private $oVersion;

	/**
	 * @var Ext_Thebing_Inquiry_Document_Version[]
	 */
	private $aCompareVersions = [];

	public function __construct(Ext_TS_Inquiry_Abstract $oInquiry, array $aItems, array $aCompareVersions, Ext_Thebing_Inquiry_Document_Version $oVersion) {
		$this->oInquiry = $oInquiry;
		$this->aItems = $aItems;
		$this->oVersion = $oVersion;
		$this->aCompareVersions = $aCompareVersions;
		$this->prepareVersions($aCompareVersions);
	}

	/**
	 * @param array $aVersions
	 * @throws Exception
	 */
	private function prepareVersions(array $aVersions) {

		foreach($aVersions as $oVersion) {
			$this->compareVersions[$oVersion->id] = $oVersion;
			/** @var Ext_Thebing_Inquiry_Document_Version_Item[] $aVersionItems */
			$aVersionItems = $oVersion->getJoinedObjectChilds('items');
			foreach($aVersionItems as $oItem) {
				if($oItem->onPdf) {
					$aItem = $oItem->getData();
					$aItem['additional_info'] = json_decode($aItem['additional_info'], true);
					$aItem['diff_key'] = $this->createDiffKey($aItem);
					$this->aCompareVersionItems[$oVersion->id][] = $aItem;
				}
			}
		}

	}

	/**
	 * @param Ext_TS_Document_PaymentCondition $oPaymentConditionService
	 */
	public function setPaymentConditionService(Ext_TS_Document_PaymentCondition $oPaymentConditionService) {
		$this->oPaymentConditionService = $oPaymentConditionService;
	}

	/**
	 * Kompletten Restzeitraum abrechnen (Teilrechnung vorhanden, aber Checkbox nicht ausgewählt)
	 */
	public function setInvoicingCompleteRemainder() {
		$this->bInvoiceCompleteRemainder = true;
	}

	protected function getManualItem(Ext_TS_Document_PaymentCondition_Row $deposit, $interimPayment=false) {

		if(!empty($this->oVersion->sLanguage)) {
			$language = $this->oVersion->sLanguage;
		} else {
			$language = $this->oVersion->template_language;
		}

		$frontendTranslation = new Tc\Service\Language\Frontend($language);

		$itemKey = Ext_Thebing_Util::generateRandomString(16);
		
		$item = [
			'item_key' => $itemKey,
            'amount' => $deposit->fAmount,
            'amount_net' => $deposit->fAmount,
            'amount_provision' => 0,
            'amount_discount' => 0,
            'type' => 'deposit',
            'type_id' => $deposit->iSettingId,
            'from' => $deposit->dDate->format('Y-m-d'),
            'until' => $deposit->dDate->format('Y-m-d'),
            'initalcost' => 0,
            'onPdf' => 1,
            'calculate' => 1,
            'tax_category' => 0,
            'additional_info' => [
				'item_key' => $itemKey,
				'billing_type' => 'once',
				'billing_units' => 1,
            ]
        ];

		if($interimPayment === false) {
			$item['description'] = $frontendTranslation->translate('Anzahlung');
		} else {
			$item['description'] = $frontendTranslation->translate('Zwischenzahlung');	
		}
		
		return $item;
	}

	/**
	 * @return array
	 */
	public function diffItems() : array {

		$oPaymentCondition = $this->oPaymentConditionService->getPaymentCondition();
		if(!$oPaymentCondition->isEligibleForPartialInvoice()) {
			throw new RuntimeException('Invalid payment condition for partial invoice!');
		}

		// Nächste Teilrechnung ermitteln
		$nextPartialInvoice = Ts\Entity\Inquiry\PartialInvoice::getRepository()->getNext($this->oInquiry);

		$oSetting = $nextPartialInvoice->getSetting();

		/** @var Ext_TS_Payment_Condition_Setting $oSetting */
		$oFirstSetting = reset($oPaymentCondition->getSettings());
		
		$oBillingPeriod = $this->getBillingPeriod($oSetting, $nextPartialInvoice);

		$aItems = [];
		$depositCreditItem = null;
		
		// Wenn Anzahlungsrechnung und Zahlungsbedingung mit Anzahlung
		if(
			$oSetting->type == 'deposit' ||
			$oSetting->type == 'interim'
		) {
			
			$deposit = $nextPartialInvoice->getRow();
			
			if($oSetting->type == 'interim') {
				$depositItem = $this->getManualItem($deposit, true);
			} else {
				$depositItem = $this->getManualItem($deposit);
			}

			$aItems[] = $depositItem;

			// Das muss hier passieren, weil wir ja zuerst den Anzahlungbetrag errechnet wollen
			$this->oPaymentConditionService->setPartialInvoice();

			return $aItems;
			
		} else {
			
			// Alle manuellen Teilrechnungseinträge, seit der letzten richtigen Teilrechnung
			$manualInvoices = [];
			$convertedPartialInvoices = Ts\Entity\Inquiry\PartialInvoice::getRepository()->getConverted($this->oInquiry);
			foreach($convertedPartialInvoices as $convertedPartialInvoice) {
				if(
					$convertedPartialInvoice->type != 'deposit' &&
					$convertedPartialInvoice->type != 'interim'
				) {
					$manualInvoices = [];
				} else {
					$manualInvoices[] = $convertedPartialInvoice;
				}
			}

			// Gibt es Anzahlungen?
			$depositAmount = 0;
			$depositDate = null;
			foreach($this->aCompareVersionItems as $items) {
				foreach($items as $item) {
					if($item['type'] === 'deposit') {
						$depositAmount += $item['amount'];
						$depositDate = max(new DateTime($item['index_from']), $depositDate);
					} elseif($item['type'] === 'deposit_credit') {
						$depositAmount += $item['amount'];
					}
				}
			}

			if($depositAmount > 0) {

				$depositCredit = new Ext_TS_Document_PaymentCondition_Row();
				$depositCredit->sType = 'deposit';
				$depositCredit->dDate = $depositDate;
				$depositCredit->fAmount = $depositAmount * -1;
				
				$depositCreditItem = $this->getManualItem($depositCredit);
				
				$depositCreditItem['type'] .= '_credit';
				
			}
		
			// Weitere Rechnungen ganz normal
			
		}

		// Nur eine Restzahlung
		$this->oPaymentConditionService->setPartialInvoice();

		if($oSetting->due_type === 'start_of_month') {
			$dDocumentDate = clone $oBillingPeriod->from;
			$dDocumentDate->modify('first day of this month');
		} elseif($oSetting->due_type === 'begin') {
			$dDocumentDate = clone $oBillingPeriod->from;
		} else {
			$dDocumentDate = clone $oBillingPeriod->until;			
		}

		$this->oPaymentConditionService->setDocumentDate($dDocumentDate->format('Y-m-d'));

		if($oSetting->type === 'installment') {

			foreach($this->aItems as $aItem) {

				// Item prüfen, ob das schon abgerechnet werden darf
				if(
					!Core\Helper\DateTime::isDate($aItem['from'], 'Y-m-d') ||
					!Core\Helper\DateTime::isDate($aItem['until'], 'Y-m-d')
				) {
					throw new LogicException('No from/until date for item '.$aItem['type']);
				}

				$dItemFrom = new DateTime($aItem['from']);
				$dItemUntil = new DateTime($aItem['until']);

				if(
					!$this->bInvoiceCompleteRemainder &&
					$dItemFrom > $oBillingPeriod->until
				) {
					continue;
				}

				if(!isset($aItem['additional_info']['billing_units'])) {
					throw new LogicException('No billing_units set for item '.$aItem['type']);
				}

				$iChargingUnits = $oSetting->installment_charging;
				$iBillingUnits = $aItem['additional_info']['billing_units'];

				// Wie viel wurde bereits abgerechnet von diesem Item?
				$aBilled = $this->calculateBilledAmounts($aItem);
				$iBilledUnits = $aBilled[0];

				/*
				 * Für Click-Ratenzahlung (#16264), erstmal nur monatlich
				 */
				if(
					$this->billingPeriodFromPartialInvoiceList === true &&
					$iBilledUnits === 0
				) {

					if($oSetting->installment_type === 'monthly') {
						$dummyCourse = new \Ext_TS_Inquiry_Journey_Course();
						$dummyCourse->from = $dItemFrom->format('Y-m-d');

						$dummyFrom = clone $oBillingPeriod->from;
						$dummyFrom->modify('-1 day');

						$dummyCourse->until = $dummyFrom->format('Y-m-d');

						$iBilledUnits = \Ext_TS_Inquiry_Journey_Service::getMonthCount($dummyCourse);
					} else {
						// Statischer Wert erstmal, ist aber nicht korrekt. Die anderen Fälle müssten korrekt abgefangen werden.
						$iBilledUnits = 1;
					}

				}

				$aItem['additional_info']['billing_type'] = $aItem['additional_info']['billing_type'] ?? null;

				if(
					(
						$aItem['additional_info']['billing_type'] !== 'once' &&
						$iBillingUnits > $iBilledUnits
					) ||
					(
						$aItem['additional_info']['billing_type'] === 'once' &&
						empty($iBilledUnits)
					)
				) {

					// Bei monatlicher Abrechnung müssen wöchentliche Leistungen angepasst werden
					// Hier wird die Schnittmenge verrechnet und im Zweifel eine Woche mehr als zu wenig verrechnet
					if($oSetting->installment_type === 'monthly') {
						if($aItem['additional_info']['billing_type'] === 'week') {
							$iDays = \Core\Helper\DateTime::getDaysInPeriodIntersection($oBillingPeriod->from, $oBillingPeriod->until, $dItemFrom, $dItemUntil);
							$iChargingUnits = ceil($iDays / 7);
						} elseif($aItem['additional_info']['billing_type'] === 'month') {
							$iChargingUnits = \Ext_TS_Inquiry_Journey_Service::getMonthCount($oBillingPeriod);
						} elseif($aItem['additional_info']['billing_type'] === 'once') {
							$iChargingUnits = $iBillingUnits;
						}
					}

					// Wenn 1 Woche noch abgerechnet werden muss, dürfen nicht die vollen Wochen der Rate berechnet werden
					// Bspw. billing_units 5, billing_offset 4, installment_charging 4, settle 1 (nicht 4)
					$iBillingSettle = $iChargingUnits;
					$iBillingLeft = $iBillingUnits - $iBilledUnits;
					if(
						$this->bInvoiceCompleteRemainder ||
						$iChargingUnits > $iBillingLeft // Nicht mehr abrechnen als möglich
					) {
						$iBillingSettle = $iBillingLeft;
					}

					$this->calculateItemAmount($aItem, $iBillingUnits, $iBillingSettle, $aBilled, $oSetting);

					// Einmalige Kosten nicht in Zeitraum oder Text verändern, da sie ja nur auf einer Rechnung vorkommen
					if($aItem['additional_info']['billing_type'] !== 'once') {
						if($iBilledUnits > 0) {
							$aItem['from'] = $oBillingPeriod->from->format('Y-m-d');
						}
						$aItem['until'] = $oBillingPeriod->until->format('Y-m-d');
						$aItem['description'] .= ' '.$this->getBillingDescription($this->aBillingPeriods, $oBillingPeriod);
						$aItem['additional_info']['instalment'] = true;
					}

					$aItem['additional_info']['billing_offset'] = $iBillingSettle;

					$aItems[] = $aItem;

				}

			}
				
		} else {
			$aItems = $this->aItems;
		}
		
		if($depositCreditItem !== null) {
			$aItems = array_merge($aItems, [$depositCreditItem]);
		}
		
		return $aItems;
	}

	/**
	 * Wie viel wurde bereits abgerechnet von diesem Item?
	 *
	 * @param array $aItem
	 * @return array
	 */
	private function calculateBilledAmounts(array $aItem) : array {

		// Wie viele Einheiten wurden bereits abgerechnet?
		$iBilledUnits = 0;
		$fBilledAmountGross = 0;
		$fBilledAmountNet = 0;
		$aMatchingItems = $this->searchMatchingItemsForItem($aItem);

		foreach($aMatchingItems as $aMatchingItem) {

			if(!is_array($aMatchingItem['additional_info'])) {
				throw new LogicException('additional_info is not an array');
			}

			$iBilledUnits += $aMatchingItem['additional_info']['billing_offset'];
			$fBilledAmountGross += $aMatchingItem['amount'];
			$fBilledAmountNet += $aMatchingItem['amount_net'];

		}

		return [$iBilledUnits, $fBilledAmountGross, $fBilledAmountNet];

	}

	/**
	 * PROZENTUALE Berechnung des abzurechnenden Betrags
	 *
	 * @param array $aItem
	 * @param float $iBillingUnits
	 * @param float $iBillingSettle
	 * @param array $aBilled
	 */
	private function calculateItemAmount(array &$aItem, float $iBillingUnits, float $iBillingSettle, array $aBilled, Ext_TS_Payment_Condition_Setting $oSetting) {
		
		list($iBilledUnits, $fBilledAmountGross, $fBilledAmountNet) = $aBilled;

		if(
			$oSetting->installment_split === 'percentage' &&
			$aItem['additional_info']['billing_type'] !== 'once'
		) {
			
			$fPercent = 1 / count($this->aBillingPeriods);
			
			$aItem['amount'] = $aItem['amount'] * $fPercent;
			$aItem['amount_net'] = $aItem['amount_net'] * $fPercent;
			
		} else {

			// Differenz ausrechnen zwischen erwarteten abgerechnetem und tatsächlich abgerechnetem Wert
			// Bspw. wird Kurs verlängert, bekommt einen günstigeren Preis und jetzt muss die Differenz abgezogen werden
			$fAmountExpectedGross = $aItem['amount'] / $iBillingUnits * $iBilledUnits;
			$fAmountExpectedNet = $aItem['amount_net'] / $iBillingUnits * $iBilledUnits;
			$fAmountDifferenceGross = $fAmountExpectedGross - $fBilledAmountGross;
			$fAmountDifferenceNet = $fAmountExpectedNet - $fBilledAmountNet;

			$aItem['amount'] = $aItem['amount'] / $iBillingUnits * $iBillingSettle + $fAmountDifferenceGross;
			$aItem['amount_net'] = $aItem['amount_net'] / $iBillingUnits * $iBillingSettle + $fAmountDifferenceNet;

		}
		
	}

	/**
	 * @param int $iBillingUnits
	 * @param int $iBillingSettle
	 * @param int $iBilledUnits
	 * @return string
	 */
	private function getBillingDescription(array $billingPeriods, Core\DTO\DateRange $billingPeriod) : string {

		$billingFrom = $billingTo = null;
		foreach($billingPeriods as $periodNumber=>$period) {
			if($billingFrom === null && $period->from >= $billingPeriod->from) {
				$billingFrom = $periodNumber+1;
			}
			if(
				$period->from >= $billingPeriod->from &&
				$period->until <= $billingPeriod->until
			) {
				$billingTo = $periodNumber+1;
			}
		}

		$billing = $billingFrom.'-'.$billingTo;

		if($billingFrom === $billingTo) {
			$billing = $billingFrom;
		}

		return sprintf('(%s/%s)', $billing, count($billingPeriods));

	}

	/**
	 * @return Core\DTO\DateRange
	 */
	public function getBillingPeriod(Ext_TS_Payment_Condition_Setting $oSetting, \Ts\Entity\Inquiry\PartialInvoice $nextPartialInvoice = null) : Core\DTO\DateRange {

		if($this->oBillingPeriod !== null) {
			return $this->oBillingPeriod;
		}

		#$oSetting = reset($this->oPaymentConditionService->getPaymentCondition()->getSettings());
		$oInquiryPeriod = $this->oPaymentConditionService->getInstallmentServicePeriod();

		// Zeitpunkt, zu dem bisher abgerechnet wurde
		$dBillingUntil = null;

		$latestConvertedPartialInvoice = Ts\Entity\Inquiry\PartialInvoice::query()
			->where('inquiry_id', $this->oInquiry->id)
			->whereNotNull('converted')
			->orderBy('until', 'desc')
			->first();
		
		if($latestConvertedPartialInvoice !== null) {
			$dBillingUntil = new DateTime($latestConvertedPartialInvoice->until);
		}
		
//		foreach($this->compareVersions as $compareVersion) {
//			foreach($aCompareVersionItems as $aCompareVersionItem) {
//				// Einmalige Kosten aus erster Rate natürlich nicht berücksichtigen
//				if(
//					$aCompareVersionItem['type'] != 'deposit' &&
//					!empty($aCompareVersionItem['index_until']) &&
//					$aCompareVersionItem['additional_info']['billing_type'] != 'once'
//				) {
//					if($dBillingUntil === null) {
//						$dBillingUntil = new DateTime($aCompareVersionItem['index_until']);
//					}
//					$dBillingUntil = max($dBillingUntil, new DateTime($aCompareVersionItem['index_until']));
//				}
//			}
//		}

		// Wenn ein Eintrag übergeben wird, dann daher den folgenden nehmen.
		if($nextPartialInvoice) {
			$nextPartialInvoiceFrom = new DateTime($nextPartialInvoice->from);
			$nextPartialInvoiceFrom->modify('-1 sec');
			
			if(
				$dBillingUntil === null ||
				$nextPartialInvoiceFrom > $dBillingUntil
			) {
				$dBillingUntil = $nextPartialInvoiceFrom;
			}

		}
		
		// Es wurden keine Items mit Leistungsberechnung gefunden
		if($dBillingUntil === null) {
			
			// Wurde bereits eine Teilrechnung gestellt? (Nicht Anzahlung)
			$billingUntil = DB::getQueryOne(
				"SELECT until FROM `ts_inquiries_partial_invoices` WHERE `inquiry_id` = :inquiry_id AND `type` != 'deposit' AND `converted` IS NOT NULL ORDER BY `until` DESC LIMIT 1", 
				[
					'inquiry_id'=>$this->oInquiry->id
				]
			);
			
			if(!empty($billingUntil)) {
				
				$dBillingUntil = new DateTime($billingUntil);
				
				$this->billingPeriodFromPartialInvoiceList = true;
				
			}
			
		}

		// Wenn alles abgerechnet werden soll: Letztes Abrechnungsdatum bis Leistungsende
		if($this->bInvoiceCompleteRemainder) {
			if($dBillingUntil === null) {
				// $dBillingUntil === null sollte hier nicht vorkommen, da bInvoiceCompleteRemainder bei der ersten Rechnung nicht gesetzt sein kann
				throw new LogicException('$dBillingUntil is null for billing of complete remainder');
			}
			$dUntil = $oInquiryPeriod->until;
			if($dBillingUntil > $dUntil) {
				// Wenn Leistungszeitraum verkürzt wurde
				$dUntil = $dBillingUntil;
			}
			$this->oBillingPeriod = new Core\DTO\DateRange($dBillingUntil, $dUntil);

			return $this->oBillingPeriod;
		}

		$this->aBillingPeriods = $this->oPaymentConditionService->getInstallmentPaymentPeriods($oSetting, $oInquiryPeriod);

		if($dBillingUntil === null) {
			$this->oBillingPeriod = reset($this->aBillingPeriods);
		} else {
			foreach($this->aBillingPeriods as $oPeriod) {
				if($oPeriod->from > $dBillingUntil) {
					$this->oBillingPeriod = $oPeriod;
					break;
				}
			}
		}

		if($this->oBillingPeriod === null) {
			// Hier sollte eigentlich ein Fehler kommen, aber das lässt sich nicht anzeigen
			if($dBillingUntil !== null) {
				$this->oBillingPeriod = new Core\DTO\DateRange($dBillingUntil, $dBillingUntil);
			} else {
				throw new LogicException('No billing period found');
			}
		}

		return $this->oBillingPeriod;
	}

	/**
	 * @param array $aItem
	 * @return array
	 */
	private function searchMatchingItemsForItem(array $aItem) : array {

		$aItems = [];
		$sDiffKey = $this->createDiffKey($aItem);

		foreach($this->aCompareVersionItems as $aCompareVersionItems) {
			foreach($aCompareVersionItems as $aCompareVersionItem) {
				if($sDiffKey === $aCompareVersionItem['diff_key']) {
					$aItems[] = $aCompareVersionItem;
				}
			}
		}

		return $aItems;

	}

	/**
	 * @param array $aItem
	 * @return string
	 */
	private function createDiffKey(array $aItem) : string {

		$sKey = $aItem['type'];

		if(
			$aItem['type'] === 'additional_course' ||
			$aItem['type'] === 'additional_accommodation'
		) {
			// journey_course_id, journey_accommodation_id
			$sKey .= '_'.(int)$aItem['parent_booking_id'];
		} else {
			$sKey .= '_'.(int)$aItem['type_id'];
		}

		if(
			$aItem['type'] === 'additional_course' ||
			$aItem['type'] === 'additional_accommodation'
		) {
			// fee_id
			$sKey .= '_'.(int)$aItem['type_id'];
		} else {
			// course_id, accommodation_id, etc.
			$sKey .= '_'.(int)$aItem['type_object_id'];
		}

		return $sKey;

	}

}
