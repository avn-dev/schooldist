<?php

class Ext_Thebing_Gui2_Format_Transfer_Locationname extends Ext_Gui2_View_Format_Abstract {

	protected $_sType = '';

	public function __construct($sType = 'start'){
		$this->_sType = $sType;
	}

	public function format($mValue, &$oColumn = null, &$aResultData = null) {

		if($aResultData['inquiry_transfer_id'] > 0) {

			$oTransfer = Ext_TS_Inquiry_Journey_Transfer::getInstance($aResultData['inquiry_transfer_id']);
			$sName = $oTransfer->getLocationName($this->_sType, true);

			return $sName;

		}

	}

}