<?php

abstract class Ext_TS_Accounting_Provider_Grouping_Gui2_Format_PositionData extends Ext_TC_Gui2_Format {

	/**
	 * Liefert den Inhalt der Kommentarspalte, je nach Bedingung
	 * @param string $sColumn
	 * @param Ext_Thebing_Basic $oPayment
	 * @return string
	 */
	protected function _getCommentData($sColumn, $oPayment) {

		$sReturn = '';

		if($sColumn === 'payed_additional_comment') {

			// Kommentar nur anzeigen, wenn dies eine ChildPayment ist
			$iParentId = $oPayment->parent_id;
			if($iParentId > 0) {
				$sReturn = $oPayment->comment;
			}

		} else {

			// Wenn Payment ein AdditionalPayment ist, dann zeige hier ParentPayment-Kommentar
			// Transferbezahlungen haben keine Zusatzbezahlungen, also Exception der WDBasic abfangen
			try {
				$iParentId = $oPayment->parent_id;
			} catch(Exception $e) {
				$iParentId = 0;
			}

			if($iParentId > 0) {
				$sPaymentClass = get_class($oPayment);
				$oParentPayment = Ext_TC_Factory::getInstance($sPaymentClass, $iParentId);
				$sReturn = $oParentPayment->comment;
			} else {
				$sReturn = $oPayment->comment;
			}

		}

		return $sReturn;

	}

}