<?php

namespace Tc\Events;

use Carbon\Carbon;
use Illuminate\Foundation\Events\Dispatchable;
use Tc\Facades\EventManager;
use Tc\Interfaces\EventManager\ManageableEvent;
use Tc\Interfaces\EventManager\Process;
use Tc\Traits\Events\Manageable\WithManageableExecutionTime;
use Tc\Traits\Events\Manageable\WithManageableSystemUserCommunication;
use Tc\Traits\Events\ManageableEventTrait;

class ManageableScheduler implements ManageableEvent
{
	use Dispatchable,
		ManageableEventTrait,
		WithManageableExecutionTime,
		WithManageableSystemUserCommunication;

	public function __construct(protected \DateTime $dateTime) {}

	public static function getTitle(): string
	{
		return EventManager::l10n()->translate('Täglich');
	}

	public static function manageEventListenersAndConditions(): void
	{
		self::addManageableCondition(Conditions\SpoolEmailsAvailable::class);
	}

	public static function dispatchScheduled(Carbon $time, Process $process, ...$args): void
	{
		self::dispatch($time, ...$args);
	}

}
