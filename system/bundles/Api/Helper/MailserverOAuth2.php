<?php

namespace Api\Helper;

use Core\Helper\BundleConfig;
use Illuminate\Support\Arr;

class MailserverOAuth2
{
	public static function getByHost(string $host): ?array
	{
		if (empty($host)) {
			return null;
		}

		$config = BundleConfig::of('Api')->get('mail_oauth2');

		foreach ($config as $providerKey => $providerConfig) {
			foreach (Arr::wrap($providerConfig['match']) as $match) {
				if (preg_match('/^'.$match.'$/i', $host)) {
					return [$providerKey, $providerConfig];
				}
			}
		}

		return null;
	}


	public static function getByProviderKey(string $providerKey): ?array
	{
		$config = BundleConfig::of('Api')->get('mail_oauth2');
		return $config[$providerKey] ?? null;
	}

}