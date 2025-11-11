<?php

class Ext_TS_Gui2_Format_Accommodation_Room extends Ext_Gui2_View_Format_Abstract {

	/**
	 * @var string
	 */
	private $sType = '';

	public function __construct($sType = '') {
		$this->sType = (string)$sType;
	}

	public function format($mValue, &$oColumn = null, &$aResultData = null) {

		$iRoomId = (int)$mValue;
		$oRoom = Ext_Thebing_Accommodation_Room::getInstance($iRoomId);

		switch($this->sType) {
			case 'room_type_name':
				return $oRoom->getType()->getName();
		}

		return $oRoom->getName();

	}

}
