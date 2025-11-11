<?php

namespace Admin\Notifications\Buttons;

use Admin\Facades\Router;
use Admin\Instance;
use Admin\Interfaces\Notification\AdminButton;
use Admin\Interfaces\RouterAction;

class RouterActionButton implements AdminButton
{
	public function __construct(
		private string $title,
		private RouterAction $routerAction,
		private string|array|null $access = null
	) {}

	public function getTitle(): string
	{
		return $this->title;
	}

	public function isAccessible(\Access $access): bool
	{
		return $access->hasRight($this->access);
	}

	public function action(): ?RouterAction
	{
		return $this->routerAction;
	}

	public function toArray(Instance $admin): array
	{
		return [
			'title' => $this->title,
			'action' => Router::toStoreData($admin, $this->routerAction),
			'access' => $this->access,
		];
	}

	public static function fromArray(Instance $admin, array $payload): ?static
	{
		$routerAction = Router::fromStoreData($admin, $payload['action']);

		if (!$routerAction instanceof RouterAction) {
			return null;
		}

		return new static($payload['title'], $routerAction, $payload['access']);
	}

}