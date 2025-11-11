<?php

namespace Core\Exception\ParallelProcessing;

use Core\Enums\ErrorLevel;
use Core\Exception\ReportErrorException;

class TaskException extends ReportErrorException
{
	public function __construct(
		ErrorLevel $errorLevel,
		string $message,
		array $task
	) {
		parent::__construct($errorLevel, $message, ['task' => $task]);
	}

	public function bindErrorData(array $aErrorData): void
	{
		$this->additionalData = array_merge($this->additionalData, $aErrorData);
	}
	
	public function getErrorData(): array
	{
		return $this->additionalData;
	}

	public function getTask(): array
	{
		return $this->additionalData['task'];
	}
}
