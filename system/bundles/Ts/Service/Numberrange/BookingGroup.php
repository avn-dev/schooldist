<?php

namespace Ts\Service\Numberrange;

class BookingGroup extends \Ext_TS_NumberRange {

	protected $_sNumberTable = 'kolumbus_groups';

	/**
	 * Liefert das Numberrange-Objekt, welches für diese Klasse benutzt wird
	 * Dieses wird zentral bei den Nummernkreisen eingestellt.
	 * @static
	 * @return \Ext_TC_NumberRange|null
	 */
	public static function getObject(\Ext_TS_Group_Interface $oEntity) {

		$oInbox = \Ext_Thebing_Client_Inbox::getInstance($oEntity->inbox_id);

		self::setInbox($oInbox);
		$oNumberRange = self::getByApplicationAndObject('group', $oEntity->school_id);
		
		if($oNumberRange->id != 0) {
			return $oNumberRange;
		}

		return null;

	}

//	protected function executeSearchLatestNumber($sSql, $aSql) {
//
//		$aSql['table'] = 'kolumbus_groups';
//		$iLatestNumberBookings = parent::executeSearchLatestNumber($sSql, $aSql);
//
//		$aSql['table'] = 'ts_groups';
//		$iLatestNumberEnquiries = parent::executeSearchLatestNumber($sSql, $aSql);
//
//		return max($iLatestNumberBookings, $iLatestNumberEnquiries);
//
//	}
	
}
