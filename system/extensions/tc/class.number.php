<?
/**
 * Klasse um Zahlen zu formatieren und Convertieren
 */
class Ext_TC_Number {
	
	public static function createNumberFormatPoints($iFormat, &$e, &$t){
		
		if($iFormat == 1){
			$t = ",";            // 1,234.56
			$e = ".";
		} elseif($iFormat == 2) {
			$t = " ";            // 1 234.56
			$e = ".";
		} elseif($iFormat == 3) {
			$t = " ";            // 1 234,56
			$e = ",";
		} elseif($iFormat == 4) {
			$t = "'";            // 1'234.56
			$e = ".";
		} else {
			$t = ".";            // 1.234,56
			$e = ",";
		}
	}
	
	public static function format($fAmount, $iCurrency = 0, $iDecimalPlaces = 2){

		$iFormat = Ext_TC_Factory::executeStatic('Ext_TC_Number', 'getNumberFormatSettings');

		$bDotZeros = true;

		$t = ".";
		$e = ",";

		// Format heraussuchen
		self::createNumberFormatPoints($iFormat, $e, $t);

		// Wenn die Kommastellen nicht aufgefüllt werden sollen
		if ( !$bDotZeros ) {
			// Wenn der Kommawert gleich null ist ( ,00 )
			if ( 
					((float)$fAmount - (int)$fAmount) == 0 
			) {
				$iDecimalPlaces = 0;
			}
		}

		// Auf die entsprechenden Stellen Runden
		$fAmount = round((float)$fAmount, $iDecimalPlaces);

		// Formatieren
		$sBack = number_format($fAmount,$iDecimalPlaces,$e,$t);

		// Wenn die Kommastellen nicht mit nullen aufgefüllt sein sollen
		if(!$bDotZeros){

			$aTemp = explode($e, $sBack);
			$aTemp[1] = rtrim($aTemp[1], "0");

			$sBack = $aTemp[0];
			if($aTemp[1] != ''){
				$sBack .= $e.$aTemp[1];
			}
			
		}

		// Wenn eine Währung mit angegeben ist
		if($iCurrency > 0){
			
			$oCurrency = Ext_TC_Currency::getInstance($iCurrency);

			// Dollar/Pfund soll IMMER vor der Zahl stehen
			if(
				$iCurrency == 2 || 
				$iCurrency == 8
			){
				$sBack = $oCurrency->sign . " " . $sBack;
			} else {
				$sBack = $sBack." ".$oCurrency->sign;
			}
			
		}
		
		return $sBack;
	}
	
	public static function convert($sAmount){
		
		$iFormat = Ext_TC_Factory::executeStatic('Ext_TC_Number', 'getNumberFormatSettings');		

		$t = ".";
		$e = ",";

		// Format heraussuchen
		self::createNumberFormatPoints($iFormat, $e, $t);

		$sAmount = trim($sAmount);

		$aFormat = array();
		$bFormat = preg_match('/^([\-]?)(([0-9]+)([\s'.$t.']?[0-9]{3})*)(\\'.$e.'([0-9]+))?$/', $sAmount, $aFormat);

		// Wenn der Wert nicht konvertiert werden kann
		if(!$bFormat) {
			return $sAmount;
		}

		$sRegex = '/[^\-0-9]/';
		$aFormat[2] = preg_replace($sRegex, '', $aFormat[2]);

		$sAmount = $aFormat[1].$aFormat[2].'.'.$aFormat[6];

		$fFloat = floatval($sAmount);

		return $fFloat;
		
	}
	
	public static function getNumberFormatSettings() {
		$oConfig = \Factory::getInstance('Ext_TC_Config');
		$iFormat = $oConfig->getValue('number_format');
		
		return $iFormat;
	}
	
}
