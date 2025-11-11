<?php

namespace Core\Events\ParallelProcessing;

use Core\Interfaces\Events\ReportErrorEvent;
use Illuminate\Foundation\Events\Dispatchable;

class TaskFailed implements ReportErrorEvent
{
	use Dispatchable;

	public function __construct(private array $task, private array $exceptionData) {}

	public function getErrorTitle(): string
	{
		return 'ParallelProcessing Exception';
	}

	public function getErrorData(): array
	{
		return $this->exceptionData;
	}
}