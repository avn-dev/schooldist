<?php

namespace Admin\Handler\System\Buttons;

use Admin\Http\InterfaceResponse;
use Illuminate\Http\Request;

class LogOut implements SystemButton
{
	const KEY = 'log-out';

	public function getKey(): string
	{
		return self::KEY;
	}

	public function getIcon(): string
	{
		return 'fa fa-sign-out-alt';
	}

	public function getTitle(): string
	{
		return \L10N::t('Ausloggen', 'Framework');
	}

	public function hasRight(\Access $access): bool
	{
		return true;
	}

	public function getOptions(): array
	{
		return [];
	}

	public function handle(Request $request): bool|InterfaceResponse
	{
		// TODO anders lösen damit man den Link in einem neuem Tab öffnen kann
		return \Admin\Facades\InterfaceResponse::visit('/admin/logout');
	}

	public function isActive(): bool
	{
		return false;
	}
}
