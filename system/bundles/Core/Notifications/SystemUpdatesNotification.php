<?php

namespace Core\Notifications;

use Admin\Notifications\Channels\Messages\AdminMailMessage;
use Core\Interfaces\HasButtons;
use Core\Service\NotificationService;
use Core\Traits\Notification\WithButtons;
use Illuminate\Notifications\Notification;

class SystemUpdatesNotification extends Notification implements HasButtons
{
	use WithButtons;

	public function via($notifiable)
	{
		return ['admin-mail', 'database'];
	}

	public function __construct(private array $updates) {}

	public function toAdminMail($notifiable)
	{
		return (new AdminMailMessage('Core', 'system_notification', [
				'group_title' => NotificationService::translate('Es sind neue Updates für Ihr System verfügbar'),
				'message' => implode('<br/>', $this->formattedList())
			]))
			->subject(NotificationService::translate('Es sind neue Updates für Ihr System verfügbar'));
	}

	public function toArray(): array
	{
		$data = [
			'group_title' => \L10N::t('Systembenachrichtigung'),
			'icon' => 'fas fa-download',
			'message' => sprintf(
				NotificationService::translate('<strong>Es sind neue Updates für Ihr System verfügbar:</strong><br/>%s'),
				implode('<br/>', $this->formattedList())
			)
		];

		return $data;
	}

	private function formattedList(): array
	{
		return array_map(
			fn ($update) => sprintf('%s (%s)', $update['label'], $update['version']),
			$this->updates
		);
	}

}