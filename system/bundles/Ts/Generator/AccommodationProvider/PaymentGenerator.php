<?php

namespace Ts\Generator\AccommodationProvider;

use Ext_Thebing_Accommodation_Allocation as Allocation;
use Ts\Entity\AccommodationProvider\Payment\Category;
use Ts\Entity\AccommodationProvider\Payment;
use Ts\Entity\AccommodationProvider\Payment\Category\Period;
use Ts\Helper\Accommodation\AllocationCombination;
use Core\Helper\DateTime;
use \Carbon\Carbon;

class PaymentGenerator {
	
	/**
	 * @var array
	 */
	protected $aCache = array();

	/**
	 * @var \Ts\Helper\Accommodation\AllocationCombination
	 */
	protected $oAccommodationAllocationCombination;
	
	/**
	 * @var \Ext_Thebing_Accommodation
	 */
	protected $oAccommodationProvider;
	
	/**
	 * @var \Ts\Entity\AccommodationProvider\Payment\Category 
	 */
	protected $oPaymentCategory;
	
	/**
	 * @var \Ext_Thebing_Accommodation_Cost_Category 
	 */
	protected $oCostCategory;
	
	/**
	 * @var \DateTime
	 */
	protected $firstPaymentStart;
	
	/**
	 * @var \Ext_Thebing_School 
	 */
	protected $oSchool;
	
	/**
	 * @var array
	 */
	protected $aMessages;
	
	/**
	 * 
	 * @param \Ext_Thebing_Accommodation_Allocation $oAccommodationAllocation
	 */
	public function __construct(AllocationCombination $oAccommodationAllocation) {
		$this->oAccommodationAllocationCombination = $oAccommodationAllocation;
		$this->oAccommodationProvider = $this->oAccommodationAllocationCombination->getAccommodationProvider();
		$this->oSchool = $this->oAccommodationAllocationCombination->getInquiry()->getSchool();
	}
	
	/**
	 * @return null|false|integer
	 */
	public function run() {

		$iEntries = 0;

		// Nicht einem Anbieter zugewiesene Allocation überspringen (entstehen nach Zerschneidung z.B.)
		if(!$this->oAccommodationProvider instanceof \Ext_Thebing_Accommodation) {
			$this->addMessage('Zuweisung ohne Unterkunftsanbieter');
			return;
		}

		/*
		 * Es ist theoretisch möglich das über die Inquiry keine Schule gefunden werden kann, allerdings nutzt
		 * die Inquiry dann einfach Ext_Thebing_School::getSchoolFromSession(), was bei All-Schools eine leere
		 * Schule ergeben würde. Zur Sicherheit wird das hier abgefangen, auch wenn es "eigentlich" gar nicht
		 * passieren kann ... (glaube ich ...) [aber man weiß ja nie ...] {vielleicht doch die Glaskugel fragen ...}
		 */
		if(!$this->oSchool->exist()) {
			$sMsg = 'No school available';
			throw new \RuntimeException($sMsg);
		}

		$dAllocationFrom = new DateTime($this->oAccommodationAllocationCombination->from);
		
		/* @var $oCategoryRepo \Ts\Entity\AccommodationProvider\Payment\CategoryRepository */
		$oCategoryRepo = Category::getRepository();

		$this->oPaymentCategory = $oCategoryRepo->findByProvider($this->oAccommodationProvider, $dAllocationFrom);

		if(
			empty($this->oPaymentCategory) ||
			(
				$this->oPaymentCategory instanceof Category &&
				$this->oPaymentCategory->active == 0
			)
		) {
			$this->saveError(Payment::ERROR_PAYMENT_CATEGORY_NOT_FOUND);
			$this->addMessage('Keine Abrechnungskategorie gefunden');
			return false;
		}

		$this->initializeCostCategory($dAllocationFrom);

		if(
			empty($this->oCostCategory) ||
			(
				$this->oCostCategory instanceof \Ext_Thebing_Accommodation_Cost_Category &&
				$this->oCostCategory->active == 0
			)
		) {
			$this->saveError(Payment::ERROR_PAYMENT_COST_CATEGORY_NOT_FOUND);
			$this->addMessage('Keine Kostenkategorie gefunden');
			return false;
		}

		/*
		 * Bei der Zuweisung des Unterkunftsanbieters mit dem Kostenkategorie-Typen "Nicht berechnen"
		 * wird einfach nur der Wert 'payment_generation_completed' gesetzt. Bezahl-Datensätze
		 * dürfen hier nicht generiert werden. Außerdem muss das Kostenkategorie-Validity-Datum
		 * vor der Unterkunftszuweisung sein.
		 */
		$aSalary = $this->oAccommodationProvider->getSalary($dAllocationFrom->format('Y-m-d'));
		$dSalaryValidityFrom = new DateTime($aSalary['valid_from']);

		if(
			$this->oCostCategory->cost_type == 'non_calculate' &&
			$dSalaryValidityFrom <= $dAllocationFrom
		) {
			$this->oAccommodationAllocationCombination->setPaymentCompleted(true);
			$this->addMessage('Nicht berechnen');
			return;
		}

		// Periode: Wiederholbarer Bereich im Dialog (eigentlich immer nur ein Eintrag)
		$aPeriods = $this->oPaymentCategory->getJoinedObjectChilds('periods');

		$bMatchingPeriod = false;

		/*
		 * Wird solange wiederholt, bis nichts mehr gefunden wird und das Skript per return rausspringt
		 * Der Counter ist zur Sicherheit drin
		 */
		$iWhileCounter = 0;
		while($iWhileCounter < 100) {

			// Wenn einmal alle Perioden nicht erfolgreich waren, dann braucht man keinen weiteren Loop
			$bNextLoop = false;
		
			/* @var $oPeriod \Ts\Entity\AccommodationProvider\Payment\Category\Period */
			foreach($aPeriods as $oPeriod) {

				$bCheck = $this->checkCondition($oPeriod);

				if($bCheck === true) {

					$mReturn = $this->runPeriod($oPeriod, $iWhileCounter);

					if($mReturn !== false) {
						$bNextLoop = true;
						$iEntries += $mReturn;
						$bMatchingPeriod = true;
						break;
					}

				}

			}
			
			if($bNextLoop === false) {
				break;
			}
			
			$iWhileCounter++;
		}

		// Keine Periode gefunden
		if($bMatchingPeriod === false) {

			// Wenn der Eintrag komplett bezahlt ist, muss nicht unbedingt eine Periode gefunden werden
			if($this->oAccommodationAllocationCombination->payment_generation_completed === null) {
			
				/*
				 * Es ist nur fehlerhaft, dass keine Periode gefunden wurde, wenn die Zuweisung komplett in der 
				 * Vergangenheit (-3 Monate) liegt
				 */
				$dAllocationUntil = new DateTime($this->oAccommodationAllocationCombination->until);
				$dLimit = new DateTime;
				$dLimit->sub(new \DateInterval('P3M'));

				if($dAllocationUntil < $dLimit) {
					$this->saveError(Payment::ERROR_NO_MATCHING_PERIOD);
					$this->addMessage('Keine übereinstimmende Periode nach drei Monaten');
					return false;
				} else {
					$this->addMessage('Keine übereinstimmende Periode');
					return;
				}
				
			}

		}

		// Zusatzkosten nur betrachten, wenn für die Zuweisung auch ein Eintrag generiert wurde
		if($iEntries > 0) {
			$this->runAdditionalServices();
		}
		
		return $iEntries;
	}

	protected function runAdditionalServices() {

		$allocation = $this->oAccommodationAllocationCombination->getMasterAllocation();

		$from = new \Carbon\Carbon($allocation->from);
		$until = new \Carbon\Carbon($allocation->until);

		$journeyAccommodation = $allocation->getInquiryAccommodation();
		$bookedAdditionalServices = [];
		if($journeyAccommodation instanceof \Ext_TS_Inquiry_Journey_Accommodation) {
			$bookedAdditionalServices = $journeyAccommodation->getJoinTableData('additionalservices');
		}

		// Gebühr nur bei ERSTER Zuweisung dem Anbieter gutschreiben
		if(
			$allocation->isFirstAllocation() !== true ||
			$from < $this->firstPaymentStart
		) {
			return;
		}

		// Alle Zusatzleistungen mit kolumbus_costs.credit_provider = 1|2 ermitteln (gecacht)
		$additionalServices = $this->oSchool->getAdditionalServices('accommodation', null, true);
		
		$onlyProviderAdditionalServices = $additionalServiceIds = [];

		foreach($additionalServices as $additionalServiceData) {
			if($additionalServiceData['credit_provider'] == \Ext_Thebing_School_Additionalcost::CREDIT_PROVIDER_ONLY_PROVIDER) {
				if($additionalServiceData['charge'] == 'auto') {
					$onlyProviderAdditionalServices[] = $additionalServiceData;
				} elseif(in_array($additionalServiceData['id'], $bookedAdditionalServices)) {
					$onlyProviderAdditionalServices[] = $additionalServiceData;
				}
			} else {
				$additionalServiceIds[] = $additionalServiceData['id'];
			}
		}

		/** @var $inquiry \Ext_TS_Inquiry */
		$inquiry = $this->oAccommodationAllocationCombination->getInquiry();
		$docSearch = new \Ext_Thebing_Inquiry_Document_Type_Search();
		
		if($inquiry->has_invoice) {
			$documentTypes = $docSearch->getSectionTypes('invoice_without_proforma');
		} else {
			$documentTypes = $docSearch->getSectionTypes('proforma');
		}

		// Gibt es eine Leistung in den Rechnungen des Kunden, die an den Anbieter weitergegeben werden muss?
		$sqlQuery = "
			SELECT
				`kidvi`.`amount`,
				`kidvi`.`type_id` `additional_service_id`,
				`kidvi`.`description`,
				`kidvi`.`index_from`,
				`kidvi`.`index_until`,
				`kidvi`.`additional_info`
			FROM
				`kolumbus_inquiries_documents` `kid` JOIN
				`kolumbus_inquiries_documents_versions` `kidv` ON
					`kid`.`latest_version` = `kidv`.`id` AND
					`kidv`.`active` = 1 JOIN
				`kolumbus_inquiries_documents_versions_items` `kidvi` ON
					`kidv`.`id` = `kidvi`.`version_id` AND
					`kidvi`.`active` = 1
			WHERE
				`kid`.`active` = 1 AND
				`kid`.`type` IN (:document_types) AND
				`kidvi`.`type` = 'additional_accommodation' AND
				`kidvi`.`type_id` IN (:additional_services) AND
				`kidvi`.`parent_booking_id` = :inquiry_accommodation_id
			";
		$sqlData = [
			'document_types' => (array)$documentTypes,
			'additional_services' => (array)$additionalServiceIds,
			'inquiry_accommodation_id' => (int)$allocation->inquiry_accommodation_id
		];
		$invoicePositions = \DB::getQueryRows($sqlQuery, $sqlData);

		// Gebühr aus Rechnung auslesen!
		if(!empty($invoicePositions)) {
			
			foreach($invoicePositions as $invoicePosition) {
				
				$oPayment = new Payment;
				$oPayment->type = 'additionalservice';
				$oPayment->accommodation_allocation_id = $allocation->id;
				$oPayment->amount_currency_id = $this->oSchool->getCurrency();
				$oPayment->amount = $invoicePosition['amount'];

				$oPayment->from = $invoicePosition['index_from'];
				$oPayment->until = $invoicePosition['index_until'];

				$oPayment->groupby = 'additionalservice_'.$allocation->id;

				$aAdditional = [
					'item_description' => json_decode($invoicePosition['additional_info'], true),
					'additionalservice_id' => (int)$invoicePosition['additional_service_id'],
					'calculation' => [],
					'combination' => $this->oAccommodationAllocationCombination->getAllocationIds()
				];

				$oPayment->additional = json_encode($aAdditional);

				$oPayment->save();

			}
			
		}

		foreach($onlyProviderAdditionalServices as $onlyProviderAdditionalService) {
			
			$additionalService = \Ext_Thebing_School_Additionalcost::getInstance($onlyProviderAdditionalService['id']);

			$inquiryAccommodation = \Ext_TS_Inquiry_Journey_Accommodation::getInstance($this->oAccommodationAllocationCombination->inquiry_accommodation_id);
			
			$oAmount = new \Ext_Thebing_Inquiry_Amount($this->oAccommodationAllocationCombination->getInquiry());
			$oAmount->setTimeData($allocation->from, $allocation->until);
			
			if($additionalService->limited_availability) {

				$validity = \Ts\Entity\Additionalcost\Validity::getValidEntry($additionalService, $from, $until);
				if($validity === null) {
					continue;
				}

				$overlap = \Core\Helper\DateTime::getDateRangeIntersection($from, $until, new Carbon($validity->valid_from), new Carbon($validity->valid_until));

				$currentFrom = $overlap['start'];
				$currentUntil = $overlap['end'];

			} else {
				$currentFrom = clone $from;
				$currentUntil = clone $until;
			}

			$iFrom = $currentFrom->getTimestamp();
			$iUntil = $currentUntil->getTimestamp();

			$iAmount = $oAmount->calculateAdditionalAccommodationCost($additionalService, $currentFrom, $currentUntil, false, $inquiryAccommodation->accommodation_id);

			$oPayment = new Payment;
			$oPayment->type = 'additionalservice';
			$oPayment->accommodation_allocation_id = $allocation->id;
			$oPayment->amount_currency_id = $this->oSchool->getCurrency();
			$oPayment->amount = $iAmount;

			$oPayment->from = $from->toDateString();
			$oPayment->until = $from->toDateString();

			$oPayment->groupby = 'additionalservice_'.$allocation->id;

			$aAdditional = [
				#'item_description' => json_decode($invoicePosition['additional_info'], true),
				'additionalservice_id' => $additionalService->id,
				'calculation' => [],
				'combination' => $this->oAccommodationAllocationCombination->getAllocationIds()
			];

			$oPayment->additional = json_encode($aAdditional);

			$oPayment->save();

		}
		
	}

	/**
	 * @param \DateTime $dAllocationFrom
	 */
	public function initializeCostCategory(\DateTime $dAllocationFrom) {

		/* @var $oCostCategoryRepo \Ext_Thebing_Accommodation_Cost_CategoryRepository */
		$oCostCategoryRepo = \Ext_Thebing_Accommodation_Cost_Category::getRepository();

		$this->oCostCategory = $oCostCategoryRepo->findByProvider($this->oAccommodationProvider, $dAllocationFrom);

	}

	/**
	 * @return DateTime|null
	 */
	protected function getLatestPaymentDate() {

		if(!isset($this->aCache['getLatestPaymentDate'])) {

			$oPaymentRepo = Payment::getRepository();
			$oLatestPayment = $oPaymentRepo->getLatestPaymentByAllocationCombination($this->oAccommodationAllocationCombination);

			$oSavedPaymentRepo = \Ext_Thebing_Accommodation_Payment::getRepository();
			$oLatestSavedPayment = $oSavedPaymentRepo->getLatestPaymentByAllocationCombination($this->oAccommodationAllocationCombination);

			if($oLatestPayment) {
				if(strpos($oLatestPayment->type, 'error') === false) {
					$dLatestPayment = new DateTime($oLatestPayment->until);
				} else {
					// Wenn schon ein Fehlereintrag existiert für diese Zuweisung, dann nicht weiter machen.
					return false;
				}
			} 

			if($oLatestSavedPayment) {
				$oLatestSavedPayment = $oLatestSavedPayment->getUntilDate();
				$dLatestSavedPayment = new DateTime($oLatestSavedPayment->get(\WDDate::DB_DATE));
			}

			$this->aCache['getLatestPaymentDate'] = $this->getMaxDate($dLatestPayment, $dLatestSavedPayment);
		}

		return $this->aCache['getLatestPaymentDate'];
	}

	/**
	 * @param DateTime $dFirst
	 * @param DateTime $dSecond
	 * @return DateTime
	 */
	protected function getMaxDate(DateTime $dFirst=null, DateTime $dSecond=null) {

		if(
			!empty($dFirst) &&
			!empty($dSecond)
		) {
			$dReturn = max($dFirst, $dSecond);
		} elseif(!empty($dFirst)) {
			$dReturn = $dFirst;
		} elseif(!empty($dSecond)) {
			$dReturn = $dSecond;
		} else {
			$dReturn = null;
		}

		return $dReturn;
	}
	
	/**
	 * 
	 * @param Period $oPeriod
	 * @param int $iWhileCounter
	 * @return int|boolean
	 */
	protected function runPeriod(Period $oPeriod, &$iWhileCounter) {

		$iEntries = 0;
		$iStartWeek = 0;

		$bMarkAllocationAsComplete = false;
		
		/**
		 * Abrechnungszeitraum ermitteln
		 */
		
		// Zuweisungszeitraum
		$dAllocationFrom = new DateTime($this->oAccommodationAllocationCombination->from);
		$dAllocationUntil = new DateTime($this->oAccommodationAllocationCombination->until);

		// Prüfen, ob es bereits Payments gibt, bzw. schon Payments ausgeführt wurden, die berücksichtigt werden müssen
		$dLatestPayment = $this->getLatestPaymentDate();

		// Es gibt bereits einen Fehlereintrag
		if($dLatestPayment === false) {
			return false;
		} elseif($dLatestPayment !== null) {
			#$iStartWeek = \Ext_Thebing_Util::countWeeks($dAllocationFrom, $dLatestPayment);
			// Extranächte nicht als Woche zählen
			$iStartWeek = $this->getWeeks($dAllocationFrom, $dLatestPayment, true);
			$dAllocationFrom = $dLatestPayment;
		}

		// Abrechnungszeitraum
		$dBillingFrom = clone $dAllocationFrom;
		$dBillingUntil = clone $dAllocationUntil;
		
		// Einheiten die abgerechnet werden sollen
		$iBillingUnits = $oPeriod->weeks;
		
		// Einheiten, die in der Zuweisung generell noch nicht abgerechnet sind
		$iAllocationUnits = 0;

		$sBillingIntervalDesignator = 'W';
		
		if($oPeriod->period_type == 'absolute_weeks') {
			
			// Nur beim ersten Durchlauf modifizieren
			if($dLatestPayment === null) {
				
				$iBillingFromWeekDay = $dBillingFrom->format('N');
				// Wenn der Starttag der Zuweisung von dem Periodenstarttag abweicht, dann anpassen
				if($oPeriod->period_start_day != $iBillingFromWeekDay) {
					$sWeekday = $this->getWeekDay($oPeriod->period_start_day);
					$dBillingFrom->modify('last '.$sWeekday);
				}

			}
			$iAllocationUnits = \Ext_Thebing_Util::countWeeks($dBillingFrom, $dBillingUntil);
			
		} elseif($oPeriod->period_type == 'absolute_month') {

			// Nur beim ersten Durchlauf modifizieren
			if($dLatestPayment === null) {
				$dBillingFrom->modify('first day of this month');
			}
			$iAllocationUnits = \Ext_Thebing_Util::countMonth($dBillingFrom, $dBillingUntil);
			$sBillingIntervalDesignator = 'M';
			
		} else {
			
			$iAllocationUnits = \Ext_Thebing_Util::countWeeks($dBillingFrom, $dBillingUntil);
			
		}

		// Zeitraum müss gekürzt werden weil die Einstellungen eine kleinere Wochenanzahl vorsehen
		if($iAllocationUnits > $iBillingUnits) {
			$dAllocationUntil = clone $dBillingFrom;
			$dAllocationUntil->add(new \DateInterval('P'.$oPeriod->weeks.$sBillingIntervalDesignator));
		// Zeitraum ist komplett, Zuweisung markieren
		} else {
			$bMarkAllocationAsComplete = true;
			// While abbrechen weil Zeitraum komplett
			$iWhileCounter = 999;
		}

		// Abbrechen, wenn nix mehr übrig ist.
		if($dAllocationUntil <= $dAllocationFrom) {
			$this->oAccommodationAllocationCombination->setPaymentCompleted();
			return false;
		}

		// Zeitraum nochmal prüfen
		$bIntersection = $this->compareWithPeriodSetting($oPeriod, $dAllocationFrom, $dAllocationUntil);
		if($bIntersection !== true) {
			return false;
		}

		// Zahlungszeitraum in Vergangenheit?
		if(
			$oPeriod->only_past_periods == 1 &&
			$dAllocationUntil > new \DateTime
		) {
			return false;
		}

		$aGroupByItems = array();
		switch($oPeriod->display) {
			case 'week':
				// Auf Wochen aufteilen
				while($dAllocationFrom < $dAllocationUntil) {
					$dWeekFrom = clone $dAllocationFrom;
					if(
						$iStartWeek === 0 &&
						/*
						 * Bei absolutem Monat nicht $dBillingFrom nehmen, da hier ansonsten komische Datumsangaben generiert werden:
						 * Zuweisung startet am 09.08.2015, $dBillingFrom steht auf 01.08.2015 und $dWeekUntil dann plötzlich auf 08.08.2015 #8615
						 */
						$oPeriod->period_type !== 'absolute_month'
					) {
						$dWeekUntil = clone $dBillingFrom;
					} else {
						$dWeekUntil = clone $dAllocationFrom;
					}
					$dWeekUntil->add(new \DateInterval('P1W'));
					if($dWeekUntil > $dAllocationUntil) {
						$dWeekUntil = clone $dAllocationUntil;
					}

					// $dWeekFrom muss auf den eingestellten Tag der Periode korrigiert werden, da das ansonsten nur mit Montag funktioniert.
					// Beispiel: Starttag Donnerstag, Unterkunftszuweisung geht von Dienstag-Sonntag. Hier müsste es jetzt zwei Einträge gebeen,
					// aber bei $dWeekFrom->format('YW') würden beide Einträge denselben Key bekommen. #12183
					$dGroupingWeek = clone $dWeekFrom;
					if($oPeriod->period_start_day != $dGroupingWeek->format('N')) {
						$sWeekday = $this->getWeekDay($oPeriod->period_start_day);
						$dGroupingWeek->modify('last '.$sWeekday);
					}

					$aGroupByItems['week_'.$this->oAccommodationAllocationCombination->getAllocationIdsAsString().'_'.$dGroupingWeek->format('YW').'_'.$dWeekUntil->format('YW')] = array(
						'from' => $dWeekFrom,
						'until' => $dWeekUntil,
						'start_week' => $iStartWeek
					);
					$dAllocationFrom = clone $dWeekUntil;
					$iStartWeek++;
				}
				break;
			case 'single':
				$aGroupByItems['single_'.$this->oAccommodationAllocationCombination->getAllocationIdsAsString().'_'.$dAllocationFrom->format('Y-m-d').'_'.$dAllocationUntil->format('Y-m-d')] = array(
					'from' => $dAllocationFrom,
					'until' => $dAllocationUntil,
					'start_week' => $iStartWeek
				);
				break;
			case 'allocation':
				$aGroupByItems['allocation_'.$this->oAccommodationAllocationCombination->getAllocationIdsAsString()] = array(
					'from' => $dAllocationFrom,
					'until' => $dAllocationUntil,
					'start_week' => $iStartWeek
				);
				break;
			case 'student':
				$oInquiry = $this->oAccommodationAllocationCombination->getInquiry();
				$aGroupByItems['student_'.$oInquiry->id] = array(
					'from' => $dAllocationFrom,
					'until' => $dAllocationUntil,
					'start_week' => $iStartWeek
				);
				break;
			case 'provider':
				$aGroupByItems['provider_'.$this->oAccommodationProvider->id] = array(
					'from' => $dAllocationFrom,
					'until' => $dAllocationUntil,
					'start_week' => $iStartWeek
				);
				break;
		}

		foreach($aGroupByItems as $sGroupBy=>$aGroupBy) {

			$bFullLastWeek = false;
			if($aGroupBy['until'] != $dBillingUntil) {
				$bFullLastWeek = true;
			}

			$fAmount = $this->getAmount($aGroupBy['from'], $aGroupBy['until'], $aGroupBy['start_week'], $bFullLastWeek);

			$this->saveEntry($oPeriod->display, $sGroupBy, $aGroupBy, $fAmount, $oPeriod);
			$iEntries++;
		}

		if($bMarkAllocationAsComplete === true) {
			$this->oAccommodationAllocationCombination->setPaymentCompleted();
		}

		return $iEntries;
	}
	
	/**
	 * @param Period $oPeriod
	 * @return bool
	 */
	public function checkCondition(Period $oPeriod) {

		$oInquiry = $this->oAccommodationAllocationCombination->getInquiry();

		$iInquiryInboxId = $oInquiry->getInbox()->id;

		// Inbox checken
		if(in_array($iInquiryInboxId, $oPeriod->inboxes) === false) {
			return false;
		}

		$dFrom = new DateTime;
		$dUntil = new DateTime;
		$this->getPeriodDateObjects($oPeriod, $dFrom, $dUntil);

		$bIntersection = $this->compareWithPeriodSetting($oPeriod, $dFrom, $dUntil);

		if($bIntersection !== true) {
			return false;
		}

		if($oPeriod->duration_dependency == 1) {

			$bCheck = $this->checkDurationDependency($oPeriod);
			if($bCheck !== true) {
				return false;
			}

		}

		return true;
	}

	/**
	 * @param Period $oPeriod
	 * @param DateTime $dFrom
	 * @param DateTime $dUntil
	 * @return boolean
	 */
	protected function compareWithPeriodSetting(Period $oPeriod, DateTime $dFrom, DateTime $dUntil) {

		$dCompareFrom = new DateTime;
		$dCompareUntil = new DateTime;
		$this->getCompareDateObjects($dCompareFrom, $oPeriod->before_quantity, $oPeriod->before_unit, $oPeriod->before_direction);
		$this->getCompareDateObjects($dCompareUntil, $oPeriod->after_quantity, $oPeriod->after_unit, $oPeriod->after_direction);

		$bIntersection = \Core\Helper\DateTime::checkDateRangeOverlap($dFrom, $dUntil, $dCompareFrom, $dCompareUntil);
		
		return $bIntersection;
	}
	
	/**
	 * @param Period $oPeriod
	 * @return boolean
	 */
	protected function checkDurationDependency(Period $oPeriod) {

		$aConditions = array_values($oPeriod->conditions);

		$sCondition = '';

		$dLatestPayment = $this->getLatestPaymentDate();

		$dAccommodationAllocationStart = new DateTime($this->oAccommodationAllocationCombination->from);
		$dNow = new DateTime;
		
		foreach($aConditions as $iCondition=>$aCondition) {
			
			if($iCondition > 0) {
				$sCondition .= ' '.$aCondition['logic_operator'].' ';
			}
			
			$oDiff = null;
			if($aCondition['basis_date'] == 'accommodation_start') {
				$oDiff = $dAccommodationAllocationStart->diff($dNow);
			} elseif(
				$dLatestPayment &&
				$aCondition['basis_date'] == 'latest_payment'
			) {
				$oDiff = $dLatestPayment->diff($dNow);
			}
			
			if($oDiff === null) {
				$sCondition .= ' true ';
			} else {
			
				$iDayDiff = $oDiff->format('%R%a');
				$iWeekDiff = ceil($iDayDiff/7);

				$sCondition .= (int)$iWeekDiff;
				
				switch($aCondition['comparative_operators']) {
					case '<':
						$sCondition .= ' < ';
						break;
					case '>':
						$sCondition .= ' > ';
						break;
					default:
						$sCondition .= ' == ';
						break;
				}
				
				$sCondition .= (int)$aCondition['weeks'];

			}
			
		}
		
		$bResult = null;
		$sEval = '$bResult = ('.$sCondition.');';

		eval($sEval);

		return $bResult;
	}

	/**
	 * Gibt die Anzahl der Wochen in einem Zeitraum in Abhängigkeit von den Schuleinstellungen zurück
	 * @param \DateTime $dFrom
	 * @param \DateTime $dUntil
	 * @param bool $bFullLastWeek
	 * @return integer
	 */
	protected function getWeeks(\DateTime $dFrom, \DateTime $dUntil, bool $bFullLastWeek) {

		$iWeekExtraNights = $this->oSchool->extra_nights_cost;

		$oDiff = $dFrom->diff($dUntil);
		$iDays = $oDiff->format('%R%a');

		$iWeeks = 0;

		$iNightsOfLastWeek = 7;
		if($bFullLastWeek === false) {
			$iNightsOfLastWeek = $this->oAccommodationAllocationCombination->getAccommodationCategory()->getAccommodationInclusiveNights($this->oSchool);
		}

		// Weniger Tage als letzte Woche aber weniger als Extranächtewoche, dann 0 Wochen
		if(
			$iDays < $iNightsOfLastWeek &&
			$iDays < $iWeekExtraNights
		) {
			return 0;
		}
		
		$iDaysFirstWeeks = $iDays - $iNightsOfLastWeek;

		// Letzte Woche zählen
		$iWeeks++;

		if($iDaysFirstWeeks > 0) {
			$iWeeks += floor($iDaysFirstWeeks/7);
		}
		
		// Das ist total falsch was hier passiert. Muss man nochmal genauer durchspielen, inwiefern das hier relevant ist.
		$iDaysRemainder = $iDaysFirstWeeks % 7;

		if($iDaysRemainder >= $iWeekExtraNights) {
			$iWeeks++;
		}

		return $iWeeks;
	}
	
	/**
	 * @param integer $iDay
	 * @return string
	 */
	protected function getWeekDay($iDay) {
		return \Ext_Thebing_Util::convertWeekdayToEngWeekday($iDay);
	}
	
	/**
	 * @param DateTime $dDate
	 * @param int $iQuantity
	 * @param int $iUnit
	 * @param string $sDirection
	 */
	protected function getCompareDateObjects(DateTime &$dDate, $iQuantity, $iUnit, $sDirection) {

		switch($sDirection) {
			case 'pre':
			case 'start_month_minus':
			case 'end_month_minus':
				$sSign = '-';
				break;
			case 'post':
			case 'start_month_plus':
			case 'end_month_plus':
				$sSign = '+';
				break;
		}

		switch($sDirection) {
			case 'start_month_minus':
			case 'start_month_plus':
				
				if($iUnit == 10) {
					$dDate->modify('first day of '.$sSign.''.(int)$iQuantity.' month');
					$dDate->modify('midnight');
					return;
				} else {
					$dDate->modify('first day of this month');
					$dDate->modify('midnight');
				}
				break;
			case 'end_month_minus':
			case 'end_month_plus':
				
				if($iUnit == 10) {
					$dDate->modify('first day of '.$sSign.''.(int)$iQuantity.' month');
					$dDate->modify('next month');
					$dDate->modify('midnight');
					$dDate->modify('-1 sec');
					return;
				} else {
					$dDate->modify('first day of next month');
					$dDate->modify('midnight');
					$dDate->modify('-1 sec');
				}
				break;
		}

		switch($iUnit) {
			case 8:
				$sModify = (int)$iQuantity.' days';
				break;
			case 9:
				$sModify = (int)$iQuantity.' weeks';
				break;
			case 10:
				$sModify = (int)$iQuantity.' months';
				break;
			default:
				$sWeekday = $this->getWeekDay($iUnit);
				$sModify = (int)$iQuantity.' '.$sWeekday;
				break;
		}

		$sModify = $sSign.$sModify;

		$dDate->modify($sModify);

	}
	
	/**
	 * @param Period $oPeriod
	 * @param DateTime $dFrom
	 * @param DateTime $dUntil
	 */
	protected function getPeriodDateObjects(Period $oPeriod, DateTime &$dFrom, DateTime &$dUntil) {
		
		switch($oPeriod->basedon) {
			case 'service_period':
				$dFrom = new DateTime($this->oAccommodationAllocationCombination->from);
				$dUntil = new DateTime($this->oAccommodationAllocationCombination->until);
				break;
			case 'accommodation_start':
				$dFrom = new DateTime($this->oAccommodationAllocationCombination->from);
				$dUntil = new DateTime($this->oAccommodationAllocationCombination->from);
				break;
			case 'accommodation_end':
				$dFrom = new DateTime($this->oAccommodationAllocationCombination->until);
				$dUntil = new DateTime($this->oAccommodationAllocationCombination->until);
				break;
		}
		
	}
	
	/**
	 * @param string $sType
	 */
	protected function saveEntry($sType, $sGroupBy, array $aDates, $fAmount, $oPeriod) {

		$fTotalAmount = $fAmount;
		
		$oDiff = $aDates['from']->diff($aDates['until']);
		
		$iTotalDays = $oDiff->format('%a');

		// Pro Zuweisung in der Kombination einen Eintrag speichern
		$aSaveEntries = [];
		foreach($this->oAccommodationAllocationCombination as $oAllocation) {
			
			$oAllocationFrom = new DateTime($oAllocation->from);
			$oAllocationUntil = new DateTime($oAllocation->until);

			$aIntersection = DateTime::getDateRangeIntersection($oAllocationFrom, $oAllocationUntil, $aDates['from'], $aDates['until']);
			
			if($aIntersection !== null) {
				
				$oIntersectionDiff = $aIntersection['start']->diff($aIntersection['end']);
				$iIntersectionDays = $oIntersectionDiff->format('%a');

				/*
				 * Keine echte Überschneidung wenn Tage = 0, 
				 * folglich auch keine Splittung des Betrags -> $fIntersectionAmount = 0
				 */
				if($iIntersectionDays === '0') {
					continue;
				}
				
				$fIntersectionAmount = round($fTotalAmount / $iTotalDays * $iIntersectionDays, 2);
				$fAmount = bcsub($fAmount, $fIntersectionAmount);

				$aSaveEntry = [
					'allocation_id' => $oAllocation->id,
					'from' => $aIntersection['start'],
					'until' => $aIntersection['end'],
					'amount' => $fIntersectionAmount
				];

				$aSaveEntries[] = $aSaveEntry;

			}
	
		}

		// Hier sollte eigentlich immer etwas drin stehen, sonst gibt es hiernach einen Leereintrag und einen Fatal Error
		if(empty($aSaveEntries)) {
			throw new \RuntimeException('UAB: No save entry has matched! '.$this->oAccommodationAllocationCombination->id);
		}

		if(bccomp($fAmount, 0) !== 0) {
			end($aSaveEntries);
			$iLatestEntry = key($aSaveEntries);
			$aSaveEntries[$iLatestEntry]['amount'] = bcadd($aSaveEntries[$iLatestEntry]['amount'], $fAmount);
		}

		foreach($aSaveEntries as $aSaveEntry) {
			
			$oPayment = new Payment;
			$oPayment->type = $sType;
			$oPayment->accommodation_allocation_id = $aSaveEntry['allocation_id'];
			$oPayment->payment_category_id = $this->oPaymentCategory->id;
			$oPayment->cost_category_id = $this->oCostCategory->id;
			$oPayment->amount_currency_id = $this->oSchool->getCurrency();
			$oPayment->amount = $aSaveEntry['amount'];
			$oPayment->period_id = $oPeriod->id;

			if(!isset($this->firstPaymentStart)) {
				$this->firstPaymentStart = $aSaveEntry['from'];
			}
			$this->firstPaymentStart = min($this->firstPaymentStart, $aSaveEntry['from']);

			$oPayment->from = $aSaveEntry['from']->format('Y-m-d');
			$oPayment->until = $aSaveEntry['until']->format('Y-m-d');

			$oPayment->groupby = $sGroupBy;

			$aAdditional = [
				'calculation' => $this->aLastCalculationDescription,
				'combination' => $this->oAccommodationAllocationCombination->getAllocationIds()
			];

			$oPayment->additional = json_encode($aAdditional);

			$oPayment->save();
			
		}

		// Cache zurücksetzen, weil sich Daten geändert haben könnten
		$this->aCache = array();
		
	}

	/**
	 * @param string $sError
	 */
	protected function saveError($sError) {

		$oPayment = new Payment;
		$oPayment->type = $sError;
		$oPayment->accommodation_allocation_id = $this->oAccommodationAllocationCombination->id;
		
		if($this->oPaymentCategory) {
			$oPayment->payment_category_id = $this->oPaymentCategory->id;
		}
		if($this->oCostCategory) {
			$oPayment->cost_category_id = $this->oCostCategory->id;
		}

		$oPayment->amount = null;
		$oPayment->groupby = $oPayment->type.'_'.$this->oAccommodationAllocationCombination->getAllocationIdsAsString();

		$aAdditional = [
			'combination' => $this->oAccommodationAllocationCombination->getAllocationIds()
		];

		$oPayment->additional = json_encode($aAdditional);

		$oPayment->save();
	}
	
	/**
	 * @param \DateTime $dFrom
	 * @param \DateTime $dUntil
	 * @param integer $iStartWeek
	 * @return float
	 */
	public function getAmount(\DateTime $dFrom, \DateTime $dUntil, $iStartWeek=0, $bFullLastWeek=false) {
		
		$oAccommodationAmount = new \Ext_Thebing_Accommodation_Amount();
		$oAccommodationAmount->setCalculateType('cost');

		$oAccommodationAmount->setAccommodationProvider($this->oAccommodationAllocationCombination->getAccommodationProvider()->id);

		$oAccommodationAmount->setInquiryAccommodation($this->oAccommodationAllocationCombination->inquiry_accommodation_id);

		// Zeitraum und Wochen nochmal speziel setzen
		// da wir nur die Zuweisung anschauen
		$oAccommodationAmount->setCalculatePeriod($dFrom->getTimestamp(), $dUntil->getTimestamp());

		$iWeeks = $this->getWeeks($dFrom, $dUntil, $bFullLastWeek);

		$oAccommodationAmount->setWeeks($iWeeks);

		$oAccommodationAmount->setCurrency($this->oSchool->getAccommodationCurrency());

		$fAmount = 0;
		
		if($this->oCostCategory->cost_type == 'night') {
			$fAmount = bcadd($fAmount, $oAccommodationAmount->calculate(false, $iStartWeek), 5);
		} else {
			if($iWeeks > 0) {
				$fAmount = bcadd($fAmount, $oAccommodationAmount->calculate(false, $iStartWeek), 5);
			}
			// Immer aufrufen. Es wird automatisch geprüft, ob es Extranächte gibt
			$fAmount = bcadd($fAmount, $oAccommodationAmount->calculateExtraNight(false, $bFullLastWeek), 5);
		}

		$this->aLastCalculationDescription = $oAccommodationAmount->getCalculationDescription();

		/*
		 * Wenn bei der Kostenberechnung ein Fehler aufgetreten ist
		 * @todo Fehlerbehandlung ergänzen 
		 */
		if(!empty($oAccommodationAmount->aErrors)) {
			
		}

		// Betrag runden je nach Einstellungen in Kostenkategorie
		if(!empty($this->oCostCategory)) {
			$fAmount = $this->oCostCategory->round($fAmount);
		}

		return $fAmount;

	}

	public function getMessages() {
		return $this->aMessages;
	}

	public function getAllocationDescription() {

		$oInquiry = $this->oAccommodationAllocationCombination->getInquiry();
		$oCustomer = $oInquiry->getCustomer();
		$sName = $oCustomer->getName();
		$sNumber = $oCustomer->getCustomerNumber();

		$oDateFormat = new \Ext_Thebing_Gui2_Format_Date();

		$sFrom = $oDateFormat->formatByValue($this->oAccommodationAllocationCombination->from);
		$sUntil = $oDateFormat->formatByValue($this->oAccommodationAllocationCombination->until);
		
		$aReturn = array(
			'customer_number' => $sNumber,
			'customer_name' => $sName,
			'from' => $sFrom,
			'until' => $sUntil
		);
		
		$oProvider = $this->oAccommodationAllocationCombination->getAccommodationProvider();
		if($oProvider) {
			$aReturn['provider'] = $oProvider->getName();
		}
	
		if($this->oPaymentCategory) {
			$aReturn['payment_category'] = $this->oPaymentCategory->name;
		}
		
		return $aReturn;
	}

	protected function addMessage($sMessage) {

		$aMessage = $this->getAllocationDescription();
		$aMessage['message'] = $sMessage;
		
		$this->aMessages[] = $aMessage;

	}

}
