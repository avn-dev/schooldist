<?php

class Ext_TC_Gui2_Format_Float extends Ext_Gui2_View_Format_Abstract {
	
	private $iDecimalPlaces;
	
	/**
	 * @param int $iDecimalPlaces
	 */
	public function __construct($iDecimalPlaces = 2) {
		$this->iDecimalPlaces = $iDecimalPlaces;
	}

	/**
	 * @param $mValue
	 * @param null $oColumn
	 * @param null $aResultData
	 * @return string
	 */
	public function format($mValue, &$oColumn = null, &$aResultData = null) {

		$mValue = Ext_TC_Factory::executeStatic('Ext_TC_Number', 'format', array($mValue, 0, $this->iDecimalPlaces));

		return $mValue;
	}

	/**
	 * Wird hier eingesetzt, damit das abspeichern mit "," statt "." funktioniert, da er vorher nicht in die Methode gesprungen ist und es somit zu Fehlern kam.
	 *
	 * @param $mValue
	 * @param null $oColumn
	 * @param null $aResultData
	 * @return float
	 */
	public function convert($mValue, &$oColumn = null, &$aResultData = null) {

		$mValue = Ext_TC_Factory::executeStatic('Ext_TC_Number', 'convert', array($mValue));

		return $mValue;
	}

	/**
	 * @param null $oColumn
	 * @return string
	 */
	public function align(&$oColumn = null){
		return 'right';
	}

}
