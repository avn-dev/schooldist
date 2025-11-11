<?php

namespace TsStudentApp\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @mixin \TsStudentApp\Helper\PropertyKey
 */
class PropertyKey extends Facade
{
	protected static function getFacadeAccessor()
	{
		return \TsStudentApp\Helper\PropertyKey::class;
	}
}