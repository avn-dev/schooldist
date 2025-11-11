<?php

namespace Core\Exception\Entity;

class ValidationException extends \RuntimeException
{
	private array $additional = [];

	public function getAdditional(): array
	{
		return $this->additional;
	}

	public function setAdditional(array $additional): self
	{
		$this->additional = $additional;
		return $this;
	}

	public function __toString(): string
	{
		return $this->message;
	}
}