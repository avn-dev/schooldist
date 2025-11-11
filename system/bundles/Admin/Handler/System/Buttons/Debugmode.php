<?php

namespace Admin\Handler\System\Buttons;

use Admin\Http\InterfaceResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

class Debugmode implements SystemButton
{
	const KEY = 'debugmode';

	public function getKey(): string
	{
		return self::KEY;
	}

	public function getIcon(): string
	{
		return 'fa fa-bug';
	}

	public function getTitle(): string
	{
		$selected = Arr::first($this->getOptions(), fn($entry) => $entry['selected']);
		if ($selected) {
			return $selected['text'];
		}
		return \L10N::t('Debugmodus', 'Framework');
	}

	public function hasRight(\Access $access): bool
	{
		$user = $access->getUser();
		return $access->hasRight('debug_mode') && \Util::isInternEmail($user->email);
	}

	public function getOptions(): array
	{
		$debudemode = $this->getCurrentDebugmode();

		return [
			['value' => 0, 'text' => \L10N::t('Debugmodus aus', 'Framework'), 'selected' => ($debudemode === 0)],
			['value' => 1, 'text' => \L10N::t('Debugmodus ohne CMS-Tags', 'Framework'), 'selected' => ($debudemode === 1)],
			['value' => 2, 'text' => \L10N::t('Debugmodus mit CMS-Tags', 'Framework'), 'selected' => ($debudemode === 2)],
			['value' => 3, 'text' => \L10N::t('Debugmodus mit CMS-Tags und Whoops', 'Framework'), 'selected' => ($debudemode === 3)],
			['value' => 4, 'text' => \L10N::t('Erweiterter Debugmodus', 'Framework'), 'selected' => ($debudemode === 4)]
		];
	}

	public function handle(Request $request): bool|InterfaceResponse
	{
		$newDebugmode = $request->query('option', 0);

		if (isset($this->getOptions()[$newDebugmode])) {
            \AdminTools\Helper\Util::setDebugMode($request->ip(), $newDebugmode);
		}

		return true;
	}

	public function isActive(): bool
	{
		return $this->getCurrentDebugmode() > 0;
	}

	private function getCurrentDebugmode(): int
	{
		$request = app()->make(Request::class);

		if (null !== $debugmode = \WDCache::get('system_debugmode_'.$request->ip(), true)) {
			return (int)$debugmode;
		}

		return (int)\System::d('debugmode', 0);
	}

}
