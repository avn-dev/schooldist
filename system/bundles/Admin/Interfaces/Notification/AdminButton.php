<?php

namespace Admin\Interfaces\Notification;

use Admin\Instance;
use Admin\Interfaces\RouterAction;
use Core\Interfaces\Notification\Button;

interface AdminButton extends Button
{
	public function action(): ?RouterAction;

	public function toArray(Instance $admin): array;

	public static function fromArray(Instance $admin, array $payload): ?static;
}