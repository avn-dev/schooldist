<?php

namespace Ts\Dto;

use Carbon\Carbon;

class ExpectedPayment
{
	public function __construct(
		public \Ext_Thebing_Inquiry_Document $document,
		public Carbon $date,
		public Amount $amount,
		public Amount $openAmount
	) {}

	public function isDue(): bool
	{
		return $this->date <= \Carbon\Carbon::now();
	}

}