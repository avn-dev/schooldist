<?php

namespace OpenBanking;

use Monolog\Logger;

class OpenBanking
{
	const L10N_PATH = 'Fidelo » Open Banking';

	public static function transactions(): Processes\LoadTransactions
	{
		return new Processes\LoadTransactions();
	}

	public static function logger(string $namespace = 'OpenBanking'): Logger
	{
		return \Log::getLogger('api', $namespace);
	}

	public static function l10n(string $language = null): \Tc\Service\LanguageAbstract
	{
		$l10n = new \Tc\Service\Language\Backend($language ?? \System::getInterfaceLanguage());
		$l10n->setContext(self::L10N_PATH);
		return $l10n;
	}
}