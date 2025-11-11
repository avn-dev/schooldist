<?php

namespace Core\Events;

use Admin\Facades\Admin;
use Admin\Facades\Router;
use Admin\Notifications\Buttons\RouterActionButton;
use Core\Interfaces\Events\SystemEvent;
use Core\Interfaces\HasButtons;
use Core\Notifications\SystemUpdatesNotification;
use Core\Traits\WithButtons;
use Illuminate\Foundation\Events\Dispatchable;

class NewSystemUpdates implements SystemEvent, HasButtons
{
	use Dispatchable,
		WithButtons;

	public function __construct(private readonly array $updates) {}

	public function getNotification($listener, $notification): SystemUpdatesNotification
	{
		return new SystemUpdatesNotification($this->updates);
	}

	public function getButtons(): array
	{
		if (empty($action = Router::openSystemUpdate())) {
			return [];
		}

		return [
			new RouterActionButton(Admin::translate('Update ausführen'), $action, 'update'),
		];
	}
}