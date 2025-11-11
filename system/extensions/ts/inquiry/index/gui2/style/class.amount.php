<?php

class Ext_TS_Inquiry_Index_Gui2_Style_Amount extends Ext_Gui2_View_Style_Abstract implements Ext_Gui2_View_Style_Index_Interface {

	/**
	 * Statischer Cache, da diese Style-Klasse mehrfach pro Zeile aufgerufen wird
	 *
	 * @var array
	 */
	private static $aCache = [];

	/**
	 * Statische FÃ¤rbung (wird im Index gespeichert)
	 *
	 * @inheritdoc
	 */
	public function getIndexStyle($mValue, &$oColumn, &$aRowData) {

		if(
			$aRowData['amount_total_original'] > 0 &&
			$aRowData['amount_open_original'] <= 0
		) {
			// Komplett bezahlt
			return 'background-color: '.Ext_Thebing_Util::getColor('lightgreen').'; color: #000;';
		} elseif(
			$aRowData['amount_total_original'] > 0 &&
			$aRowData['amount_payed_original'] > 0
		) {
			// Anteilig bezahlt (wird ggf. unten überschrieben)
			return 'background-color: '.Ext_Thebing_Util::getColor('soft_green', 30).'; color: #000;';
		}

		return '';
	}

	/**
	 * Dynamische FÃ¤rbung (wird pro Zelle von createTable ausgefÃ¼hrt)
	 *
	 * @inheritdoc
	 */
	public function getStyle($mValue, &$oColumn, &$aRowData) {

		if(isset(self::$aCache[$aRowData['id']])) {
			return self::$aCache[$aRowData['id']];
		}

		self::$aCache[$aRowData['id']] = '';

		if(
			$aRowData['amount_total_original'] > 0 &&
			$aRowData['amount_open_original'] > 0 &&
			!empty($aRowData['paymentterms_next_date_original'])
		) {
			$dNow = new DateTime();
			$dDate = new DateTime($aRowData['paymentterms_next_date_original']);

			// Zahlung überfällig
			if($dDate < $dNow) {
				self::$aCache[$aRowData['id']] = 'background-color: '.Ext_Thebing_Util::getColor('red').'; color: #000;';
			}
		}

		return self::$aCache[$aRowData['id']];
	}

}
