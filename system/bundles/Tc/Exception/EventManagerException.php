<?php

namespace Tc\Exception;

use Illuminate\Support\Str;
use Tc\Interfaces\EventManager\Process;

class EventManagerException extends \RuntimeException
{
	public function __construct(
		private readonly Process $process,
		\Throwable $previous,
		string $message = "",
		int $code = 0
	) {
		parent::__construct(Str::start($message, 'Event control: '), $code, $previous);
	}

	public function getProcess(): Process
	{
		return $this->process;
	}

	public function getErrorMessage(): string
	{
		return $this->getPrevious()->getMessage();
	}
}