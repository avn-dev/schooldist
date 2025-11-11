<?php

namespace AdminLte\Helper;

class Fontawesome {
	
	static public function getCurrencyIcon(string $sCurrencyIso) {

		$sCurrencyIso = strtolower($sCurrencyIso);

		$aExistingIcons = [		
			'btc',
			'eur',
			'gbp',
			'ils',
			'inr',
			'jpy',
			'krw',
			'rub',
			'try',
			'usd',
		];

		if(in_array($sCurrencyIso, $aExistingIcons)) {
			return 'fa-'.$sCurrencyIso;
		}
		
		return 'fa-money';
	}
	
}