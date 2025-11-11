<?php

namespace Core\Exception;

use Core\Enums\ErrorLevel;

/**
 *
 */
class ReportErrorException extends \Exception
{
	public function __construct(
		protected ErrorLevel $errorLevel,
		protected $message,
		protected array $additionalData = []
	)
	{
		parent::__construct($message);
	}

	/**
	 * @return ErrorLevel
	 */
	public function getErrorLevel(): ErrorLevel
	{
		return $this->errorLevel;
	}

	/**
	 * @return array
	 */
	public function getAdditionalData(): array
	{
		return $this->additionalData;
	}

}