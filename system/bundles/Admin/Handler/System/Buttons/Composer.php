<?php

namespace Admin\Handler\System\Buttons;

use Admin\Http\InterfaceResponse;
use Illuminate\Http\Request;

class Composer implements SystemButton
{
	const KEY = 'composer-update';

	public function getKey(): string
	{
		return self::KEY;
	}
	public function getIcon(): string
	{
		return 'fa fa-arrow-alt-circle-down';
	}

	public function getTitle(): string
	{
		return \L10N::t('Composer aktualisieren', 'Framework');
	}

	public function hasRight(\Access $access): bool
	{
		$user = $access->getUser();
		return $access->hasRight('composer_update') && \Util::isInternEmail($user->email);
	}

	public function getOptions(): array
	{
		return [];
	}

	public function handle(Request $request): bool|InterfaceResponse
	{
		return (new \Update())->executeComposerUpdate();
	}

	public function isActive(): bool
	{
		return false;
	}

}
