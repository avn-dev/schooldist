<?php

namespace Core\Facade;

use Illuminate\Support\Facades\Facade;

/**
 * @method static begin(string $name, array|string $eventNames = [])
 * @method static array stop(string $name)
 * @method static array commit(string $name, $events = [])
 */
class EventTransaction extends Facade
{
	protected static function getFacadeAccessor()
	{
		return \Core\Service\EventTransaction::class;
	}
}