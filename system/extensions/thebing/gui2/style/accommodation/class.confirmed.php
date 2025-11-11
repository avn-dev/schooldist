<?php

use TsAccommodation\Dto\Allocation\ConfirmationStatus;

class Ext_Thebing_Gui2_Style_Accommodation_Confirmed extends Ext_Gui2_View_Style_Abstract {

	protected $_sType = '';

	public function __construct($sType = '') {
		$this->_sType = $sType;
	}

	public function getStyle($mValue, &$oColumn, &$aRowData) {

		// Wenn es um Transferdaten geht, dann nur farblich kennzeichnen, wenn auch Transfer gebucht ist
		if(
			$this->_sType == 'accommodation_transfer_confirmed' &&
			$aRowData['transfer_mode'] == Ext_TS_Inquiry_Journey::TRANSFER_MODE_NONE
		){
			return '';
		}

		$iAllocationId			= (int)$aRowData['allocation_id'];
		$aActiveAllocations		= explode(',', $aRowData['active_accommodation_allocations'] ?? '');

		$getDate = function ($value) {
			return (is_numeric($value) && $value != 0)
				? \Carbon\Carbon::createFromTimestamp($value)
				: null;
		};

		$confirmDate = $getDate($mValue);
		$changedDate = $getDate($aRowData['accommodation_changed']);

		$color = ConfirmationStatus::getColorByValues($iAllocationId, $confirmDate, $changedDate, $aActiveAllocations);

		return ($color) ? sprintf('background-color: %s;', $color) : '';

	}


}