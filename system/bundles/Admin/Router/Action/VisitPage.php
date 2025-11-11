<?php

namespace Admin\Router\Action;

use Admin\Enums\RouterAction as Target;
use Admin\Instance;
use Admin\Interfaces\RouterAction;

class VisitPage implements RouterAction
{
	public function __construct(
		private readonly string $url,
	) {}

	public function getTarget(): Target
	{
		return Target::PAGE;
	}

	public function getPayload(Instance $admin): array
	{
		return [
			'url' => $this->url
		];
	}

	public static function fromPayload(Instance $admin, array $payload): ?static
	{
		return new self($payload['url']);
	}
}