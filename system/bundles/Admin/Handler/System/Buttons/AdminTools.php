<?php

namespace Admin\Handler\System\Buttons;

use Admin\Http\InterfaceResponse;
use Illuminate\Http\Request;

class AdminTools implements SystemButton
{
	const KEY = 'admin-tools';

	public function getKey(): string
	{
		return self::KEY;
	}

	public function getIcon(): string
	{
		return 'fa fa-tools';
	}

	public function getTitle(): string
	{
		return \L10N::t('Admin-Tools', 'Framework');
	}

	public function hasRight(\Access $access): bool
	{
		$user = $access->getUser();
		return \Util::isInternEmail($user->email);
	}

	public function getOptions(): array
	{
		return [];
	}

	public function handle(Request $request): bool|InterfaceResponse
	{
		return \Admin\Facades\InterfaceResponse::visit('/admin/tools');
	}

	public function isActive(): bool
	{
		return false;
	}
}
