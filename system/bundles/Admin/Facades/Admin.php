<?php

namespace Admin\Facades;

use Admin\Instance;
use Admin\Interfaces\Component;
use Illuminate\Support\Facades\Facade;

/**
 * @method static static booting(callable $process)
 * @method static void boot()
 * @method static Component getComponent(string $component, array $placeholders = [])
 * @method static string translate(string $value, string $section = null)
 * @method static \Psr\Log\LoggerInterface getLogger(string $namespace = 'default')
 */
class Admin extends Facade
{
	protected static function getFacadeAccessor()
	{
		return Instance::class;
	}

	public static function instance(): Instance
	{
		return self::getFacadeRoot();
	}
}