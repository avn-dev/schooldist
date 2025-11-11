<?php

namespace TsMews\Exceptions;

use Illuminate\Support\Str;

abstract class AbstractMewsException extends \RuntimeException {

	public function __construct($message = "", $code = 0, \Throwable $previous = null)
	{
		parent::__construct(Str::start($message, 'Mews: '), $code, $previous);
	}

}
