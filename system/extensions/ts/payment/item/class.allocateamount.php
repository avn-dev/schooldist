<?php

/**
 * Pendant zu JS PaymentGUI::allocateSumToAmountInputs(): Verfügbaren Betrag auf Items verteilen
 *
 * Wenn hier Anpassungen gemacht werden, muss das ggf. auch im JS gemacht werden!
 */
class Ext_TS_Payment_Item_AllocateAmount {

	/** @var Ext_Thebing_Inquiry_Document_Version_Item[] */
	private $aItems = [];

	/** @var float|int */
	private $fAmountToAllocate = 0;

	/** @var float[] */
	private $aAllocatedAmounts = [];

	/** @var bool Bezahlten Betrag umkomvertieren, nötig für Creditnote Payout und Verteilung eines negativen Betrags */
	public $bInvertPayedAmount = false;

	private int|float $fAmountOverpayment = 0;

	/**
	 * @param Ext_Thebing_Inquiry_Document_Version_Item[] $aItems
	 * @param float $fAmountToAllocate
	 */
	public function __construct(array $aItems, $fAmountToAllocate) {

		$this->aItems = array_filter($aItems, function(Ext_Thebing_Inquiry_Document_Version_Item $oItem) {
			// Deaktivierte Items dürfen nicht verrechnet werden
			return $oItem->onPdf;
		});
		
		$this->fAmountToAllocate = $fAmountToAllocate;

	}

	/**
	 * Zuzuweisenden Betrag auf die Items verteilen
	 *
	 * @return float[]
	 */
	public function allocateAmounts() {

		// Je nachdem, ob der Betrag positiv oder negativ ist, gibt es nicht nur eine Verteilungsart
		// TODO In 2.023 von > 0 auf >= 0 geändert, da Amount=0 nicht so wie im Dialog verteilt wurde (Betrag = 0)
		if($this->fAmountToAllocate >= 0) {
			$this->allocatePositiveAmount();
		} else {
			$this->allocateNegativeAmount();
		}

		// Ungenauigkeiten können bei komischen Gruppenbeträgen auftauchen
		if(abs($this->fAmountToAllocate) < 0.0001) {
			$this->fAmountToAllocate = 0;
		}

		$this->fAmountOverpayment += $this->fAmountToAllocate;

		return $this->aAllocatedAmounts;

	}

	/**
	 * Positiven Betrag verteilen:
	 * 1. Negative Items bezahlen und verfügbaren Betrag erhöhen
	 * 2. Verfügbaren Betrag verteilen
	 */
	private function allocatePositiveAmount() {

		$aItems = $this->aItems;

		/*
		 * Zuerst alle Items sammeln mit negativem Bezahlbetrag (z.B. unbezahlte Specials).
		 * Diese werden beim positiven Verteilen auf magische Weise bezahlt und der verfügbare Betrag wird erhöht
		 */
		foreach($aItems as $iKey => $oItem) {
			$fOpenAmount = $this->getItemOpenAmount($oItem);
			
			if($fOpenAmount < 0) {
				$this->fAmountToAllocate += $fOpenAmount * -1;
				$this->aAllocatedAmounts[$oItem->id] = $fOpenAmount;
				unset($aItems[$iKey]); // Entfernen, damit das nicht noch einmal durchlaufen wird
			}
		}

		// Verfügbaren Betrag auf alle Items verteilen, wo noch ein Betrag offen ist
		foreach($aItems as $iKey => $oItem) {

			$fOpenAmount = $this->getItemOpenAmount($oItem);
			$fAllocateToItem = 0;

			if($this->fAmountToAllocate >= $fOpenAmount) {
				// Item kann voll bezahlt werden
				$fAllocateToItem = $fOpenAmount;
				$this->fAmountToAllocate = bcsub($this->fAmountToAllocate, $fOpenAmount);
			} elseif(
				$this->fAmountToAllocate > 0 &&
				$this->fAmountToAllocate < $fOpenAmount
			) {
				// Item kann teilweise (noch) bezahlt werden
				$fAllocateToItem = $this->fAmountToAllocate;
				$this->fAmountToAllocate = 0;
			}

			$this->aAllocatedAmounts[$oItem->id] = $fAllocateToItem;

		}

	}

	/**
	 * Negativen Betrag verteilen:
	 * 	1. Overpayment ausbezahlen (sofern gesetzt)
	 * 	2. Negative Items ausbezahlen
	 * 	3. Bezahlung von bezahlten Items wegnehmen
	 */
	private function allocateNegativeAmount() {

		$aItems = $this->aItems;

		// Gleiche Logik wie im JavaScript: Overpayment zuerst ausbezahlen
		if (abs($this->fAmountOverpayment) > 0) {
			$fAmountOverpayment = $this->fAmountOverpayment * -1;
			if($this->fAmountToAllocate >= $fAmountOverpayment) {
				// Auszahlungsbetrag ist geringer als Overpayment, kann also nur mit Overpayment bezahlt werden
				$this->fAmountOverpayment = $this->fAmountToAllocate;
				$this->fAmountToAllocate = 0;
			} else {
				// Overpayment ist geringer als Auszahlungsbetrag: Overpayment aufzehren und weiter verteilen
				$this->fAmountOverpayment = $fAmountOverpayment;
				$this->fAmountToAllocate -= $fAmountOverpayment; // Overpayment vom zum verteilenden Betrag abziehen
			}
		}

		// Zuerst alle Items mit negativem Betrag ausbezahlen
		foreach($aItems as $iKey => $oItem) {

			$fOpenAmount = $this->getItemOpenAmount($oItem);
			$fAllocateToItem = 0;

			if($fOpenAmount < 0) {

				if($this->fAmountToAllocate <= $fOpenAmount) {
					// Item passt voll in den Auszahlungsbetrag
					$fAllocateToItem = $fOpenAmount;
					$this->fAmountToAllocate -= $fOpenAmount;
				} elseif(
					$this->fAmountToAllocate < 0 &&
					$this->fAmountToAllocate > $fOpenAmount
				) {
					// Item passt noch teilweise in den Auszahlungsbetrag
					$fAllocateToItem = $this->fAmountToAllocate;
					$this->fAmountToAllocate = 0;
				}

				$this->aAllocatedAmounts[$oItem->id] = $fAllocateToItem;
				unset($aItems[$iKey]); // Entfernen, damit das nicht noch einmal durchlaufen wird

			}

		}

		// Wenn noch zu verteilender Betrag übrig ist: Die Bezahlung von bezahlten Items wegnehmen
		foreach($aItems as $iKey => $oItem) {

			$fPayedAmount = $oItem->getPayedAmount($oItem->getDocument()->getCurrencyId());

			// Wenn Dialog positive Werte verarbeitet, aber intern alles negativ ist, muss der Betrag umgedreht werden…
			if($this->bInvertPayedAmount) {
				$fPayedAmount *= -1;
			}

			$fPayedAmountNegative = $fPayedAmount * -1;
			$fAllocateToItem = 0;

			if($fPayedAmount > 0) {

				if($this->fAmountToAllocate <= $fPayedAmountNegative) {
					// Bezahlter Betrag des Items passt vollständig in den übrigen Auszahlungsbetrag
					$fAllocateToItem = $fPayedAmountNegative;
					$this->fAmountToAllocate -= $fPayedAmountNegative;
				} elseif(
					$this->fAmountToAllocate < 0 &&
					$this->fAmountToAllocate > $fPayedAmountNegative
				) {
					// Bezahlter Betrag des Items passt noch teilweise in den übrigen Auszahlungsbetrag
					$fAllocateToItem = $this->fAmountToAllocate;
					$this->fAmountToAllocate = 0;
				}

			}

			$this->aAllocatedAmounts[$oItem->id] = $fAllocateToItem;

		}

	}

	/**
	 * Overpayment setzen: Nur relevant für Ausbezahlung/negative Beträge (analog zum JavaScript)
	 */
	public function setOverPayment(int|float $fAmount) {
		$this->fAmountOverpayment = $fAmount;
	}

	/**
	 * Wenn verfügbarer Betrag nach dem Verteilen übrig ist, ist das eine Überbezahlung
	 *
	 * @return bool
	 */
	public function hasOverPayment() {
		return abs($this->fAmountOverpayment) > 0;
	}

	/**
	 * @return float|int
	 */
	public function getOverPayment() {
		return $this->fAmountOverpayment;
	}

	/**
	 * @param Ext_Thebing_Inquiry_Document_Version_Item $oItem
	 * @return float
	 */
	private function getItemOpenAmount(Ext_Thebing_Inquiry_Document_Version_Item $oItem) {

		// Es muss immer mit aktiellen Werten gerechnet werden
		Ext_Thebing_Inquiry_Document_Version_Item::truncatePayedAmountCache();

		$fAmount = $oItem->getOpenAmount();

		// Ungenauigkeiten können bei komischen Gruppenbeträgen auftauchen
		if(abs($fAmount) < 0.0001) {
			$fAmount = 0;
		}

		return $fAmount;

	}

}