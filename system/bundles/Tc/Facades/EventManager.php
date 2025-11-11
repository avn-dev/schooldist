<?php

namespace Tc\Facades;

use Illuminate\Support\Facades\Facade;
use Tc\Interfaces\EventManager\Process;
use Tc\Interfaces\Events\Settings;

/**
 * @method static turnOff()
 * @method static enableLogging(string $eventName = null)
 * @method static handle(string $eventName, mixed $payload)
 * @method static array handleScheduled(\Carbon\Carbon $datetime, ...$args)
 * @method static listen(string $eventName, array $config = [])
 * @method static bool isListening(string $eventName)
 * @method static array getEventList()
 * @method static string getEventTitle(string $eventName)
 * @method static \Illuminate\Support\Collection getDetailedList(\Access $access = null)
 * @method static array getConfiguration()
 * @method static \Monolog\Logger logger(string $channel = 'Log')
 * @method static \Tc\Service\LanguageAbstract l10n(string $language = null)
 * @method static array getEventListenersAndConditions(string $eventName)
 * @method static array runProcessTest(string $eventName, Process $process, Settings $settings)
 */
class EventManager extends Facade
{
	protected static function getFacadeAccessor()
	{
		return \Tc\Service\EventManager::class;
	}
}
