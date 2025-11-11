<?php

class Ext_Thebing_Currency_Search {


	function search($oSchool = null) {
		$oCurrency = new Ext_Thebing_Currency_Util($oSchool);
		$aCurrencies = $oCurrency->getCurrencyList();
		$aCurrencyList = array();
		foreach ((array)$aCurrencies as $sCurrency => $sCurrencyName) {
			$aCurrencyList[] = new School_Period_Price_Currency($sCurrency);
		}
		return $aCurrencyList;
	}


	public static function getCurrencies($oSchool = null) {
		$oCurrency = new Ext_Thebing_Currency_Util($oSchool);
		$aCurrencies = $oCurrency->getCurrencyList(true);
		return $aCurrencies;
	}


	public static function checkCurrency($sCurrency,$oSchool = null) {
		$oCurrency = new Ext_Thebing_Currency_Util($oSchool);
		$aCurrencies = self::getCurrencies();
		if (array_key_exists((string)$sCurrency, (array)$aCurrencies)) {
			return $sCurrency;
		}
		return 'EUR';
	}


	public static function format($sAmount, $mCurrency,$oSchool = null) {
		$oCurrency = new Ext_Thebing_Currency_Util($oSchool);
		if (is_object($mCurrency) && $mCurrency->get() != '') {
			$sCurrency = $mCurrency->get();
		} else {
			$sCurrency = self::checkCurrency($mCurrency);
		}
		$sAmount = L10N_Number::format($sAmount);
		
		$oCurrency->setCurrencyByIso($sCurrency);
		return $sAmount.' '.$oCurrency->getSign();

	}

}