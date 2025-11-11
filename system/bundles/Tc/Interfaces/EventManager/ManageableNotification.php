<?php

namespace Tc\Interfaces\EventManager;

interface ManageableNotification
{
	public function isManaged(): bool;

	public function bindProcess(Process $process, Process\Task $task): static;

	public function getProcess(): ?Process;

	public function getTask(): ?Process\Task;

}