<?php

namespace Admin\Traits;

use Carbon\Carbon;

trait WithDateAsOf
{
	private ?Carbon $dateAsOf = null;

	public function dateAsOf(?Carbon $dateAsOf): static
	{
		$this->dateAsOf = $dateAsOf;
		return $this;
	}

	public function getDateAsOf(): ?Carbon
	{
		return $this->dateAsOf;
	}
}