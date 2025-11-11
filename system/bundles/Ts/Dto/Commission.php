<?php

namespace Ts\Dto;

use Ts\Enums\CommissionType;

class Commission
{
	public function __construct(
		public float $rate,
		public CommissionType $type
	) {}

	public function calculate(float $amount): float
	{
		if ($this->type->isPercent()) {
			$calculated = $amount * ($this->rate / 100);
		} else if ($this->type->isFixAmount()) {
			$calculated = $this->rate;
		} else {
			throw new \RuntimeException(sprintf('Unknown provision type [%s]', $this->type->value));
		}

		return $calculated;
	}
}