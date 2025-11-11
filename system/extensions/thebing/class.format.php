<?php


class Ext_Thebing_Format {

	/**
	 * @param float|int $fValue
	 * @param null $iCurrencyId
	 * @param null $iSchool
	 * @return string
	 * @throws Exception
	 */
	public static function Int($fValue = 0,$iCurrencyId = null, $iSchool = null){
		global $session_data;

		// Diese Abfrage wurde hinzugefügt da es dazu kam dass $fValue den Wert 4 als String beinhaltet hat.
		// Oder auch rfz als Value bekomme...
		if(is_numeric($fValue)) {
			$fValue = (float)$fValue;
		} else {
			$fValue = 0;
		}

		if(empty($iSchool)) {
			$oSchool = Ext_Thebing_School::getSchoolFromSession();
		} else {
			$oSchool = Ext_Thebing_School::getInstance($iSchool);
		}
		
		$t = ".";
		$e = ",";
		$iFormat = $oSchool->number_format;
		if($iFormat == 1){
			$t = ",";
			$e = ".";
		} elseif($iFormat == 2) {
			$t = " ";
			$e = ".";
		} elseif($iFormat == 3) {
			$t = " ";
			$e = ",";
		} elseif($iFormat == 4) {
			$t = "'";
			$e = ".";
		}

		$iBack = number_format($fValue, 0, $e, $t);
		if($iCurrencyId != null){
			$oCurrency = Ext_Thebing_Currency_Util::getInstance($oSchool);
			
			$oCurrency->setCurrencyById($iCurrencyId);
			
			$iBack = $iBack." ".$oCurrency->getSign();
			
		}

		return $iBack;

	}
	
	public static function createNumberFormatPoints($oSchool, &$e, &$t)
	{
		$iFormat = $oSchool->number_format;

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
		}
	}

	public static function roundBySchoolSettings(float $amount) {
		
		$decimalPlaces = 2;
		if(\System::getInterface() !== 'backend') {
			$school = Ext_Thebing_School::getSchoolFromSessionOrFirstSchool();
			$decimalPlaces = (int)$school->decimal_place;
		}

		return round($amount, $decimalPlaces);
	}
	
	/**
	 * @param int|float $iValue
	 * @param int|Ext_Thebing_Currency $mCurrency
	 * @param int|Ext_Thebing_School $mSchool
	 * @param bool $bDotZeros
	 * @param int $iDecimalPlaces
	 * @return string
	 * @throws Exception
	 */
	public static function Number($iValue = 0, $mCurrency = null, $mSchool = null, $bDotZeros = true, $iDecimalPlaces = null) {

		if($iValue == '') {
			$iValue = 0;
		}
		
		if(is_string($iValue)) {
			$iValue = (float)$iValue;
		}
		
		if(empty($mSchool)) {
			if(Ext_Thebing_System::isAllSchools()) {				
				$oSchool = Ext_Thebing_School::getFirstSchool();
			} else {
				$oSchool = Ext_Thebing_School::getSchoolFromSession();
			}
		} else {
			$oSchool = $mSchool;
			if(!$oSchool instanceof Ext_Thebing_School) {
				$oSchool = Ext_Thebing_School::getInstance($mSchool);
			}
		}

		if(is_object($oSchool)) {

			if(
				is_null($iDecimalPlaces) &&
				\System::getInterface() !== 'backend'
			) {
				$iDecimalPlaces = (int)$oSchool->decimal_place;
			}

			if(is_null($iDecimalPlaces))
			{
				$iDecimalPlaces = 2;
			}
			else if($iDecimalPlaces <= 0) {
				$iDecimalPlaces = 0;
			}

			$t = ".";
			$e = ",";

			self::createNumberFormatPoints($oSchool, $e, $t);

			if ( !$bDotZeros ) {
				if ( ((float)$iValue - (int)$iValue) == 0 ) {
					$iDecimalPlaces = 0;
				}
			}

			// Damit auch Zahlen wie -0.00054 -> 0.00 ergeben und nicht -0.00
			$iValue = round($iValue, $iDecimalPlaces);

			if($iValue == 0){
				$iValue = 0;
			}

			$iBack = number_format($iValue, $iDecimalPlaces, $e, $t);

			if(!$bDotZeros){

				$aTemp = explode($e, $iBack);
				$aTemp[1] = rtrim($aTemp[1] ?? '', "0");

				$iBack = $aTemp[0];
				if($aTemp[1] != ''){
					$iBack .= $e.$aTemp[1];
				}
			}

			if($mCurrency !== null) {

				$oCurrency = $mCurrency;
				if(!$mCurrency instanceof Ext_Thebing_Currency) {
					$oCurrency = Ext_Thebing_Currency::getInstance($mCurrency);
				}

				//siehe t-3380
				#$oCurrency = Ext_Thebing_Currency_Util::getInstance($oSchool);
				#$oCurrency->setCurrencyById($iCurrencyId);

				$oCurrency->addSign($iBack);

			}
	
			return $iBack;
		} else {
			return 'Error - no School';
		}

	}
	
	public static function LessonsNumber($mValue = 0, $idSchool = 0){
	    return self::Number(
	        $mValue, 
	        null, 
	        $idSchool,
	        false,
			4
	    );
	}
	
	public static function getJSNumberFunction($iSchool = null){
		global $session_data;

		$sJavaScript = "";

		if(empty($iSchool)) {
			$oSchool = Ext_Thebing_School::getSchoolFromSession();
		} else {
			$oSchool = Ext_Thebing_School::getInstance($iSchool);
		}
		
		$iDecimalPlaces = 2;
		
		if(\System::getInterface() !== 'backend'){
			$iDecimalPlaces = $oSchool->decimal_place;
		}
		
		if($iDecimalPlaces <= 0){
			$iDecimalPlaces = 0;
		}
		
		$t = ".";
		$e = ",";
		$iFormat = $oSchool->number_format;
		if($iFormat == 1){
			$t = ",";
			$e = ".";
		} elseif($iFormat == 2) {
			$t = " ";
			$e = ".";
		} elseif($iFormat == 3) {
			$t = " ";
			$e = ",";
		} elseif($iFormat == 4) {
			$t = "'";
			$e = ".";
		}

		$sJavaScript .= 'function thebing_number_format(fValue) { return fValue.number_format('.(int)$iDecimalPlaces.', \''.addslashes($e).'\', \''.addslashes($t).'\'); }';

		return $sJavaScript;

	}

	/*
	 * Wandelt eine nach Schulformat formatierte Zahl in ein Float um
	 */
	static public function convertFloat($sFloat, $iSchoolId = null, $iFormat=false) {

		if(is_float($sFloat)){
			return $sFloat;
		}elseif(is_int($sFloat)){
			return $sFloat;
		}

		if(empty($iSchoolId)) {
			if(Ext_Thebing_System::isAllSchools()) {				
				$oSchool = Ext_Thebing_School::getFirstSchool();
			} else {
				$oSchool = Ext_Thebing_School::getSchoolFromSession();
			}
		} else {
			$oSchool = Ext_Thebing_School::getInstance($iSchoolId);
		}

		if($iFormat){
			$aNumberFormat	= $oSchool->getNumberFormatData($iFormat);
		}else{
			$aNumberFormat	= $oSchool->getNumberFormatData();
		}

		$sTrimAmount	= $aNumberFormat['t'];
		$sTrimDecimal	= $aNumberFormat['e'];
		
		$sFloat = trim($sFloat);

		$bFormat = preg_match('/^([\-]?)(([0-9]+)([\s'.$sTrimAmount.']?[0-9]{3})*)(\\'.$sTrimDecimal.'([0-9]+))?$/', $sFloat, $aFormat);

		// Wenn der Wert nicht konvertiert werden kann
		if(!$bFormat) {
			return (float)$sFloat; // Muss ein float sein, da man ausgeht, dass man mit dem Rückgabewert rechenn kann (PHP 7)
		}

		$sRegex = '/[^\-0-9]/';
		$aFormat[2] = preg_replace($sRegex, '', $aFormat[2]);

		$sFloat = $aFormat[1].$aFormat[2].'.'.$aFormat[6];

		$fFloat = floatval($sFloat);

		return $fFloat;
	
	}
	
	public static function getFormat($iSchool = null, $bShort = false) {

		if(empty($iSchool)) {
			$oSchool = Ext_Thebing_School::getSchoolFromSession();
		} else {
			$oSchool = Ext_Thebing_School::getInstance($iSchool);
		}
		
		if($bShort) {
			$sFormat = $oSchool->date_format_short;
		} else {
			$sFormat = $oSchool->date_format_long;
		}
	
		if(
			$sFormat == '' || 
			is_numeric($sFormat)
		){
			$sFormat = '%d.%m.%Y';
		}

		return $sFormat;
	}

	/**
	 * @see \Ext_Thebing_School_Proxy::getDateFormat()
	 *
	 * @param ?Ext_Thebing_School|int $iSchool
	 * @param string|mixed|bool $mType
	 * @return array|mixed|string|string[]
	 */
	public static function getDateFormat($iSchool = null, $mType=false) {

		if(empty($iSchool)) {
			$oSchool = Ext_Thebing_Client::getFirstSchool(); 
		} else {
			$oSchool = $iSchool instanceof Ext_Thebing_School ? $iSchool : Ext_Thebing_School::getInstance($iSchool);
		}

		if(is_object($oSchool)) {

			if(is_string($mType) && $mType == 'frontend_date_format') {
				$sFormat = $oSchool->frontend_date_format;
				if(empty($sFormat)) {
					$sFormat = $oSchool->date_format_long;
				}
			} elseif(
				$mType === true ||
				$mType === 'backend_datepicker_format_short'
			) {
				// Kompatiblität: War vorher $bShort und dieser Fall ist true
				$sFormat = $oSchool->date_format_short;
			} else {
				// $bShort false-Fall
				$sFormat = $oSchool->date_format_long;
			}

			if(
				$sFormat == "" ||
				is_numeric($sFormat)
			) {
				$sFormat = "%x";
			}

			if(is_string($mType)) {
				Util::convertDateFormat($sFormat, $mType);
			}

		}

		return $sFormat;
	}

	/**
	 * Datum in lokales Datumsformat (der Schule) umwandeln
	 *
	 * @param int|DateTimeInterface $mValue
	 * @param int $iSchool
	 * @param bool $bShort
	 * @return int|string
	 */
	public static function LocalDate($mValue, $iSchool = null, $bShort=false){

		if(empty($iSchool)) {
			$school = \Ext_Thebing_School::getSchoolFromSessionOrFirstSchool();
			$iSchool = $school->id;
		}

		// Kompatibilität
		// Achtung: Bei »lokalen Timestamps«, wo die ganze Software damit arbeitet, kann es hier Probleme geben!
		if($mValue instanceof DateTimeInterface) {
			$mValue = $mValue->getTimestamp();
		}

		//@mf... warum hast du hier die Bedingung von "$iValue == 0" auf "$iValue < 0" verändert? 
		//meintest du eher "$iValue <= 0" oder gibt es da doch irgendnen grund dafür?
		if($mValue <= 0) {
			return '';
		}

		$oFormat = new Ext_Thebing_Gui2_Format_Date($bShort);
		$oDummy = null;
		$aResultData = array('school_id' => $iSchool);

		$sBack = $oFormat->format($mValue, $oDummy, $aResultData);

		return $sBack;

	}

	public static function LocalDateTime($iValue, $iSchool= 0) {
		$mDate = self::LocalDate($iValue, $iSchool).' '.self::LocalTime($iValue, $iSchool);
		return $mDate;
	}

	/**
	 * Uhrzeit in lokales Datumsformat (der Schule) umwandeln
	 *
	 * @param int|DateTime $mValue
	 * @param int $iSchool
	 * @return bool|string
	 */
	public static function LocalTime($mValue, $iSchool= 0){

		// Kompatibilität
		if($mValue instanceof DateTime) {
			$mValue = $mValue->getTimestamp();
		}

		if(!is_numeric($mValue)) {
			$bIsDate = WDDate::isDate($mValue, WDDate::DB_DATETIME);
			if($bIsDate === true) {
				$oDate = new WDDate($mValue, WDDate::DB_DATETIME);
				$mValue = $oDate->get(WDDate::TIMESTAMP);
			}
		}
		
		if($mValue == 0){
			return '';
		}
		
		$sBack = date("H:i", (int)$mValue);
		
		return $sBack;

	}
	

	/**
	 * 
	 * @param	string	$sValue
	 * @param	INTEGER	$iSchool
	 * @param	BOOLEAN|INTEGER	$iFormat		return fomat: true|1 = mySQL-Format, 2 = ARRAY, other = UNIX timestamp
	 */
	public static function ConvertDate($sValue, $iSchool = null, $iFormat=false, $bReturnOriginalIfNotConverted=false){

		if($sValue == '') {
			if($iFormat == 3) {
				return null;
			}

			return '';
		}

		// Fals es eine Zahl ist(Timestamp) gebe es direkt zurück
		if(is_numeric($sValue)){
			return (int)$sValue;
		}

		if ($iSchool instanceof \Ext_Thebing_School) {
			$oSchool = $iSchool;
		} else if(empty($iSchool)) {
			$oSchool = Ext_Thebing_Client::getFirstSchool();
		} else {
			$oSchool = Ext_Thebing_School::getInstance($iSchool);
		}

		$sFormat = $oSchool->date_format_long;
		if($sFormat == "" || is_numeric($sFormat)){
			$sFormat = "%x";
		}

		$aBack = strptime($sValue, $sFormat);

		if($iFormat === true || $iFormat === 1) {
			if($aBack) {
				$sDate = sprintf("%04d-%02d-%02d", $aBack['tm_year']+1900, $aBack['tm_mon']+1, $aBack['tm_mday']);
			} else {
				if($bReturnOriginalIfNotConverted){
					$sDate = $sValue;
				}else{
					$sDate = '';
				}
			}
			
			return $sDate;
			
		} else if( $iFormat === 2 ) {
			
			if($aBack) {

				$aBack['tm_year'] = $aBack['tm_year']+1900;
				$aBack['tm_mon']  = $aBack['tm_mon']+1;

				return $aBack;

			} else {
				
				if($bReturnOriginalIfNotConverted){
					return $sValue;
				}else{
					return false;
				}
				
			}
			
		// DateTime Object
		} else if( $iFormat === 3 ) {
			
			$oDateTime = new DateTime();
			$oDateTime->setDate($aBack['tm_year']+1900, $aBack['tm_mon']+1, $aBack['tm_mday']);
			$oDateTime->setTime(0, 0, 0);
			
			return $oDateTime;
			
		// GMT Unix Timestamp
		} else if( $iFormat === 4 ) {

			$iBack = gmmktime(0, 0, 0, $aBack['tm_mon']+1, $aBack['tm_mday'], $aBack['tm_year']+1900);

			return $iBack;
			
		} else {
			
			$iBack = mktime(0, 0, 0,$aBack['tm_mon']+1,$aBack['tm_mday'],$aBack['tm_year']+1900);
			
			if((int)$iBack <= 0 && $iSchool > 0){
				// Warum geben wir die Zahl nicht direkt zurück?
				// oben wir das doch per return gemacht wenn eine "Zahl" übergeben wurde..
				// weshalb also nochmal die methode starten? (cw)
				//$iBack = self::ConvertDate((int)$iBack);

				return (int)$iBack;
			}
			
			return (int)$iBack;
			
		}
			
	}
	
	public static function ConvertDateTime($sValue, $iSchool=null, $iFormat=false) {

		if($sValue == ''){
			return 0;
		}
		
		// Fals es eine Zahl ist(Timestamp) gebe es direkt zurück
		if(is_numeric($sValue)){
			return $sValue;
		}

		if(empty($iSchool)) {
			$oSchool = Ext_Thebing_School::getSchoolFromSession();
		} else {
			$oSchool = Ext_Thebing_School::getInstance($iSchool);
		}
		
		$sFormat = $oSchool->date_format_long;
		if($sFormat == "" || is_numeric($sFormat)){
			$sFormat = "%x";
		}

		$aBack = strptime($sValue,$sFormat." %H:%M:%S");

		if($iFormat === true || $iFormat === 1) {
			if($aBack) {
				$sDate = sprintf("%04d%02d%02d%02d%02d%02d", $aBack['tm_year']+1900, $aBack['tm_mon']+1, $aBack['tm_mday'], $aBack['tm_hour'], $aBack['tm_min'], $aBack['tm_sec']);
			} else {
				$sDate = '';
			}
			return $sDate;
			
		} else if( $iFormat === 2 ) {
			
			$aBack['tm_year'] = $aBack['tm_year']+1900;
			$aBack['tm_mon']  = $aBack['tm_mon']+1;
			return $aBack;
			
		} else {
			$iBack = mktime($aBack['tm_hour'],$aBack['tm_min'],$aBack['tm_sec'],$aBack['tm_mon']+1,$aBack['tm_mday'],$aBack['tm_year']+1900);
			return $iBack;
			
		}

	}
	
	public static function BirthdayDate($iDay, $iMonth, $iYear, $iSchool = null) {

		if(empty($iSchool)) {
			$oSchool = Ext_Thebing_School::getSchoolFromSession();
		} else {
			$oSchool = Ext_Thebing_School::getInstance($iSchool);
		}

		$sFormat = $oSchool->date_format_long;

		if($sFormat == "" || is_numeric($sFormat)){
			$sFormat = "%d.%m.%y";
		}
		$sFormat = strtolower($sFormat);
		$sFormat = str_replace('%d', $iDay, $sFormat);
		$sFormat = str_replace('%m', $iMonth, $sFormat);
		$sFormat = str_replace('%y', $iYear, $sFormat);
			
		return $sFormat;

			
	}
	
	public static function getCurrencyFactor ($iFromCurrency,$iToCurrency){
		if($iFromCurrency == $iToCurrency){
			return 1;
		}
		$oCurrency = Ext_Thebing_Currency::getInstance($iFromCurrency);
		return $oCurrency->getConversionFactor($iToCurrency);
	}
	
	public static function ConvertAmount($fAmount, $iFromCurrency, $iToCurrency, $iSchool = null, $sDate = null) {
				
		$fAmountFrom = Ext_Thebing_Format::convertFloat($fAmount, $iSchool);
		$oCurrency = Ext_Thebing_Currency::getInstance($iFromCurrency);
		$fConvertAmount = $oCurrency->convertAmount($fAmountFrom, $iToCurrency, $sDate);

		//Ext_Thebing_Format::Number($fConvertAmount,0,$iSchool);
		return $fConvertAmount;
	}

	public static function getFilesize($iSize) {
		$units = array(' B', ' KB', ' MB', ' GB', ' TB');
		for($i = 0; $iSize > 1024; $i++) {
			$iSize /= 1024;
		}
		return self::Number($iSize).$units[$i];
	}

	/**
	 * @param scalar $mValue
	 * @return boolean
	 */
	public static function parseBooleanValue($mValue, $aMapping=null) {
		
		$mValue = mb_strtolower($mValue);

		if($aMapping === null) {
			$aMapping = [
				false,
				true
			];
		}
		
		if(
			$mValue === 'yes' ||
			$mValue === 'true' ||
			$mValue === 'si' ||
			$mValue === 'sí' ||
			$mValue === 'oui' ||
			$mValue === 'ja' ||
			$mValue === 'wahr' ||
			$mValue === 'j' ||
			$mValue === 'y' ||
			$mValue === '1' ||
			$mValue === 1 ||
			$mValue === true
		) {
			$bReturn = 1;
		} else {
			$bReturn = 0;
		}
		
		return $aMapping[$bReturn];
	}
	
	public static function parseExcelDate($sDate) {
		
		$oFormat = new Ext_Thebing_Gui2_Format_Date();

		$sDate = $oFormat->convert($sDate);
		
		return $sDate;
	}
	
	public static function parseCountry($sCountry) {

		$aCountries = Data_Countries::search($sCountry);

		if(!empty($aCountries)) {
			return $aCountries[0]['cn_iso_2'];
		}

	}
	
	public static function getArrayValue($mValue, $aArray) {
		if(isset($aArray[$mValue])) {
			return $aArray[$mValue];
		}
	}
	
}
