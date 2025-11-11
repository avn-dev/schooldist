<?php

namespace Admin\Interfaces;

use Admin\Instance;

interface RouterAction
{
	public function getTarget(): \Admin\Enums\RouterAction;

	public function getPayload(Instance $admin): array;

	public static function fromPayload(Instance $admin, array $payload): ?static;
}