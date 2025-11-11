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



class wdPDF_ExtendedDocument_InvoiceItem {


	protected $_aVariables = array();


	protected $_sEmptyValueReturn = '';


	protected $_sQuantityValueName = 'quantity';


	protected $_sAmountValueName = 'amount';


	protected $_sVatValueName = 'vat';


	/**
	 * Constructor.
	 *
	 * @return void
	 */
	public function __construct() {
		// placeholder, maybe something will be to do here later ;)
	}


	public function __set($sName, $sValue) {
		$this->setValue($sName, $sValue);
	}


	public function setValue($sName, $sValue) {

		// convert the passed arguments
		$sName  = (string)$sName;
		$sValue = (string)$sValue;

		// set the specified value
		$this->_aVariables[$sName] = $sValue;

	}


	public function __get($sName) {
		return $this->getValue($sName);
	}


	public function getValue($sName) {

		// convert the passed arguments
		$sName = (string)$sName;

		// return the value if it exists
		if (array_key_exists($sName, $this->_aVariables)) {
			return (string)$this->_aVariables[$sName];
		}

		// return an empty string by default
		return (string)$this->_sEmptyValueReturn;

	}


	public function setEmptyValueReturn($sValue) {
		$this->_sEmptyValueReturn = (string)$sValue;
	}


	public function getEmptyValueReturn() {
		return (string)$this->_sEmptyValueReturn;
	}


	public function setQuantityValueName($sName) {
		$this->_sQuantityValueName = (string)$sName;
	}


	public function getQuantityValueName() {
		return (string)$this->_sQuantityValueName;
	}


	public function getQuantity() {
		if (array_key_exists($this->_sQuantityValueName, $this->_aVariables)) {
			return (float)$this->_aVariables[$this->_sQuantityValueName];
		}
		return (float)0;
	}

	public function getDiscount() {
		return (float)$this->_aVariables['discount'];
	}

	public function setAmountValueName($sName) {
		$this->_sAmountValueName = (string)$sName;
	}


	public function getAmountValueName() {
		return (string)$this->_sAmountValueName;
	}


	public function getAmount() {
		if (array_key_exists($this->_sAmountValueName, $this->_aVariables)) {
			return (float)$this->_aVariables[$this->_sAmountValueName];
		}
		return (float)0;
	}


	public function setVatValueName($sName) {
		$this->_sVatValueName = (string)$sName;
	}


	public function getVatValueName() {
		return (string)$this->_sVatValueName;
	}


	public function getVat() {
		if (array_key_exists($this->_sVatValueName, $this->_aVariables)) {
			return (float)$this->_aVariables[$this->_sVatValueName];
		}
		return (float)0;
	}

	public function getTotalAmount() {

		$fTotalAmount = bcmul((float)$this->getAmount(), (float)$this->getQuantity(), 4);
		$fDiscount = 1 - $this->getDiscount()/100;
		
		$fTotalAmount = bcmul((float)$fTotalAmount, (float)$fDiscount, 4);
		
		return $fTotalAmount;
		
	}

}
