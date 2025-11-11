<?php

namespace Admin\Handler\System\Buttons;

use Admin\Http\InterfaceResponse;
use Illuminate\Http\Request;

class InfoTextsMode implements SystemButton
{
	const KEY = 'info-texts-mode';

	public function getKey(): string
	{
		return self::KEY;
	}

	public function getIcon(): string
	{
		return 'fa fa-info-circle';
	}

	public function getTitle(): string
	{
		return \L10N::t('Info-Texte pflegen', 'Framework');
	}

	public function hasRight(\Access $access): bool
	{
		return $access->hasRight('info_texts');
	}

	public function getOptions(): array
	{
		return [];
	}

	public function handle(Request $request): bool|InterfaceResponse
	{
		$session = \Core\Handler\SessionHandler::getInstance();

		if ($session->get('system_infotexts_mode') === true) {
			$session->set('system_infotexts_mode', false);
		} else {
			$session->set('system_infotexts_mode', true);
		}

		return true;
	}

	public function isActive(): bool
	{
		return \Core\Handler\SessionHandler::getInstance()->get('system_infotexts_mode') === true;
	}
}
