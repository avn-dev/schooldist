<?php

namespace Admin\Exceptions;

use Admin\Interfaces\Component;

class InvalidComponentParameters extends \InvalidArgumentException
{
	public function __construct(
		private Component $component,
		private array $messages,
		int $code = 0,
		?\Throwable $previous = null
	) {
		parent::__construct(sprintf('Invalid component parameters for component [%s]', $component::class), $code, $previous);
	}

	public function getComponent(): Component
	{
		return $this->component;
	}

	public function getMessages(): array
	{
		return $this->messages;
	}
}