<?php

namespace OpenBanking\Dto;

class Amount
{
	public function __construct(
		public float $amount,
		public \Ext_TC_Currency $currency,
	) {}

	/**
	 * @return float
	 */
	public function getAmount(): float
	{
		return $this->amount;
	}

	/**
	 * @return \Ext_TC_Currency
	 */
	public function getCurrency(): \Ext_TC_Currency
	{
		return $this->currency;
	}

	public function __toString()
	{
		return sprintf('%d %s', $this->amount, $this->currency->iso4217);
	}
}