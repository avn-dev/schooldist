<?php

namespace Tc\Traits\Events;

use Tc\Interfaces\EventManager\Process;
use Tc\Interfaces\EventManager\Process\Task;

trait ManageableTrait
{
	protected null|Process|Task $managedObject = null;

	public static function getDescription(): ?string
	{
		return null;
	}

	public function isManaged(): bool
	{
		return $this->managedObject !== null;
	}

	public function bindManagedObject(Process|Task $object): static
	{
		$this->managedObject = $object;
		return $this;
	}

	public function getManagedObject(): null|Process|Task
	{
		return $this->managedObject;
	}
}
