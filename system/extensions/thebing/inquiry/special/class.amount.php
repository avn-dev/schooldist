<?PHP

use \Core\Helper\DateTime;

/*
 * Klasse berechnet den Special Amount einer Rechnungsposition
 */
class Ext_Thebing_Inquiry_Special_Amount {

	use \Ts\Traits\SpecialAmount;
	
	/**
	 * Preis preis für eine Woche
	 *
	 * @var int
	 */
	public $fPrice = 0;

	/**
	 * Preis für alle Wochen
	 *
	 * @var array
	 */
	public $aPrice = array();

	/**
	 * Object der Buchungspos. die gerade berechnet wird
	 *
	 * @var Ext_TS_Inquiry_Journey_Course|Ext_TS_Inquiry_Journey_Accommodation|Ext_Thebing_Transfer_Package|Ext_Thebing_School_Additionalcost
	 */
	public $oObject = null;

	/**
	 * Gibt an ob es sich z.B. um eine Wöchentliche be-specialung handelt.
	 *
	 * @var string
	 */
	public $sCalculationType = '';

	/**
	 * @var DateTime
	 */
	//protected $dCalculationWeek;

	/**
	 * Gibt die aktuelle zu berechnende Kurswoche an
	 *
	 * @var int
	 */
	public $iCurrentCourseWeek = 0;

	/**
	 * Inquiry setzen (benötigt für Transfer Special)
	 *
	 * @var null
	 */
	protected $_oInquiry = null;

	/**
	 * Gefundene und angewendete Special Blocks
	 *
	 * @var array
	 */
	protected $_aSpecialBlocks = array();

	protected $aCalculation = [];

	public $aSpecialPeriods = [];
	
	/**
	 * @var \Core\DTO\DateRange
	 */
	public $oSpecialPeriod;

	private int $iCalculationTime = 0;
	
	/**
	 * @param $mPrice
	 * @param null $oObject
	 * @param int $iCurrentCourseWeek
	 */
	public function __construct($mPrice = 0, $oObject = null, $iCurrentCourseWeek = 0) {
		
		$this->oObject = $oObject;
		$this->setPrice($mPrice);
		$this->iCurrentCourseWeek = $iCurrentCourseWeek;

	}

	/**
	 * Setzt manuell das Inquiry Object
	 *
	 * @param $oInquiry
	 */
	public function setInquiry($oInquiry) {

		if($oInquiry instanceof Ext_TS_Inquiry_Abstract) {
			$this->_oInquiry = $oInquiry;
		}

	}

	/**
	 * Setzen des Betrages
	 *
	 * @param $mPrice
	 */
	public function setPrice($mPrice) {

		if(is_array($mPrice)) {
			// Array mit Preisen für alle Wochen
			$this->aPrice = $mPrice;
		} else {
			$this->fPrice = (float)$mPrice;
		}

	}

	/**
	 * Setzen der Berechnungsart. Ob nur Specials gesucht werden
	 * sollen die sich auf wöchentliche Preisberechnung beziahen, oder nicht
	 *
	 * @param $sType
	 */
	public function setCalculationType($sType) {
		$this->sCalculationType = $sType;
	}

	/**
	 * Zeit für die Berechnung setzen, da der Preis mal eine Zeitangabe enthalten kann oder mal nicht (Legacy-Legacy-Mist?)
	 *
	 * @param $iTime
	 * @return void
	 */
	public function setCalculationTime($iTime) {
		$this->iCalculationTime = $iTime;
	}

	/**
	 * Woche (als Datum) setzen, damit Berechnung: Exakter Leistungszeitraum funktioniert
	 *
	 * @param DateTime $dDate
	 */
	//public function setCalculationWeek(DateTime $dDate) {
	//	$this->dCalculationWeek = $dDate;
	//}

	/**
	 * Liefert alle gefundenen Special-block Ids
	 *
	 * @return array
	 */
	public function getBlocks() {
		return $this->_aSpecialBlocks;
	}

	/**
	 * @return float
	 */
	public function getAmount() {

		$iObjectId = 0;
		$oInquiry = null;
		$sType = '';
		$iChargeType = null;
		
		$this->oSpecialPeriod = new \Core\DTO\DateRange;

		if($this->oObject instanceof Ext_TS_Service_Interface_Course) {

			$oInquiry = $this->oObject->getInquiry();
			$iObjectId = $this->oObject->id;
			$sType = 'course';
			$oCourse = $this->oObject->getCourse();

		} elseif($this->oObject instanceof Ext_TS_Service_Interface_Accommodation) {

			$oInquiry = $this->oObject->getInquiry();
			$iObjectId = $this->oObject->id;
			$sType = 'accommodation';

		} elseif(
			$this->oObject instanceof Ext_Thebing_Transfer_Package &&
			$this->_oInquiry instanceof Ext_TS_Inquiry_Abstract
		) {

			$oInquiry = $this->_oInquiry;
			$iObjectId = $this->oObject->id;
			$sType = 'transfer';

		} elseif(
			$this->oObject instanceof Ext_Thebing_School_Additionalcost &&
			$this->_oInquiry instanceof Ext_TS_Inquiry_Abstract
		) {

			$oInquiry = $this->_oInquiry;
			$iObjectId = $this->oObject->id;
			
			if($this->oObject->type == 1) {
				$sType = 'additional_accommodation';
			} elseif($this->oObject->type == '0') {
				$sType = 'additional_course';
			} else {
				$sType = 'additional_general';
			}
			$iChargeType = $this->oObject->calculate;
			
		}

		// Objekt ist noch nicht gespeichert
		if(empty($iObjectId)) {
			$iObjectId = spl_object_hash($this->oObject);
		}
		
		// Betrag der Vergünstigung
		$aSpecialAmount = array();

		if(is_object($this->oObject)) {

			// Special finden, das für diesen Kurs genommen werden darf
			$inquirySpecials = Ext_Thebing_Inquiry_Special_Position::search($sType, $iObjectId, $oInquiry, true);

			// Preise als Basis für die Special Blöcke
			if(count($this->aPrice) > 0) {
				$aTempPrice = $this->aPrice;
			} else {
//				$aTempPrice = array(0 =>$this->fPrice);
				$aTempPrice = [$this->iCalculationTime => $this->fPrice];
			}

			$iTempPriceKey = 1; // Wochen für $aCalculation hochzählen, da Array nur Timestamps als Keys enthählt
			// Über diese Variable wird gesteuert, dass bei Wochenpreisen pauschale Rabatte nur einmal abgezogen werden
			$discountCalculated = [];
			$aSpecialPeriods = [];

			// Alle Preise durchgehen:
			// Array hat nur einen Durchlauf, dann ist es ein Wochenpreis
			// Array hat mehrere Durchläufe, dann ist es ein end-preis aus mehreren Wochen
			foreach((array)$aTempPrice as $iTime => $fTempPrice) {

				// Hinweis:
				// Bei mehreren gefundenen Special Blocks muss immer mit dem bereits runter-gerechneten
				// Special Betrag weitergerechnet werden, für den nächsten Block
				foreach($inquirySpecials as $inquirySpecial) {

					if(
						$this->oObject instanceof Ext_Thebing_School_Additionalcost &&
						$iChargeType == 0 &&
						!empty($aSpecialAmount)
					) {
						continue;
					}

					$oSpecial = $inquirySpecial->getSpecial();
					if($oSpecial->service_from !== null) {
						$dSpecialFrom = new \Carbon\Carbon($oSpecial->service_from);
					}
					if($oSpecial->service_until !== null) {
						$dSpecialUntil = new \Carbon\Carbon($oSpecial->service_until);
						$dSpecialUntil->endOfDay();
					}					

					if(!is_object($oSpecial)) {
						continue;
					}

					// Abhängig von Dauer
					$oSpecialBlock = $inquirySpecial->getBlock();
	
					// Special, das nicht für Transfere gilt
					if(
						$this->oObject instanceof Ext_Thebing_Transfer_Package &&
						(
							$oSpecial->amount_type == 2 ||
							$oSpecial->amount_type == 3
						)
					) {
						continue;
					}
				
					// Das special Limit muss immer gecheckt werden
					$mAvailableSpacials = $oSpecial->getAvailable();
				
					if(
						$mAvailableSpacials < 1 
//							&&
//						$inquirySpecial->used == 0 // wenn Position verwendet wurde, darf wieder verwendet werden
					) {
						continue;
					}

					// Blöcke die Pro Kurs/Unterkunft gelten gelten NICHT für Zusatzkosten #1847
					if(
						$this->oObject instanceof Ext_Thebing_School_Additionalcost &&
						$oSpecialBlock->option_id == 1
					) {
						continue;
					}

					// Special ist noch gültig
					if(is_object($oSpecial)) {

						// Wöchentliche Specials dürfen nur berücksichtigt werden, wenn wir in der Wöchentlichen Berechnung sind
						if($this->sCalculationType == 'weekly') {

							if($oSpecial->amount_type == 1){
								// Prozentual ist NIE wöchentlich
								continue;
							}

							if(
								$oSpecial->amount_type == 2 &&
								(
									$oSpecialBlock->option_id == 1 ||
									$oSpecialBlock->option_id == 4 ||
									$oSpecialBlock->option_id == 5
								)
							) {
								// "Einmal" ist NIE wöchentlich
								continue;
							}

						} elseif($this->sCalculationType != 'weekly') {

							if(
								$oSpecial->amount_type == 2 &&
								$oSpecialBlock->option_id != 1 &&
								$oSpecialBlock->option_id != 4 &&
								$oSpecialBlock->option_id != 5
							) {
								// Wöchentliche specials überspringen
								continue;
							}

							if($oSpecial->amount_type == 3) {
								// Weeks ist immer wöchentlich
								continue;
							}

						}

						// Special Block gefunden und kann angewand werden 
						switch($oSpecial->amount_type) {
							case 1: // Prozent

								$fPercent = (float)$oSpecialBlock->percent;
								$fAmount = $fTempPrice * ($fPercent / 100);

								// Wenn der Preis ohne Array gesetzt wird oder leer ist, gibt es keinen Zeitpunkt
								// 2022: Wenn dann auch $this->iCalculationTime leer ist, darf $dWeek nicht 1970 sein
								if (empty($iTime)) {
									throw new RuntimeException(sprintf('No time for special calculation: "%s", %s %d', $oSpecial->name, $sType, $iObjectId));
								}

								$dWeek = \Carbon\Carbon::instance(\Core\Helper\DateTime::createFromLocalTimestamp($iTime));

								if(
									(
										$sType === 'course' ||
										$sType === 'accommodation'
									) &&
									$this->sCalculationType !== 'weekly' #&&
									#$oSpecial->period_type == 1
								) {

//									if($iTime == 0) {
//										throw new RuntimeException('$iTime == 0 for partial_service_period!');
//									}
									
									$dWeekEnd = \Carbon\Carbon::instance($dWeek);

									if(
										$oCourse instanceof Ext_Thebing_Tuition_Course && 
										$oCourse->price_calculation === 'month'
									) {
										
										$oBookingUntil = new \Carbon\Carbon($this->oObject->until);
										$oBookingUntil->endOfDay();
										
										$dWeekEnd->endOfMonth();
										
										// Falls Monat nicht komplett gebucht, auf Buchungsende setzen
										if($dWeekEnd > $oBookingUntil) {
											$dWeekEnd = $oBookingUntil;
										}
										
									} else {
										$dWeekEnd->addDays(4);
									}

									// Prüfen, ob Start der Woche + 4 Tage (muss nicht Montag sein) noch in den Zeitraum des Specials fallen
									// Die ganze Preisberechnung basiert auf Wochen und dem Startdatum der Leistung, nicht auf den tatsächlichen Zeiträumen!
									if(
										$oSpecial->service_period_calculation === 'partial_service_period' &&
										$dSpecialFrom &&
										$dSpecialUntil &&
										(
											!$dWeek->isBetween($dSpecialFrom, $dSpecialUntil) ||
											!$dWeekEnd->isBetween($dSpecialFrom, $dSpecialUntil)
										)
									) {
										$this->aCalculation[] = 'W'.$iTempPriceKey.': '.Ext_Thebing_Format::Number(0).' ('.$fPercent.'%, not in time frame)';
										break;
									}

									if($this->oSpecialPeriod->from) {
										$this->oSpecialPeriod->from = min($this->oSpecialPeriod->from, $dWeek);
									} else {
										$this->oSpecialPeriod->from = $dWeek;
									}
									if($this->oSpecialPeriod->until) {
										$this->oSpecialPeriod->until = max($this->oSpecialPeriod->until, $dWeekEnd);
									} else {
										$this->oSpecialPeriod->until = $dWeek;
									}

								}

								$this->aCalculation[] = 'W'.$iTempPriceKey.': '.Ext_Thebing_Format::Number($fAmount * -1).' ('.$fPercent.'%)';

								$aSpecialAmount[] = $fAmount;
								$aSpecialPeriods[$dWeek->toDateString()] += $fAmount;

								break;
							case 2: // Absolut

								$aBlockData = (array)$oSpecialBlock->getAdditionalData();

								// Buchungswährung
								$iCurrency = $oInquiry->getCurrency();

								// Entsprechender Betrag der Währung ermitteln der abgezogen werden soll
								foreach($aBlockData as $aData) {

									if(
										$aData['type'] == 'currency' &&
										$aData['type_id'] == $iCurrency &&
										empty($discountCalculated[$aData['block_id']])
									) {
										$aSpecialAmount[] = (float)$aData['value'];
										$aSpecialPeriods[$iTime] += (float)$aData['value'];

										$this->aCalculation[] = 'W'.$this->iCurrentCourseWeek.': '.Ext_Thebing_Format::Number($aData['value'] * -1).' (absolute value)';

										// Unterscheidung ob "absolut pro Woche" ODER "absolut einmalig"
										if(
											$oSpecialBlock->option_id == 1 ||
											$oSpecialBlock->option_id == 4 ||
											$oSpecialBlock->option_id == 5
										) {
											$discountCalculated[$aData['block_id']] = true;
										}
									}

								}

								break;
							case 3: // Kostenfreie Wochen ($oSpecial->amount_type == 3)

								// Als preis immer den unangetasteten Wochenpreis nehmen.
								if((int)$oSpecialBlock->weeks == (int)$this->iCurrentCourseWeek) {
									$fAmount = $fTempPrice * $oSpecialBlock->free_weeks;
									$aSpecialAmount[] = $fAmount;
									$aSpecialPeriods[$iTime] += $fAmount;
									$this->aCalculation[] = 'W'.$this->iCurrentCourseWeek.': '.Ext_Thebing_Format::Number($fAmount * -1).' ('.$oSpecialBlock->free_weeks.' weeks free of charge)';
								}

								break;
						}

						if(!empty($inquirySpecial->code)) {
							$this->specialCodes[$inquirySpecial->code->id] = $inquirySpecial->code;
						}

						// Special Block merken
						$oSpecialBlock->aSpecialAmountCalculation = $this->aCalculation;
						$this->_aSpecialBlocks[] = $oSpecialBlock->id;

					}

				}

				$iTempPriceKey++;

			}

		}
		
		$this->aSpecialPeriods = $aSpecialPeriods;

		// Alle Special-Block Beträge summieren
		$fSpecialAmount = array_sum($aSpecialAmount);

		return $fSpecialAmount;
	}

	/**
	 * @return array
	 */
	public function getCalculation() {
		return $this->aCalculation;
	}

	/**
	 * Kompatibilität zu Ext_Thebing_Course_Amount|Ext_Thebing_Accommodation_Amount
	 * @return array
	 */
	public function getSpecialBlocks():array {
		return $this->getBlocks();
	}
	
	/**
	 * Kompatibilität zu Ext_Thebing_Course_Amount|Ext_Thebing_Accommodation_Amount
	 * @return float
	 */
	public function getSpecialAmount():float {
		return $this->getAmount();
	}
	
}