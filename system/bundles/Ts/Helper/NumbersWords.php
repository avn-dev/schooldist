<?php

namespace Ts\Helper;

use NumberToWords\NumberToWords;

class NumbersWords {
	
	private $sLocale;
	
	public function __construct($sLanguage) {
		
		$this->sLocale = $sLanguage;

	}

	public function toWords($iNumber) {
			
		$oNumberToWords = new NumberToWords();
		
		try {

			$oNumberTransformer = $oNumberToWords->getNumberTransformer($this->sLocale);
	
		} catch (\InvalidArgumentException $ex) {

			// Fallback
			$oNumberTransformer = $oNumberToWords->getNumberTransformer('en');
	
		}
		
		$sWords = $numberTransformer->toWords($iNumber);
		
		return $sWords;
	}

	public function toCurrency($iNumber, $sCurrencyIso) {
		
		$oNumberToWords = new NumberToWords();
		
		try {

			$oCurrencyTransformer = $oNumberToWords->getCurrencyTransformer($this->sLocale);
	
		} catch (\InvalidArgumentException $ex) {

			// Fallback
			$oCurrencyTransformer = $oNumberToWords->getCurrencyTransformer('en');
	
		}
		
		$sWords = $oCurrencyTransformer->toWords(bcmul($iNumber, 100), $sCurrencyIso);
		
		return $sWords;
	}
	
}