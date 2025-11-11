<?php

/**
 * DTO
 */
class Ext_TS_Document_PaymentCondition_Row {

	/**
	 * @var string
	 */
	public $sType;

	/**
	 * @var \DateTime
	 */
	public $dDate;

	/**
	 * @var int|float
	 */
	public $fAmount = 0;

	/**
	 * @var string
	 */
	public $sLabel;

	/**
	 * @var int
	 */
	public $iSettingId = 0;

	/**
	 * @var array
	 */
	public $aSettingData = [];

	/**
	 * @var bool
	 */
	public $bDisabled = false;

	public function getArray() {
		
		$array = [
			'setting_id' => $this->iSettingId,
			'setting_data' => $this->aSettingData
		];
		
		return $array;
	}
	
}
