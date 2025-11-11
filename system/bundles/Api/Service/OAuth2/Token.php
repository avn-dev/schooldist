<?php

namespace Api\Service\OAuth2;

use Api\Client\OAuth2\ClientAuth;
use Api\Factory\OAuth2Provider;
use Illuminate\Support\Arr;
use League\OAuth2\Client\Token\AccessToken;

class Token
{
	public static function getAccessToken(string $provider, string $code, array $scopes, ClientAuth $auth = null): ?AccessToken
	{
		$accessToken = OAuth2Provider::get($provider, $auth)->getAccessToken('authorization_code', [
			'code' => $code,
			'scope' => $scopes
		]);

		return $accessToken;
	}

	public static function refresh(string $provider, AccessToken $token, ClientAuth $auth = null): ?AccessToken
	{
		if (empty($scopes = Arr::get($token->getValues(), 'scope'))) {
			$providerConfig = \Api\Helper\MailserverOAuth2::getByProviderKey($provider);
			$scopes = Arr::wrap($providerConfig['scopes'] ?? []);
		}

		$newToken = \Api\Factory\OAuth2Provider::get($provider, $auth)->getAccessToken('refresh_token', [
			'refresh_token' => $token->getRefreshToken(),
			'scope' => $scopes
		]);

		if ($newToken && $newToken->getRefreshToken() === null) {
			// Alten Refresh-Token übernehmen. Google sendet den Refresh-Token nur beim allerersten Mal mit
			$newData = $newToken->jsonSerialize();
			$newData['refresh_token'] = $token->getRefreshToken();
			$newToken = new AccessToken($newData);
		}

		return $newToken;
	}
}