<?php

namespace Core\Notifications;

use Admin\Notifications\Channels\Messages\AdminMailMessage;
use Core\Enums\AlertLevel;
use Core\Interfaces\HasAlertLevel;
use Core\Interfaces\HasAttachments;
use Core\Interfaces\HasButtons;
use Core\Service\NotificationService;
use Core\Traits\Notification\WithAttachments;
use Core\Traits\Notification\WithButtons;
use Core\Traits\WithAdditionalData;
use Core\Traits\WithAlertLevel;
use Illuminate\Notifications\Notification;

class SystemUserNotification extends Notification implements HasButtons, HasAttachments, HasAlertLevel
{
	use WithAdditionalData,
		WithAttachments,
		WithButtons,
		WithAlertLevel;

	private ?string $icon = null;

	private ?AlertLevel $alert = null;

	protected string|array|null $groupTitle = null;

	public function __construct(protected string $message) {}

	public function via(): array
	{
		return ['database', 'admin-mail'];
	}

	public function group(string|array $groupTitle): static
	{
		$this->groupTitle = $groupTitle;
		return $this;
	}

	public function message(string $message)
	{
		$this->message = $message;
		return $this;
	}

	public function hasGroup(): bool
	{
		return $this->groupTitle !== null;
	}

	public function isNotEmpty(): bool
	{
		return !empty($this->message);
	}

	public function getMessage(): string
	{
		return $this->message;
	}

	/**
	 * Database-/Broadcast-Channel
	 * https://laravel.com/docs/9.x/notifications#formatting-database-notifications
	 *
	 * @return array
	 * @throws \Exception
	 */
	public function toArray()
	{
		if (empty($this->message)) {
			return null;
		}

		$data = [
			'message' => $this->message
		];

		if ($this->groupTitle) {
			$data['group_title'] = $this->groupTitle;
		}

		return array_merge(
			$data,
			$this->getAdditionalData()
		);
	}

	public function toAdminMail($notifiable)
	{
		return (new AdminMailMessage('Core', 'system_notification', [
				'group_title' => $this->groupTitle,
				'message' => $this->message
			]))
			->subject(NotificationService::translate('Sie haben eine neue Systembenachrichtigung'));
	}

}