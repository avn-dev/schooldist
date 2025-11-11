<?php

/**
 * WDBASIC für Währungen
 * Diese Datei ist keine Ableitung, sondern dient mit dem Aufruf von getArrayList()
 * 
 * Beispiel:
 *	$oCurrencies = new Ext_TC_Currency(0);
 *	$aCurrencies = $oCurrencies->getArrayList(true)
 *
 * @see Ext_TC_Basic::getArrayList()
 * @param string $iso4217
 * @param string $sign
 */
class Ext_TC_Currency extends Ext_TC_Basic {

	protected $_sTable = 'data_currencies';
	
	protected static $_aArrayListCache = array();

	/**
	 * @param mixed $mIso
	 * @return static
	 * @throws Exception
	 */
	static public function getInstance($mIso=0) {

		if(is_array($mIso)) {
			$sIso = $mIso['iso4217'];
		} else {
			$sIso = $mIso;
		}

		return parent::getInstance($sIso);

	}

	public function __construct($mIso = '') {

		if(!empty($mIso)) {

			if(is_array($mIso)) {
				
				$this->_aData = $mIso;
				
			} else {
				$sSql = "SELECT
							*
							FROM
								#table
							WHERE
								`iso4217` = :iso
							LIMIT 1
					";


				$aSql = array('table' => $this->_sTable, 'iso' => $mIso);

				$aResult = (array)DB::getPreparedQueryData($sSql, $aSql);

				$this->_aData = reset($aResult);
			
			}
			
		}

	}
	
	public function __get($sName){
		if($sName == 'sign'){
			$sSign = $this->_aData['sign'];
			if(empty($sSign)){
				$sSign = $this->iso4217;
			}
			return $sSign;
		} else {
			return $this->_aData[$sName];
		}
	}
	
	public static function getSelectOptions()
	{		
		return self::getISOSelectOptions();
	}

	/**
	 * @TODO Was ist das hier für ein Mist? Warum wurde diese Methode überschrieben?
	 *
	 * @param array $aArray
	 * @return Ext_TC_Currency|static
	 */
	public static function getObjectFromArray(array $aArray) {

		// Hässlicher Workaround, da Ext_Thebing_Currency immer noch auf eigener Tabelle basiert
		if(static::class === 'Ext_Thebing_Currency') {
			return parent::getObjectFromArray($aArray);
		}

		$oCurrency = self::getInstance($aArray['iso4217']);
		return $oCurrency;
	}

	/**
	 * Holt sich alle Währungen für ein Select mit ISO als key
	 * @todo Alle Daten stehen schon im Array, hier müssen keine Instanzen der Objekte aufgebaut werden. Bitte mit getObjektFromArray oder direkt mit den Daten arbeiten
	 * @return array
	 */
	public static function getISOSelectOptions($bOnlySign = false)
	{

		$oCurrencies = new self(0);
		$aCurrencies = $oCurrencies->getArrayList2();

		$oFormat = new Ext_TC_Gui2_Format_CurrencyTitle();
		$aReturn = array();

		foreach($aCurrencies as $aCurrency) {

			$oCurrency = self::getInstance($aCurrency);
			if($bOnlySign){
				$aReturn[(string)$aCurrency['iso4217']] = $oCurrency->getSign();
			} else {
				$aReturn[(string)$aCurrency['iso4217']] = $oCurrency->getName();
			}

		}

		return $aReturn;

	}
	
	/**
	 * Liefert den Namen / das Label des Objekts
	 * @return string 
	 */
	public function getName() {
		
		$sCurrencyTitle = $this->iso4217.' '.$this->sign;
		
		return $sCurrencyTitle;
		
	}


	public function getArrayList2($bForSelect = false, $sNameField = 'name', $bCheckValid = true){

		$sCacheKey = get_class($this) . '_' . (int)$bForSelect . '_' . $sNameField;

		if(empty(self::$_aArrayListCache[$sCacheKey]))
		{
			$sSql = " SELECT * FROM #table";

			$aSql = array('table' => $this->_sTable);

			$aResult = DB::getPreparedQueryData($sSql, $aSql);

			$aBack = array();

			foreach ($aResult as $aData) {
				if(!$bForSelect){
					$aBack[] = $aData;
				} else {
					$aBack[$aData['iso4217']] = $aData[$sNameField];
				}
			}

			self::$_aArrayListCache[$sCacheKey] = $aBack;
		}

		return self::$_aArrayListCache[$sCacheKey];
	}

	/**
	 *
	 * @return self[]
	 */
	public static function getObjectList($bCheckValid = true) {

		$oSelf = new static();

		$sSql = " SELECT `iso4217` FROM #table ";
		$aSql = array('table' => $oSelf->_sTable);

		$aResult = DB::getPreparedQueryData($sSql, $aSql);

		$aBack = array();

		foreach ($aResult as $aData) {
			$sClassName = get_class($oSelf);
			$aBack[] = call_user_func(array($sClassName, 'getInstance'), (string)$aData['iso4217']);
		}

		return $aBack;

	}

	/**
	 * Währungszeichen liefern
	 *
	 * @return string
	 */
	public function getSign() {
		$sSign = $this->sign;
		if(empty($sSign)){
			$sSign = $this->iso4217;
		}
		return $sSign;
	}

	/**
	 * Währungszeichen links oder rechts vom Betrag?
	 * @deprecated
	 * @todo Das muss geändert werden, da sich das an der Anzeigesprache (locale) und nicht an der Währung orientieren muss
	 * @return bool
	 */
	public function hasLeftBoundSign() {

		if(in_array($this->iso4217, ['USD', 'GBP', 'AUD', 'NZD'])) {
			return true;
		}

		return false;

	}
	
}
