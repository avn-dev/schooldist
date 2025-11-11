<?php

namespace TsHubspot\Service;

use TsHubspot\Service\Helper\General;
use HubSpot\Factory;

class ApiCreate {

	public static $hubspotAPIObject = null;

	/**
	 * @var string
	 */
	const CLIENT_ID = 'f1ddc729-1f63-417b-b8ac-00525a965540';

	/**
	 * @var string
	 */
	const CLIENT_SECRET = '7aae545f-d4c8-4bea-88ba-e6c3ff569ecd';

	public static function createAPIObject() {
		if (self::$hubspotAPIObject !== null) {
			return self::$hubspotAPIObject;
		} else {
			$sAccessToken = \System::d('hubspot_access_token');
			if (!empty($sAccessToken)) {

				$dTokenExpirationTime = new \DateTime(\System::d('hubspot_token_expiration'));
				$dComparisonTime = new \DateTime();

				// Nur wenn Token refreshed werden muss
				if ($dTokenExpirationTime < $dComparisonTime) {
					self::refreshToken();
					$sAccessToken = \System::d('hubspot_access_token');
				}

				General::increaseHubspotAPILimitCache();
				self::$hubspotAPIObject = Factory::createWithAccessToken($sAccessToken);
				return self::$hubspotAPIObject;
			}
		}
	}

	/**
	 * Erneut das Token wenn is nicht mehr gültig ist.
	 */
	protected static function refreshToken() {

		$sRefreshToken = \System::d('hubspot_refresh_token');
		$sRedirectUri = \Util::getProxyHost() . 'hubspot/auth-redirect';

		$tokens = Factory::create()->oauth()->tokensApi()->create(
			'refresh_token',
			null,
			$sRedirectUri,
			self::CLIENT_ID,
			self::CLIENT_SECRET,
			$sRefreshToken
		);

		if (!empty($tokens->getAccessToken())) {

			$dExpirationTime = new \DateTime();
			$dExpirationTime->modify('+'.$tokens->getExpiresIn().' seconds');
			$sExpirationTime = $dExpirationTime->format('Y-m-d H:i:s');

			\System::s('hubspot_access_token', $tokens->getAccessToken());
			\System::s('hubspot_refresh_token', $tokens->getRefreshToken());
			\System::s('hubspot_token_expiration', $sExpirationTime);

		}
	}

}
