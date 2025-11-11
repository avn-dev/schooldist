<?php

namespace TsFrontend\Exceptions;

use RuntimeException;

class PaymentError extends RuntimeException
{
	private array $additional = [];

	public function setAdditional(array $additional): static
	{
		$this->additional = $additional;
		return $this;
	}

	public function getAdditional(): array
	{
		return $this->additional;
	}
}