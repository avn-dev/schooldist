<?php

namespace TsReporting\Traits;

use Carbon\Carbon;
use Carbon\CarbonPeriod;

/**
 * @property ?CarbonPeriod $value
 */
trait TimeframeFilterTrait
{
	public function setValue(mixed $value): void
	{
		if (!$value) {
			$this->value = null;
			return;
		}

		if ($value instanceof CarbonPeriod) {
			$this->value = $value;
		} else {
			$start = Carbon::parse($value['start'])->startOfDay();
			$end = Carbon::parse($value['end'])->endOfDay();
			$this->value = new CarbonPeriod($start, $end);
		}
	}

	public function __clone(): void
	{
		$this->value = clone $this->value;
	}
}