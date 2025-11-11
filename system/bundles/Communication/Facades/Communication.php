<?php

namespace Communication\Facades;

use Illuminate\Support\Facades\Facade;

class Communication extends Facade
{
	protected static function getFacadeAccessor()
	{
		return \Communication\Services\Communication::class;
	}
}