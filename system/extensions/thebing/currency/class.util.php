<?php

/**
 * @deprecated
 */
class Ext_Thebing_Currency_Util {
	
	protected $oSchool;
	protected $aCurrencyList;
	protected $aCurrency;

	static protected $aCacheStaticCurrencyById = array();
	static protected $aInstance = null;

	static public function getInstance($oSchool = "noData") {

		if($oSchool == null) {
			$oSchool = Ext_Thebing_School::getSchoolFromSession();
		} else if (!is_object($oSchool) && (int)$oSchool > 0) {
			$oSchool = Ext_Thebing_School::getInstance((int)$oSchool);
		} else if($oSchool == "noData") {
			self::_error("Sorry no School available");
		} else if(is_int($oSchool)) {
			$oSchool = Ext_Thebing_School::getInstance($oSchool);
		}
		
		$iSchool = $oSchool->id;

		if(empty(self::$aInstance[$iSchool])) {
			self::$aInstance[$iSchool] = new Ext_Thebing_Currency_Util($oSchool);
		}

		return self::$aInstance[$iSchool];
	}

	public function __construct($oSchool){

		if(
			$oSchool == null || 
			(
				is_numeric($oSchool) &&
				(int)$oSchool === 0
			)			
		) {
			$oSchool = Ext_Thebing_School::getSchoolFromSession();
		} elseif (
			is_numeric($oSchool) && 
			(int)$oSchool > 0
		) {
			$oSchool = Ext_Thebing_School::getInstance((int)$oSchool);
		} elseif($oSchool == "noData") {
			self::_error("Sorry no School available");
		}

		$this->oSchool = $oSchool;
		$this->_setCurrencyList();

	}
	
	public function __get($sField){
		return $this->getField($sField);
	}
	
	/**
	 * get the value of the Field
	 */
	public function getField($sField){
		
		$mBack = $this->aCurrency[$sField];
		
		if($sField == "sign" && $mBack == ""){
			$mBack = $this->aCurrency['iso4217'];
		}
		
		return $mBack;
	}
	
	public function getSchoolCurrencyList($bForSelect = false){

		$aCurrencys = json_decode($this->oSchool->currencies);
		$aBack = array();
		if($bForSelect === true){
			foreach((array)$this->aCurrencyList as $aCur){
				if(!in_array($aCur['id'],$aCurrencys)){
					continue;
				}
				$aBack[$aCur['iso4217']] = $aCur['iso4217'] . ' (' . $aCur['sign'] . ')';
			}
		} else if($bForSelect == 2){
			foreach((array)$this->aCurrencyList as $aCur){
				if(!in_array($aCur['id'],$aCurrencys)){
					continue;
				}
				$aBack[$aCur['id']] = $aCur['iso4217'] . ' (' . $aCur['sign'] . ')';
			}
		}else{
			$aBack = $this->aCurrencyList;
			foreach($aBack as $aCur){
				if(!in_array($aCur['id'],$aCurrencys)){
					unset($aBack[$aCur['id']]);
				}
			}
		}
		
		
		return $aBack;
	}

	/**
	 * Gibt eine Liste mit Währungen zurück, die in mindestens einer aktiven Schule ausgewählt sind.
	 *
	 * @param bool|int $bForSelect
	 * @return mixed[]
	 */
	public static function getAllSchoolsCurrencyList($bForSelect = false) {

		$aCurrencies = [];
		$aSchools = Ext_Thebing_Client::getSchoolList(false, 0, true);
		foreach($aSchools as $oSchool) {
			$aSchoolCurrencies = $oSchool->getCurrencies();
			foreach($aSchoolCurrencies as $aSchoolCurrency) {
				$aCurrencies[$aSchoolCurrency['id']] = $aSchoolCurrency;
			}
		}

		$aReturn = [];

		if($bForSelect === true) {
			foreach($aCurrencies as $aCurrency) {
				$aReturn[$aCurrency['iso4217']] = $aCurrency['iso4217'].' ('.$aCurrency['sign'].')';
			}
		} elseif($bForSelect == 2) {
			foreach($aCurrencies as $aCurrency){
				$aReturn[$aCurrency['id']] = $aCurrency['iso4217'].' ('.$aCurrency['sign'].')';
			}
		} else {
			$aReturn = array_values($aCurrencies);
		}

		return $aReturn;

	}

	/**
	 * get a Array with all School Currency
	 */
	public function getCurrencyList($bForSelect = false){

		$aBack = [];
		if($bForSelect === true){
			foreach((array)$this->aCurrencyList as $aCur){
				$aBack[$aCur['iso4217']] = $aCur['iso4217'] . ' (' . $aCur['sign'] . ')';
			}
		} else if($bForSelect == 2){
			foreach((array)$this->aCurrencyList as $aCur){
				$aBack[$aCur['id']] =  $aCur['iso4217'] . ' (' . $aCur['sign'] . ')';
			}
		}else{
			$aBack = $this->aCurrencyList;
		}
		return $aBack;
	}
	/**
	 * get a Array with the Current Currency of the School
	 */
	public function getCurrentCurrency(){
		return $this->aCurrency;
	}
	
	public function getIso(){
		return $this->aCurrency['iso4217']; 
	}
	
	/**
	 * get the Current Sign
	 */
	public function getSign(){
		return $this->aCurrency['sign'];
	}
	public function getCurrencyId(){
		return $this->aCurrency['id'];
	}
	
	public function _getData(){
	    return $this->aCurrency;
	}
	
	public static function getCurrencyDataById($iCurrencyId){
	    // hier war der gleiche inhalt daher hab ich das mal umgeschrieben (cw)
		$aResult = self::getStaticCurrencyById($iCurrencyId);
		return $aResult;
	}

	public static function getStaticCurrencyById($iCurrencyId){

		if($iCurrencyId <= 0){
			return [];
		}
		
		if(empty(self::$aCacheStaticCurrencyById[(int)$iCurrencyId])){
			$sSql = "SELECT
					*
				FROM
					#table
				WHERE
					`id` = :id
				LIMIT 1
				";
			$aSql = array('table'=>'kolumbus_currency','id'=>(int)$iCurrencyId);
			$aResult = DB::getPreparedQueryData($sSql,$aSql);
			if($aResult[0]['sign'] == ""){
				$aResult[0]['sign'] = $aResult[0]['iso4217'];
			}
			self::$aCacheStaticCurrencyById[(int)$iCurrencyId] = $aResult[0];
		}

		return self::$aCacheStaticCurrencyById[(int)$iCurrencyId];

	}
	
	public function getCurrencyById($iCurrencyId){
		// Damit es gecached wird leite ich das mal auf die statische weiter...(cw)
		$aResult = self::getStaticCurrencyById($iCurrencyId);
		return $aResult;
	}
	
	public function getCurrencyByIso($sIso){
		
		return $this->aCurrencyIsoList[$sIso];
		
	}

	public function setCurrencyByIso($sIso){
		if(!is_int($sIso)){
			$this->aCurrency = $this->aCurrencyIsoList[$sIso];
		} else {
			$this->setCurrencyById($sIso);
		}
	}
	public function setCurrencyById($iId){
		
		$this->aCurrency = $this->aCurrencyList[$iId];
		
	}

	/**
	 * get Array with all Currency Data of the Id Array
	 */
	public function getCurrencyByIdArray($aCurrencyIds){
		
		$aBack = [];
		foreach((array)$aCurrencyIds as $iID){
			$aResult = self::getCurrencyDataById((int)$iID);
			$aBack[$aResult['id']] = $aResult;
		}
		return $aBack;
	}

	/**
	 * get Array with all Currency Data of the Iso Array
	 */
	public function getCurrencyIsoByIdArray($aCurrencyIds){

		$aBack = [];
		foreach((array)$aCurrencyIds as $iID){
			$aResult = self::getCurrencyDataById((int)$iID);
			$aBack[$aResult['iso4217']] = $aResult;
		}
		return $aBack;
	}
	
	/**
	 * Set the Currency List Array
	 */
	protected function _setCurrencyList(){
		
		$sCurrencyJSON = $this->oSchool->getField('currencies');
		
		$aCurrencyListTemp = json_decode($sCurrencyJSON);

		// Standartschulwährung soll ganz oben sein
		$iDefaultCurrency = $this->oSchool->getCurrency();

		$aCurrencyList = array();
		foreach((array)$aCurrencyListTemp as $iKey => $iValue){
			if($iValue == $iDefaultCurrency){
				array_unshift($aCurrencyList, $iValue);
			}else{
				$aCurrencyList[$iKey] = $iValue;
			}
		}

		$this->aCurrencyList	= $this->getCurrencyByIdArray($aCurrencyList);
		$this->aCurrencyIsoList = $this->getCurrencyIsoByIdArray($aCurrencyList);
		$this->_setCurrentCurrency();
	}
	
	
	/**
	 * Set the Current Currency Array
	 *
	 * Achtung: Der Schrott funktioniert mit mehr als einer Währung nicht korrekt
	 * @todo Was ist das für eine 90er Jahre Scheiße? Refaktoring!
	 */
	protected function _setCurrentCurrency(){
		global $_VARS;

		if(isset($_VARS['currency_id']) && is_numeric($_VARS['currency_id'])){
			$this->aCurrency = $this->getCurrencyById($_VARS['currency_id']);
		} else if (isset($_VARS['currency_id']) && !is_numeric($_VARS['currency_id'])){
			$this->aCurrency = $this->getCurrencyByIso($_VARS['currency_id']);
			
		}
		if(!empty($_VARS['sCurrency'])) {
			$this->aCurrency = $this->getCurrencyByIso($_VARS['sCurrency']);
		} elseif(empty($_VARS['currency_id'])) {
			if(is_array($this->aCurrencyList)){
				$aFirstCurrency = reset($this->aCurrencyList);
				$this->aCurrency = $this->getCurrencyById($aFirstCurrency['id']);
				
			}
		}

		if($this->aCurrency['sign'] ==""){
			$this->aCurrency['sign'] = $this->aCurrency['iso4217'];
		}
		$_VARS['sCurrency'] = $this->aCurrency['iso4217'];
		$_VARS['currency_id'] = $this->aCurrency['id'];

	}
	
	protected static function _error($sString){
		die("[Currency] :: ".$sString);
	}
}