<?php

namespace TsStudentApp\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @mixin \TsStudentApp\Helper\ComponentBuilder
 */
class Component extends Facade
{
	protected static function getFacadeAccessor()
	{
		return \TsStudentApp\Helper\ComponentBuilder::class;
	}
}