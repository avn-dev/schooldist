<?php

namespace Ts\Dto;

class Amount
{
	public function __construct(
		public float $amount,
		public ?\Ext_Thebing_Currency $currency,
	) {}

	public function toString(\Ext_Thebing_School $school = null): string
	{
		return \Ext_Thebing_Format::Number($this->amount, $this->currency, $school);
	}

}