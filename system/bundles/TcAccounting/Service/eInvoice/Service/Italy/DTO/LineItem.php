<?php

namespace TcAccounting\Service\eInvoice\Service\Italy\DTO;

class LineItem {
	
	const TYPE_STANDARD = 'standard';
	const TYPE_STANDARD_DISCOUNT = 'standard_discount';
	const TYPE_DISCOUNT = 'discount';
	
	protected $type;
	protected $item_type;
	protected $item_type_id;
	protected $description;
	protected $from;
	protected $until;
	protected $amount = 0;
	protected $vat_id = 0;
	protected $vat_rate = 0;
	protected $discount_percent = 0;
	protected $discount_amount = 0;
	protected $natura = 'N5';
	protected $quantity = 1;
	
	public function __construct(string $sType, string $sItemType, int $iItemTypeId, string $sDescription) {
		$this->type = $sType;
		$this->item_type = $sItemType;
		$this->item_type_id = $iItemTypeId;
		$this->description = $this->cleanName($sDescription);
	}
	
	public function getType() : string {
		return $this->type;
	}
	
	public function getItemType() : string {
		return implode('-', [strtoupper($this->item_type), $this->item_type_id]);
	}
	
	public function getDescription() {
		return $this->description;
	}
	
	public function setAmount(float $fAmount) : void {
		$this->amount = $fAmount;
	}
	
	public function getAmount() : float  {
		return $this->amount;
	}
	
	public function setVat(int $fVatId, float $fVatRate) : void {
		$this->vat_id = $fVatId;
		$this->vat_rate = $fVatRate;
	}
	
	public function getVatId() : int  {
		return $this->vat_id;
	}
	
	public function getVatRate() : int  {
		return $this->vat_rate;
	}
	
	public function setDiscountPercent(float $fDiscountPercent) : void {
		$this->discount_percent = $fDiscountPercent;
	}
	
	public function getDiscountPercent() : float  {
		return $this->discount_percent;
	}
	
	public function setDiscountAmount(float $fDiscountAmount) : void {
		$this->discount_amount = $fDiscountAmount;
	}
	
	public function getDiscountAmount() : float  {
		return $this->discount_amount;
	}
	
	public function setFrom(string $sFrom) : void {
		$this->from = $sFrom;
	}
	
	public function getFrom() : ?string {
		return $this->from;
	}
	
	public function setUntil(string $sUntil) : void {
		$this->until = $sUntil;
	}
	
	public function getUntil() : ?string {
		return $this->until;
	}
	
	public function setNatura(string $sNatura) : void {
		$this->natura = $sNatura;
	}
	
	public function getNatura() : string {
		return $this->natura;
	}
	
	public function setQuantity(int $iQuantity) : void {
		$this->quantity = $iQuantity;
	}
	
	public function getQuantity() : int {
		return $this->quantity;
	}
	
	private function cleanName($sName) {
		$sName = preg_replace("/\r|\n/", " ", $sName);
		$sName = strip_tags($sName);
		return $sName;
	}
}