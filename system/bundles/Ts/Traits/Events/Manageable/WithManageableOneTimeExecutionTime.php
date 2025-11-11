<?php

namespace Ts\Traits\Events\Manageable;

use Carbon\Carbon;
use Tc\Interfaces\EventManager\Process;

trait WithManageableOneTimeExecutionTime
{
	use WithManageableExecutionTime;

	public static function dispatchScheduled(Carbon $time, Process $process, \Ext_Thebing_School $school): void
	{
		$firstSchoolId = (int)\Ext_Thebing_School::query()->pluck('id')->first();

		// Verhindern dass das Event für jede Schule ausgeführt wird
		if ($firstSchoolId === (int)$school->id) {
			self::dispatchScheduledOnce($time, $process);
		}
	}

	abstract public static function dispatchScheduledOnce(Carbon $time, Process $process): void;

}