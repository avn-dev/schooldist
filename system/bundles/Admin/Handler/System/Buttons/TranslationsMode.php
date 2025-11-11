<?php

namespace Admin\Handler\System\Buttons;

use Admin\Http\InterfaceResponse;
use Illuminate\Http\Request;

class TranslationsMode implements SystemButton
{
	const KEY = 'translation-mode';

	public function getKey(): string
	{
		return self::KEY;
	}

	public function getIcon(): string
	{
		return 'fa fa-language';
	}

	public function getTitle(): string
	{
		return \L10N::t('Übersetzungsmodus', 'Framework');
	}

	public function hasRight(\Access $access): bool
	{
		return $access->hasRight('languages');
	}

	public function getOptions(): array
	{
		return [];
	}

	public function handle(Request $request): bool|InterfaceResponse
	{
		$session = \Core\Handler\SessionHandler::getInstance();

		if ($session->get('system_translation_mode') === true) {
			$session->set('system_translation_mode', false);
		} else {
			$session->set('system_translation_mode', true);
		}

		return true;
	}

	public function isActive(): bool
	{
		return \Core\Handler\SessionHandler::getInstance()->get('system_translation_mode') === true;
	}
}
