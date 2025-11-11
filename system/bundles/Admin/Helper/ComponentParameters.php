<?php

namespace Admin\Helper;

class ComponentParameters
{
	public static function encrypt(array $parameters): string
	{
		$json = json_encode($parameters);
		return \Illuminate\Support\Facades\Crypt::encrypt($json);
	}

	public static function decrypt(string $encrypted): array
	{
		$json = \Illuminate\Support\Facades\Crypt::decrypt($encrypted);
		$parameters = json_decode($json, true);
		return $parameters;
	}
}