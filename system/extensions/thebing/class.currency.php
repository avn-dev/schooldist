<?php

/**
 * @TODO Das ist doch riesiger Müll, dass es hier zwei Tabellen gibt und in der Schule immer noch IDs verwendet werden!
 */
class Ext_Thebing_Currency extends Ext_TC_Currency {

	public $bThinspaceSign = false;

	// Tabellenname
	protected $_sTable = 'kolumbus_currency';

	protected static $sCurrencyFormat = null;

	/**
	 * Direkt Parent-Parent aufrufen, da Parent mit ISO-Code arbeitet
	 *
	 * @param int $iDataId
	 * @param string $sTable
	 */
	public function __construct($iDataId = 0, $sTable = null) {
		Ext_TC_Basic::__construct($iDataId, $sTable = null);
	}

	public function getIso(){
		return $this->_aData['iso4217'];
	}

	public function getIsoNum(){
		return $this->_aData['iso4217_num'];
	}
	
	public function getSign(){
		if($this->_aData['sign'] == ""){
			$this->_aData['sign'] = $this->_aData['iso4217'];
		}
		return $this->_aData['sign'];
	}

	public function getCurrencyId(){
		return $this->_aData['id'];
	}

	public function __get($sField){
		
		Ext_Gui2_Index_Registry::set($this);
		
		if($sField == 'factor'){
			return $this->getFactor();
		}
		return $this->_aData[$sField];
	}

	/**
	 * Gibt Den Umrechnungsfaktor Währung -> Währung zurück
	 * Falls ein Fester Kurs angegeben wurde wird dieser zurückgegeben
	 * @return float
	 */
	public function getConversionFactor($iToCurrency, $sDate = null) {

		if(
			$iToCurrency <= 0 ||
			$iToCurrency == $this->id
		) {
			return 1;
		}

		$fFromCurrencyFactor = $this->getFactor($sDate);

		$oToCurrency = Ext_Thebing_Currency::getInstance($iToCurrency);
		$fToCurrencyFactor = $oToCurrency->getFactor($sDate);

		$fFactor = $fToCurrencyFactor / $fFromCurrencyFactor;

		return $fFactor;

	}

	public function getFactor($sDate=null) {

		if($sDate === null) {
			$sDate = date('Y-m-d');
		} else {
			if(!\Core\Helper\DateTime::isDate($sDate, 'Y-m-d')) {
				throw new InvalidArgumentException('Invalid date given: '.$sDate);
			}
		}
		
		$sSql = " 
			SELECT
				`id`
			FROM
				`kolumbus_currency_factor`
			WHERE
				`currency_id` = :currency_id AND
				`active` = 1 AND
				`date` <= :date
			ORDER BY
				`date` DESC
			LIMIT 1
			";
		
		$aSql = array(
			'currency_id' =>(int)$this->id,
			'date' => $sDate
		);
		$aResult = DB::getPreparedQueryData($sSql, $aSql);

		if($aResult[0]['id'] > 0){
			$oFactor = Ext_Thebing_Currency_Factor::getInstance($aResult[0]['id']);
			return (float)$oFactor->factor;
		} else {
			return 1;
		}

	}

	/**
	 * Rechnet einen Betrag von der aktuellen Währung in die Zielwährung um
	 * @param $iAmount
	 * @param $iToCurrency => Currency ID
	 * @return float
	 */
	public function convertAmount($iAmount, $iToCurrency, $sDate=null) {

		if(
			$this->id == $iToCurrency ||
			$iToCurrency <= 0
		) {
			return $iAmount;
		}

		$iFactor = $this->getConversionFactor($iToCurrency, $sDate);

		$iFactor = (float)$iFactor;

		$iAmount = $iAmount * $iFactor;
		// 2 kommastellen da convertFloat bei 3 kommastellen die zahl als tausend erkennt!
		$fAmount = round($iAmount, 2);

		return $fAmount;

	}

	public static function getCurrencyByIso($sIso){
		$sSql = " SELECT `id` FROM `kolumbus_currency` WHERE `iso4217` = :iso LIMIT 1";
		$aSql = array('iso' => $sIso);
		$aResult = DB::getPreparedQueryData($sSql, $aSql);
		return Ext_Thebing_Currency::getInstance($aResult[0]['id']);
	}

	public static function updateCurrencyValues() {

		//DB::executeQuery('TRUNCATE kolumbus_currency_factor'); // - Fürs Debuggen
		
		ini_set('max_execution_time', ini_get('max_execution_time') + 121);
		
		$sSQL = "SELECT `id` FROM `kolumbus_currency_factor` WHERE `date` = '" . date('Y-m-d') . "' LIMIT 1";
		$iCheck = DB::getQueryOne($sSQL);

		if(empty($iCheck))
		{
			$sSQL = "SELECT `iso4217`, `id` FROM `kolumbus_currency`";
			$aCurrencies = DB::getQueryPairs($sSQL);

			$oXML = self::_getCBCurrencysData(false);
			if(!$oXML) {
				sleep(60);
				$oXML = self::_getCBCurrencysData(false);
			}

			// Datum des Kurses
			$sTime = (string)$oXML->Cube->Cube->attributes()->time;
			
			$bCheckDate = WDDate::isDate($sTime, WDDate::DB_DATE);
			if(!$bCheckDate) {
				$sTime = date('Y-m-d');
			}
			
			// Es wird hier trotzdem das aktuelle Datum verwendet, weil das System aktuell für jeden Tag einen Kurs erwartet
			$sTime = date('Y-m-d');
			
			// Insert EUR
			$aInsert = array(
				'date'			=> $sTime,
				'currency_id'	=> 1,
				'factor'		=> 1
			);
			DB::insertData('kolumbus_currency_factor', $aInsert);

			$iCounter = 0;
			foreach($oXML->Cube->Cube->Cube as $aCube)
			{
				if(isset($aCurrencies[(string)$aCube['currency']]))
				{
					$aInsert = array(
						'date'			=> $sTime,
						'currency_id'	=> $aCurrencies[(string)$aCube['currency']],
						'factor'		=> (string)$aCube['rate']
					);
					DB::insertData('kolumbus_currency_factor', $aInsert);
					$iCounter++;
				}
			}
			
			if($iCounter < 10) {
				Ext_Thebing_Util::reportError('Currency Cron error (count) - ecb.int (2)', print_r($oXML, true));
			}
				
			// Egyptian pound
//			$sCbeorgeg = self::_getEGPCurrencyData(false);
//			if(!$sCbeorgeg) {
//				sleep(60);
//				$sCbeorgeg = self::_getEGPCurrencyData(true);
//			}
//
//			$iCurrencyLine = strpos($sCbeorgeg, 'font', strpos($sCbeorgeg, '(EUR)'));
//			$iCurrencyOpening = strpos($sCbeorgeg, '>', $iCurrencyLine);
//			$iCurrencyClosing = strpos($sCbeorgeg, '<', $iCurrencyOpening);
//
//			$sEGP = substr($sCbeorgeg, $iCurrencyOpening + 1, ($iCurrencyClosing - 1) - $iCurrencyOpening);
//			$fEGP = (float)$sEGP;

			// Wir holen uns ab jetzt einfach den ägyptischen Pfund über die neuere Thebing Wechselkurs API
			$oThebingApi = simplexml_load_file('http://update.fidelo.com/exchangerates/api.php?base=EUR');
			#$sEGPDate = (string)$oThebingApi->rates->attributes()->date;
			foreach($oThebingApi->rates->rate as $oRate) {
				if((string)$oRate->currency === 'EGP') {
					$fEGP = (float)$oRate->value;
					break;
				}
			}

			if($fEGP) {
				$aInsert = array(
					'date'			=> $sTime,
					'currency_id'	=> $aCurrencies['EGP'],
					'factor'		=> $fEGP
				);

				DB::insertData('kolumbus_currency_factor', $aInsert);
			} else {
				Ext_Thebing_Util::reportError('Currency Cron error - EGP - Invalid Value', print_r($oThebingApi, true));
			}
			
		}

		return true;

	}
	
	protected static function _getCBCurrencysData($reporting=false)
	{
		$oXML = simplexml_load_file('http://www.ecb.int/stats/eurofxref/eurofxref-daily.xml');
		if(!$oXML) {
			if($reporting) {
				Ext_Thebing_Util::reportError('Currency Cron error - ecb.int - File fetch failed', print_r($oXML, true));
			}
			return false;
		}
		return $oXML;
	}
	
//	protected static function _getEGPCurrencyData($reporting=false)
//	{
//		$sCbeorgeg = file_get_contents('http://www.cbe.org.eg/'); // invalid html site - watch out!
//
//		if(!$sCbeorgeg) {
//			if($reporting) {
//				Ext_Thebing_Util::reportError('Currency Cron error - EGP - File fetch failed', print_r($sCbeorgeg, true));
//			}
//			return false;
//		}
//		return $sCbeorgeg;
//	}

	/**
	 * Leider notwendig, da Klasse nicht auf TC-Struktur basiert
	 *
	 * @param $sIso4217
	 * @return Ext_Thebing_Currency|null
	 */
	public static function getByIso($sIso4217) {

		$sSql = "
			SELECT
				*
			FROM
				`kolumbus_currency`
			WHERE
				`iso4217` = :iso
		";

		$aData = (array)DB::getQueryRow($sSql, ['iso' => $sIso4217]);
		if(!empty($aData)) {
			return self::getObjectFromArray($aData);
		}

		return null;

	}

	/**
	 * Währungszeichen links oder rechts vom Betrag?
	 * @todo Ist hier eigentlich komplett falsch, da es nicht von der einzelnen Währung abhängig ist
	 * @return bool
	 */
	public function addSign(&$sAmount) {

		if(empty(self::$sCurrencyFormat)) {
			
			$oLocaleService = new Core\Service\LocaleService();

			$oSchool = Ext_Thebing_School::getSchoolFromSessionOrFirstSchool();
			
			$sLocale = $oSchool->language.'_'.$oSchool->country_id;
			
			$sCurrencyFormat = $oLocaleService->getLocaleValue($sLocale, null, 'currencynumber');
			
			if(empty($sCurrencyFormat)) {
				$sCurrencyFormat = '#,##0.00 ¤';
			}

			if($this->bThinspaceSign) {
				// NARROW NO-BREAK SPACE einfügen, damit Symbol nicht an Zahl klebt
				if(\Illuminate\Support\Str::startsWith($sCurrencyFormat, '¤')) {
					$sCurrencyFormat = str_replace('¤', '¤ ', $sCurrencyFormat);
				} elseif(!\Illuminate\Support\Str::endsWith($sCurrencyFormat, ' ¤')) {
					$sCurrencyFormat = str_replace('¤', ' ¤', $sCurrencyFormat);
				}
			}

			if(strpos($sCurrencyFormat, ';') !== false) {
				list($sCurrencyFormat) = explode(';', $sCurrencyFormat);
			}
			
			self::$sCurrencyFormat = $sCurrencyFormat;

		}

		$sAmount = str_replace(['#,##0.00', '¤'], [$sAmount, $this->getSign()], self::$sCurrencyFormat);
		
	}
	
}