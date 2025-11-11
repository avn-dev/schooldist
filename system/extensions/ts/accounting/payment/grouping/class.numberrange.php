<?php

class Ext_TS_Accounting_Payment_Grouping_Numberrange extends Ext_TS_NumberRange {

	protected $_sNumberTable = 'ts_inquiries_payments_groupings';
	protected $_sNumberField = 'number';

	/**
	 * Liefert das Numberrange-Objekt, welches für diese Klasse benutzt wird
	 * Dieses wird zentral bei den Nummernkreisen eingestellt.
	 * @static
	 * @return Ext_TC_NumberRange|null
	 */
	public static function getObject(\WDBasic $oEntity) {

		$oConfig = \Factory::getInstance('Ext_TC_Config');
		$mAgencyNumberRange = $oConfig->getValue('ts_payment_groupings_numbers');
		$mReturn = null;

		// Auf Ziffer prüfen
		if(is_numeric($mAgencyNumberRange)) {
			$oNumberRange = Ext_TS_Accounting_Payment_Grouping_Numberrange::getInstance($mAgencyNumberRange);
			if($oNumberRange->id != 0) {
				$mReturn = $oNumberRange;
			}
		}

		return $mReturn;

	}

}
