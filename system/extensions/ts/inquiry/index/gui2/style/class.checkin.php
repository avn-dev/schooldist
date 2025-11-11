<?php

class Ext_TS_Inquiry_Index_Gui2_Style_Checkin extends Ext_Gui2_View_Style_Abstract implements Ext_Gui2_View_Style_Index_Interface {

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

		if(isset($aRowData['checkin']) && $aRowData['checkin'] !== null) {
			return 'background-color: '.Ext_Thebing_Util::getColor('lightgreen').';color:#000000;';
		}

		return '';

	}

	/**
	 * Dynamische FÃ¤rbung (wird pro Zelle von createTable ausgefÃ¼hrt)
	 *
	 * @inheritdoc
	 */
	public function getStyle($mValue, &$oColumn, &$aRowData) {

	}

}
