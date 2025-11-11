<?php

namespace OpenBanking\Providers\finAPI\Exceptions;

use Api\Interfaces\ApiClient\Operation;
use Illuminate\Support\Str;

class ApiException extends \RuntimeException
{
	private ?Operation $operation;

	public function __construct(string $message = "", int $code = 0, ?\Throwable $previous = null)
	{
		parent::__construct(Str::start($message, 'finAPI: '), $code, $previous);
	}

	public function operation(Operation $operation): static
	{
		$this->operation = $operation;
		return $this;
	}

	public function getOperation(): ?Operation
	{
		return $this->operation;
	}
}