<?php

namespace TsActivities\Dto;

use Carbon\Carbon;

class BlockEvent {

	public Carbon $start;

	public Carbon $end;

	public string $place;

	public function __construct(Carbon $start, Carbon $end, $place = '') {
		$this->start = $start;
		$this->end = $end;
		$this->place = $place;
	}

}
