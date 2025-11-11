<?php

namespace Tc\Traits\Events;

use Tc\Interfaces\EventManager\Manageable;
use Tc\Interfaces\EventManager\ManageableEvent;
use Tc\Interfaces\EventManager\Process;

trait ManageableNotificationTrait
{
	protected ?Process $process = null;

	protected ?Process\Task $task = null;

	public function isManaged(): bool
	{
		return $this->process !== null;
	}

	public function bindProcess(Process $process, Process\Task $task = null): static
	{
		$this->process = $process;
		$this->task = $task;
		return $this;
	}

	public function getProcess(): ?Process
	{
		return $this->process;
	}

	public function getTask(): ?Process\Task
	{
		return $this->task;
	}

}
