<?php

namespace Core\Middleware;

use Access_Backend;
use Core\Handler\CookieHandler;
use System;

final class Backend extends AbstractInterface {

	protected string $interface = 'backend';

	protected string $access = Access_Backend::class;

	protected function setup(): void {

		if (CookieHandler::is('systemlanguage')) {
			$systemLanguage = CookieHandler::get('systemlanguage');
		} else {
			$systemLanguage = System::getDefaultInterfaceLanguage();
		}

		System::setInterfaceLanguage($systemLanguage);

		\Factory::executeStatic(\System::class, 'setLocale');

		\Factory::executeStatic(\Util::class, 'getAndSetTimezone');

	}

}
