<?php

namespace OpenBanking\Exception;

use Illuminate\Support\Str;
use OpenBanking\Interfaces\Process;
use OpenBanking\Interfaces\Processes\Task;

class ProcessException extends \RuntimeException
{
	private ?Process $process;
	private ?Task $task;

	public function __construct(string $message = "", int $code = 0, ?\Throwable $previous = null)
	{
		parent::__construct(Str::start($message, 'Open Banking: '), $code, $previous);
	}

	public function process(Process $process, Task $task = null): static
	{
		$this->process = $process;
		$this->task = $task;
		return $this;
	}

	/**
	 * @return Process|null
	 */
	public function getProcess(): ?Process
	{
		return $this->process;
	}

	/**
	 * @return Task|null
	 */
	public function getTask(): ?Task
	{
		return $this->task;
	}
}