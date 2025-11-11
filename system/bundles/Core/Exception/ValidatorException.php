<?php

namespace Core\Exception;

use Illuminate\Contracts\Support\Responsable;
use Illuminate\Validation\Validator;

class ValidatorException extends \InvalidArgumentException implements Responsable
{
	public function __construct(private Validator $validator, int $code = 0, ?\Throwable $previous = null)
	{
		parent::__construct('Validation failed', $code, $previous);
	}

	public function getValidator(): Validator
	{
		return $this->validator;
	}

	public function toResponse($request)
	{
		if ($request->expectsJson()) {
			return response()->json([
				'code' => 'VALIDATION_ERROR',
				'messages' => $this->validator->getMessageBag()->messages()
			], 400);
		}
	}
}