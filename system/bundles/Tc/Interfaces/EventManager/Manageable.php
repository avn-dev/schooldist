<?php

namespace Tc\Interfaces\EventManager;

use Tc\Interfaces\EventManager\Process\Task;
use Tc\Interfaces\Events\Settings;

interface Manageable
{
	public static function getTitle(): string;

	public static function getDescription(): ?string;

	public function isManaged(): bool;

	public function bindManagedObject(Process|Task $object): static;

	public function getManagedObject(): Process|Task|null;

}