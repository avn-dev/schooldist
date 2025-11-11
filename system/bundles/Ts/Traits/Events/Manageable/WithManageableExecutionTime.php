<?php

namespace Ts\Traits\Events\Manageable;

use Carbon\Carbon;
use Tc\Interfaces\EventManager\Process;
use Tc\Traits\Events\Manageable\WithManageableExecutionTime as BaseWithManageableExecutionTime;

trait WithManageableExecutionTime
{
	use BaseWithManageableExecutionTime;

	/**
	 * @see \Ts\Hook\SchedulerHook
	 * @param Carbon $time
	 * @param Process $process
	 * @param \Ext_Thebing_School $school
	 * @return void
	 */
	abstract public static function dispatchScheduled(Carbon $time, Process $process, \Ext_Thebing_School $school): void;

}