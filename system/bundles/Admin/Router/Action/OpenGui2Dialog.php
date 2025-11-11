<?php

namespace Admin\Router\Action;

use Admin\Enums\RouterAction as Target;
use Admin\Factory\Content as ContentFactory;
use Admin\Instance;
use Admin\Interfaces\RouterAction;
use Admin\Router\ComponentContent;


class OpenGui2Dialog implements RouterAction
{
	public function __construct(
		private ComponentContent $content
	) {}

	public function getTarget(): Target
	{
		return Target::GUI2_DIALOG;
	}

	public function getPayload(Instance $admin): array
	{
		return [
			'content' => $this->content->toArray(),
		];
	}

	public static function fromPayload(Instance $admin, array $payload): ?static
	{
		$content = ContentFactory::fromArray($payload['content']);

		$action = new self($content);
		return $action;
	}
}