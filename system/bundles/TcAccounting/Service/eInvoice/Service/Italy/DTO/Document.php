<?php

namespace TcAccounting\Service\eInvoice\Service\Italy\DTO;

use TcAccounting\Service\eInvoice\Service\Italy\Exceptions\BuildException;

class Document {
	
	const CREDITNOTE = 'creditnote';
	
	const CANCELLATION = 'cancellation';
	
	protected $type;
	
	protected $number;
	
	protected $currency;
	
	protected $created;
	
	protected $file_data = [];
	
	protected $amount = 0;
	
	protected $items = [];
	
	protected $vat_items = [];
	
	protected $paymentterms = [];
	
	public function __construct(string $sType, string $sNumber, string $sCurrency, \DateTime $dDate) {
		$this->type = $sType;
		$this->number = $sNumber;
		$this->currency = $sCurrency;
		$this->created = $dDate;
	}
	
	public function getType() : string {
		return ($this->isCancellation() || $this->isCreditNote())
			? 'TD04'
			: 'TD01';
	}
	
	public function isCreditNote() : bool {
		return ($this->type === self::CREDITNOTE);
	}
	
	public function isCancellation() : bool {
		return ($this->type === self::CANCELLATION);
	}
	
	public function getNumber() : string {
		return $this->number;
	}
	
	public function getCurrency() : string {
		return $this->currency;
	}
	
	public function getDate() : \DateTime {
		return $this->created;
	}
	
	public function setFile(string $sFilePath, $sLabel = '', $sDescription = 'Disegno di legge') {
		if(!file_exists($sFilePath)) {
			throw (new BuildException('no_pdf'))->bindParameter($sLabel);
		}
		
		$sName = basename($sFilePath);
		
		if(strlen($sName) > 60) {
			$aFileName = explode('.', $sName);			
			$sName = substr($aFileName[0], 0, 56) . '.' . $aFileName[1];
		}
		
		$this->file_data = [
			'name' => $sName,
			'description' => $sDescription,
			'file_path' => $sFilePath,
		];
	}
	
	public function getFileData() : array {
		return $this->file_data;
	}
	
	public function setAmount(float $fAmount) : void {
		$this->amount = $fAmount;
	}
	
	public function getAmount() : float {
		return $this->amount;
	}
	
	public function addItem(LineItem $oItem) : void {
		$this->items[] = $oItem;
		
		$this->vat_items[$oItem->getVatId()][] = $oItem;
	}
	
	/**
	 * 
	 * @return \TcAccounting\Service\eInvoice\Service\Italy\DTO\LineItem
	 */
	public function getItems() : array {
		return $this->items;
	}
	
	public function getVatItems() : array {
		
		$aVatItems = [];
		
		foreach($this->vat_items as $vatId => $aGroupedItems) {
			$school = \Ext_Thebing_School::getSchoolFromSession();
			$aVat = [];
			$fAmount = 0;
			foreach($aGroupedItems as $oGroupedItem) {
				for ($i = 0; $i < $oGroupedItem->getQuantity(); $i++) {
					$fAmount = bcadd($fAmount, $oGroupedItem->getAmount(), 5);
				}
				$aVat['natura'] = $oGroupedItem->getNatura();
			}

			$aVat['amount'] = $fAmount;
			if (!empty($vatId)) {
				$aVat['rate'] = \Ext_TC_Vat::getInstance($vatId)?->getTaxRate($vatId, $school) ?? 0;
				// Benötigt für Xml Export (DatiRiepilogo).
				$aVat['note'] = \Ext_TC_Vat::getInstance($vatId)?->getNote($school->getInterfaceLanguage());
			}
			$aVatItems[] = $aVat;
		}
		
		return $aVatItems;
	}
	
	public function addPaymentTerm(\DateTime $dDueDate, float $fAmount) : void {
		$this->paymentterms[] = [
			'due_date' => $dDueDate,
			'amount' => $fAmount			
		];
	}
	
	public function getPaymentterms() : array {
		return $this->paymentterms;
	}
}

