<?php

namespace Admin\Handler\System\Buttons;

use Admin\Http\InterfaceResponse;
use Illuminate\Http\Request;

class CacheClear implements SystemButton
{
	const KEY = 'cache-flush';

	public function getKey(): string
	{
		return self::KEY;
	}

	public function getIcon(): string
	{
		return 'fa fa-trash';
	}

	public function getTitle(): string
	{
		return \L10N::t('Cache leeren', 'Framework');
	}

	public function hasRight(\Access $access): bool
	{
		return $access->hasRight('cache') || \Util::isDebugIP();
	}

	public function getOptions(): array
	{
		return [];
	}

	public function handle(Request $request): bool|InterfaceResponse
	{
		(new \Core\Helper\Cache())->clearAll();
		return true;
	}

	public function isActive(): bool
	{
		return false;
	}
}
