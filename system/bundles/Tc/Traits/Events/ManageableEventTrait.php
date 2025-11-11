<?php

namespace Tc\Traits\Events;

use Core\Facade\Cache;

trait ManageableEventTrait
{
	use ManageableTrait;

	/**
	 * e.g. [
	 * 		\Tc\Listeners\SendUserSystemNotification::class,
	 * 		\Tc\Listeners\SendUserSystemNotification::class.'@handle'
	 * 		[\Tc\Listeners\SendUserSystemNotification::class, 'handle']
	 * ]
	 * @var array
	 */
	protected static array $manageableListeners = [];

	/**
	 * e.g. [
	 * 		\Tc\Listeners\SendUserSystemNotification::class,
	 * 		\Tc\Listeners\SendUserSystemNotification::class.'@passes'
	 * 		[\Tc\Listeners\SendUserSystemNotification::class, 'passes']
	 * ]
	 * @var array
	 */
	protected static array $manageableConditions = [];

	protected static function addManageableListener(string|array $listener): void
	{
		static::$manageableListeners[static::class][] = $listener;
	}

	protected static function addManageableCondition(string|array $condition): void
	{
		static::$manageableConditions[static::class][] = $condition;
	}

	public static function getManageableListeners(): array
	{
		static::registerManageables();
		return static::$manageableListeners[static::class] ?? [];
	}

	public static function getManageableConditions(): array
	{
		static::registerManageables();
		return static::$manageableConditions[static::class] ?? [];
	}

	private static function registerManageables(): void
	{
		if (isset(static::$manageableListeners[static::class])) {
			// Bereits durchgelaufen
			return;
		}

		$cacheKey = 'event_'.static::class;

		$cached = Cache::get($cacheKey);

		if ($cached !== null && \System::d('debugmode') != 2) {
			static::$manageableListeners[static::class] = $cached['listeners'];
			static::$manageableConditions[static::class] = $cached['conditions'];
			return;
		}

		static::$manageableListeners[static::class] = [];
		static::$manageableConditions[static::class] = [];

		$class = new \ReflectionClass(static::class);
		$methods = array_filter(
			$class->getMethods(\ReflectionMethod::IS_PUBLIC),
			// Alle statischen Methoden der Klasse sammeln die mit manage* beginnen
			fn (\ReflectionMethod $method) => $method->isStatic() && str_starts_with($method->name, 'manage')
		);

		foreach ($methods as $method) {
			call_user_func([static::class, $method->name]);
		}

		Cache::put($cacheKey, 60*60*24, [
			'listeners' => array_unique(static::$manageableListeners[static::class]),
			'conditions' => array_unique(static::$manageableConditions[static::class]),
		]);
	}

}
