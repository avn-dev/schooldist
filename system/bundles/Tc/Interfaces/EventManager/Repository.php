<?php

namespace Tc\Interfaces\EventManager;

use Carbon\Carbon;
use Illuminate\Support\Collection;

interface Repository
{
	public function forEvent(string $eventName): Collection;

	public function forEntity(string $eventName, \WDBasic $entity): Collection;

	public function forTime(Carbon $date): Collection;
}