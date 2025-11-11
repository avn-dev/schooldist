<?php

namespace Admin\Traits;

use Admin\Handler\System\Buttons;
use Admin\Handler\System\Buttons\SystemButton;
use Illuminate\Container\Container;
use Illuminate\Support\Collection;

trait SystemButtons
{
	private function getButtons(\Access_Backend $access, Container $container): Collection
	{
		$buttons = [
			\Admin\Handler\System\Buttons\LogOut::class,
			\Admin\Handler\System\Buttons\Debugmode::class,
			\Admin\Handler\System\Buttons\CacheClear::class,
			\Admin\Handler\System\Buttons\TranslationsMode::class,
			\Admin\Handler\System\Buttons\InfoTextsMode::class,
			\Admin\Handler\System\Buttons\Routing::class,
			\Admin\Handler\System\Buttons\Composer::class,
			\Admin\Handler\System\Buttons\AdminTools::class,
		];

		\System::wd()->executeHook('control_sidebar_buttons', $buttons);

		return collect($buttons)
			->map(fn (string $class) => $container->make($class))
			->filter(fn (\Admin\Handler\System\Buttons\SystemButton $button) => $button->hasRight($access))
			->values();
	}

	private function getButton(\Access_Backend $access, Container $container, string $key): ?\Admin\Handler\System\Buttons\SystemButton
	{
		/* @var SystemButton $button */
		$button = $this->getButtons($access, $container)
			->first(fn (\Admin\Handler\System\Buttons\SystemButton $button) => $button->getKey() === $key);

		if (!$button || !$button->hasRight($access)) {
			return null;
		}

		return $button;
	}
}