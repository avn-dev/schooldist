<?php

class Ext_Gui2_View_Format_Float extends Ext_Gui2_View_Format_Abstract {

	protected $iDecimals	= 2;
	protected $iDecPoint	= '.';
	protected $iThausendSep = ' ';

	/**
	 * Setzt die Einstellung zum Umwandel
	 * @param type $sDecimal
	 * @param type $iThousand 
	 */
	public function __construct($sDecimal=null, $iThousand=null) {

		// Autokonfiguration aus den Locales
		if(
			$sDecimal === null ||
			$iThousand === null
		) {

			$aLocaleConv = System::getLocaleConv();

			if($sDecimal === null) {
				$sDecimal = $aLocaleConv['decimal_point'];
			}
			if($iThousand === null) {
				$iThousand = $aLocaleConv['thousands_sep'];
			}

		}

		$this->iDecPoint	= $sDecimal;
		$this->iThausendSep = $iThousand;

	}
	
	/**
	 * Wandelt ein Float in einen String um entsprechend den gesetzten Trennzeichen.
	 * @param float $fAmount
	 * @param object $oColumn
	 * @param array $aResultData
	 * @return string
	 */
	public function format($fAmount, &$oColumn = null, &$aResultData = null){

		$fAmount = (float)$fAmount;
		// Formatieren
		$mAmount = number_format($fAmount, $this->iDecimals, $this->iDecPoint, $this->iThausendSep);

		return $mAmount;

	}
	
	/**
	 * Wandelt einen String in ein Float um entsprechend den gesetzten Trennzeichen.
	 * @param string $mValue
	 * @param object $oColumn
	 * @param array $aResultData
	 * @return float
	 */
	public function convert($mValue, &$oColumn = null, &$aResultData = null){

		$mValue = str_replace($this->iThausendSep, '', $mValue);

		$mValue = str_replace($this->iDecPoint, '.', $mValue);

		if(is_numeric($mValue)) {
			$mValue = (float)$mValue;
		}

		return $mValue;
	}

	public function align(&$oColumn = null){
		return 'right';
	}

}
