<?php

namespace TsIvvy\Exceptions;

use Illuminate\Support\Str;

class RuntimeException extends \RuntimeException {

    public function __construct($message = "", $code = 0, \Throwable $previous = null) {
        parent::__construct(Str::start($message, 'Ivvy: '), $code, $previous);
    }

}
