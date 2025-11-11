<?php

namespace Ts\Service\Numberrange;

class Booking extends \Ext_TS_NumberRange {

	protected $_sNumberTable = 'ts_inquiries';
	protected $_sNumberField = 'number';

	/**
	 * Liefert das Numberrange-Objekt, welches für diese Klasse benutzt wird
	 * Dieses wird zentral bei den Nummernkreisen eingestellt.
	 * @static
	 * @return Ext_TC_NumberRange|null
	 */
	public static function getObject(\Ext_TS_Inquiry $oEntity) {

		$oInbox = \Ext_Thebing_Client_Inbox::getByShort($oEntity->inbox);

		self::setInbox($oInbox);
		$oNumberRange = self::getByApplicationAndObject('booking', $oEntity->getSchool()->id);
		
		if($oNumberRange->id != 0) {
			return $oNumberRange;
		}

	}

}
