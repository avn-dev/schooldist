<?php

namespace Api\Factory;

use League\OAuth2\Client\Provider\AbstractProvider;
use Api\Client\OAuth2;

class OAuth2Provider
{
	public static function get(string $providerKey, OAuth2\ClientAuth $auth = null): AbstractProvider
	{
		$fallbackAuth = self::getProviderClientAuth($providerKey);

		if (!$auth) {
			$auth = $fallbackAuth;
		}

		switch ($providerKey) {
			case 'google':
				$provider = new OAuth2\GoogleProvider([
					'clientId' => $auth->getClientId(),
					'clientSecret' => $auth->getClientSecret(),
					'accessType' => 'offline',
					'approvalPrompt' => 'force', // Refresh-Token
					// URL muss hinterlegt sein
					'redirectUri' => \Util::getProxyHost().'oauth2/callback',
				]);
				break;

			case 'microsoft':
				$provider = new OAuth2\MicrosoftProvider([
					'clientId' => $auth->getClientId(),
					// Bei Microsoft läuft das client secret ab, deswegen muss immer das aktuell eingetragene verwendet werden
					'clientSecret' => $fallbackAuth->getClientSecret(),
					// URL muss hinterlegt sein
					'redirectUri' => \Util::getProxyHost().'oauth2/callback',
					'approvalPrompt' => 'force',
					// Sind in dem Package falsch für Office365
					// https://github.com/stevenmaguire/oauth2-microsoft/issues/18
					// https://github.com/singularo/oauth2-microsoft/commit/3c45ed7a26363fda4407add20e0f638604393211
					'urlAuthorize' => 'https://login.microsoftonline.com/common/oauth2/v2.0/authorize',
					'urlAccessToken' => 'https://login.microsoftonline.com/common/oauth2/v2.0/token',
					'urlResourceOwnerDetails' => 'https://graph.microsoft.com/v1.0/me'
				]);
				break;
			default:
				throw new \InvalidArgumentException('Unknown oauth2 provider "'.$providerKey.'"');
		}

		return $provider;
	}

	public static function getProviderClientAuth(string $providerKey): OAuth2\ClientAuth
	{
		switch ($providerKey) {
			case 'google':
				$auth = new OAuth2\ClientAuth(\System::d('oauth2.google.client_id'), \System::d('oauth2.google.client_secret'));
				break;
			case 'microsoft':
				$auth = new OAuth2\ClientAuth(\System::d('oauth2.microsoft.client_id'), \System::d('oauth2.microsoft.client_secret'));
				break;
			default:
				throw new \InvalidArgumentException('Unknown oauth2 provider "'.$providerKey.'"');
		}

		return $auth;
	}
}