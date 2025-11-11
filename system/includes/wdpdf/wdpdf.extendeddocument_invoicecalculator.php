<?php


/*
 * -- webDynamics pdf classes --
 * Björn Goetschke <bg@plan-i.de>
 *
 * copyright by plan-i GmbH
 *
 * Include from: /system/includes/wdpdf/wdpdf.php
 *
 * 
 */



class wdPDF_ExtendedDocument_InvoiceCalculator {


	protected $_aVatValues = array();


	protected $_aVatQuantities = array();


	protected $_aVatAmountsPreTax = array();


	protected $_aVatAmountsAfterTax = array();


	protected $_aVatTaxAmounts = array();


	protected $_fTotalQuantity = 0;


	protected $_fTotalAmountPreTax = 0;


	protected $_fTotalAmountPreTaxAfterDiscount = 0;


	protected $_fTotalAmountAfterTax = 0;


	protected $_fTotalTaxAmount = 0;
	
	
	protected $_fTotalDiscountAmount = 0;


	public function __set($sName, $mValue) {
		throw new Exception('Unable to set property "'.$sName.'".');
	}


	public function __get($sName) {

		// convert the passed arguments
		$sName = (string)$sName;

		// return the specified value
		switch ($sName) {

			case 'vatValues':
				$aReturn = (array)$this->_aVatValues;
				return $aReturn;

			case 'vatQuantities':
				$aReturn = (array)$this->_aVatQuantities;
				return $aReturn;

			case 'vatAmountsPreTax':
				$aReturn = (array)$this->_aVatAmountsPreTax;
				return $aReturn;

			case 'vatAmountsAfterTax':
				$aReturn = (array)$this->_aVatAmountsAfterTax;
				return $aReturn;

			case 'vatTaxAmounts':
				$aReturn = (array)$this->_aVatTaxAmounts;
				return $aReturn;

			case 'totalQuantity':
				$fReturn = (float)$this->_fTotalQuantity;
				return $fReturn;

			case 'totalAmountPreTax':
				$fReturn = (float)$this->_fTotalAmountPreTax;
				return $fReturn;
			
			case 'totalAmountPreTaxAfterDiscount':
				$fReturn = (float)$this->_fTotalAmountPreTaxAfterDiscount;
				return $fReturn;

			case 'totalAmountAfterTax':
				$fReturn = (float)$this->_fTotalAmountAfterTax;
				return $fReturn;

			case 'totalTaxAmount':
				$fReturn = (float)$this->_fTotalTaxAmount;
				return $fReturn;
			
			case 'totalDiscountAmount':
				$fReturn = (float)$this->_fTotalDiscountAmount;
				return $fReturn;

		}

		// the specified property was not found
		throw new Exception('Unable to get property "'.$sName.'".');

	}


	public function __construct(wdPDF_ExtendedDocument $oDocument) {

		// get required configuration values
		$bAmountsPreTax = (bool)$oDocument->invoiceItemsPreTax;

		// process all invoice items
		foreach ($oDocument->invoiceItems as $oInvoiceItem) {
			
			// ignore group items (text only) if the amount is not displayed
			if ($oInvoiceItem->only_text && $oInvoiceItem->display_amount == $oInvoiceItem->getEmptyValueReturn()) {
				continue;
			}

            // ignore invoice positions that are hidden (amounts have been added to the group item)
            if (!$oInvoiceItem->only_text && $oInvoiceItem->hide_amount != $oInvoiceItem->getEmptyValueReturn()) {
                continue;
            }

			// add the vat values to the data arrays if required
			$fVat = (float)$oInvoiceItem->getVat();
			if (!$this->_vatEntryExists($fVat)) {
				$this->_aVatValues[]                                  = $fVat;
				$this->_aVatQuantities[$this->_getVatKey($fVat)]      = 0;
				$this->_aVatAmountsPreTax[$this->_getVatKey($fVat)]   = 0;
				$this->_aVatAmountsAfterTax[$this->_getVatKey($fVat)] = 0;
				$this->_aVatTaxAmounts[$this->_getVatKey($fVat)]      = 0;
			}

			// get the vat key
			$iVatKey = $this->_getVatKey($fVat);

			// get the quantity and amount values
			$fQuantity = (float)$oInvoiceItem->getQuantity();
			$fAmount   = (float)$oInvoiceItem->getTotalAmount();
			$fDiscount = (float)$oInvoiceItem->getDiscount();

			// add the quantity
			$this->_aVatQuantities[$iVatKey] += $fQuantity;
			$this->_fTotalQuantity           += $fQuantity;

			// add the amount
			if ($bAmountsPreTax == true) {
				$this->_aVatAmountsPreTax[$iVatKey] += $fAmount;
				$this->_fTotalAmountPreTax          += $fAmount;
			} else {
				$this->_aVatAmountsAfterTax[$iVatKey] += $fAmount;
				$this->_fTotalAmountAfterTax          += $fAmount;
			}

		}

		// calculate vat amount
		foreach ($this->_aVatValues as $fVat) {

			// get the vat key
			$iVatKey = $this->_getVatKey($fVat);

			// pre tax => after tax
			if ($bAmountsPreTax == true) {
				$fVatFactor                           = ($fVat / 100) + 1;
				$this->_aVatAmountsPreTax[$iVatKey]	 -= ($this->_aVatAmountsPreTax[$iVatKey]/100*$oDocument->invoiceDiscount);
				$this->_aVatAmountsAfterTax[$iVatKey] = $this->_aVatAmountsPreTax[$iVatKey] * $fVatFactor;
				$this->_fTotalAmountAfterTax         += $this->_aVatAmountsAfterTax[$iVatKey];
			}

			// after tax => pre tax
			else {
				$fVatDivisor                        = ($fVat / 100) + 1;
				$this->_aVatAmountsPreTax[$iVatKey] = $this->_aVatAmountsAfterTax[$iVatKey] / $fVatDivisor;
				$this->_fTotalAmountPreTax         += $this->_aVatAmountsPreTax[$iVatKey];
			}

			// tax amount for the current vat value
			$this->_aVatTaxAmounts[$iVatKey] = $this->_aVatAmountsAfterTax[$iVatKey] - $this->_aVatAmountsPreTax[$iVatKey];
			$this->_fTotalTaxAmount         += $this->_aVatTaxAmounts[$iVatKey];

		}

		// sub discount
		$this->_fTotalAmountPreTaxAfterDiscount = $this->_fTotalAmountPreTax - $this->_fTotalAmountPreTax*$oDocument->invoiceDiscount/100;
		$this->_fTotalDiscountAmount = $this->_fTotalAmountPreTax*$oDocument->invoiceDiscount/100;

	}


	public function getVatQuantity($fVat) {
		$fVat = (float)$fVat;
		if (!$this->_vatEntryExists($fVat)) {
			return (float)0;
		}
		return (float)$this->_aVatQuantities[$this->_getVatKey($fVat)];
	}


	public function getVatAmountPreTax($fVat) {
		$fVat = (float)$fVat;
		if (!$this->_vatEntryExists($fVat)) {
			return (float)0;
		}
		return (float)$this->_aVatAmountsPreTax[$this->_getVatKey($fVat)];
	}


	public function getVatAmountAfterTax($fVat) {
		$fVat = (float)$fVat;
		if (!$this->_vatEntryExists($fVat)) {
			return (float)0;
		}
		return (float)$this->_aVatAmountsAfterTax[$this->_getVatKey($fVat)];
	}


	public function getVatTaxAmount($fVat) {
		$fVat = (float)$fVat;
		if (!$this->_vatEntryExists($fVat)) {
			return (float)0;
		}
		return (float)$this->_aVatTaxAmounts[$this->_getVatKey($fVat)];
	}


	protected function _vatEntryExists($fVat) {
		$fVat = (float)$fVat;
		if (in_array($fVat, $this->_aVatValues)) {
			return true;
		}
		return false;
	}


	protected function _getVatKey($fVat) {
		$fVat = (float)$fVat;
		return array_search($fVat, $this->_aVatValues);
	}


}
