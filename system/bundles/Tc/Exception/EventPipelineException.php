<?php

namespace Tc\Exception;

class EventPipelineException extends \RuntimeException
{
	public function __construct(
		private $failedElement,
		\Throwable $previous,
		string $message = "",
		int $code = 0
	) {
		parent::__construct($message, $code, $previous);
	}

	public function getFailedElement()
	{
		return $this->failedElement;
	}

}