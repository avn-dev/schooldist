<?php

namespace Tc\Service\Exchangerate;

class Convert {

	private $table;

	public function __construct(\Ext_TC_Exchangerate_Table $table) {
		$this->table = $table;
	}

	public function convert(float $amount, string $currencyFrom, string $currencyTo, \DateTime $date = null): array {

		if($currencyFrom !== $currencyTo) {
			$date = (!is_null($date)) ? $date->format('Y-m-d') : null;

			$rate = $this->table->getRate($currencyFrom, $currencyTo, $date);
			$factor = (float)$rate->price;

			return [bcmul($amount, $factor, 5), $factor];
		}

		return [$amount, 1];
	}

}
