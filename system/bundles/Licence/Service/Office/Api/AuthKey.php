<?php

namespace Licence\Service\Office\Api;

use Illuminate\Support\Str;

class AuthKey {

	const SEPARATOR = '|';

	public static function encode($authKey, $licence, $host) {
		$implode = implode(self::SEPARATOR, [$authKey, $licence, $host]);
		return 'base64:'.base64_encode($implode);
	}

	public static function decode(string $token) {
		// Key ist nicht encoded - TODO komplett auf base64 umstellen - warten bis Update durch ist
		if(Str::contains($token, 'base64:')) {
			$token = (string) base64_decode(Str::after($token, 'base64:'), true);
		}

		if(strpos($token, self::SEPARATOR) !== false) {
			$explode = explode(self::SEPARATOR, $token);

			if(count($explode) === 3) {
				return [
					'license_auth_key' => $explode[0],
					'license' => $explode[1],
					'host' => $explode[2]
				];
			}
		}

		return null;
	}
}
