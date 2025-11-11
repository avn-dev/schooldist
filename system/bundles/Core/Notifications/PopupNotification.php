<?php

namespace Core\Notifications;

use Admin\Notifications\Channels\Messages\AdminMailMessage;
use Core\Service\NotificationService;
use Illuminate\Notifications\Notification;

class PopupNotification extends Notification {

	public function __construct(
		private string $title,
		private string $message
	) {}

	public function via($notifiable)
	{
		return ['database'];
	}

	public static function getGroupTitle(array $data): string
	{
		return NotificationService::translate('Wichtige Ankündigungen');
	}

	public function toAdminMail($notifiable)
	{
		return (new AdminMailMessage('Core', 'system_notification', [
				'group_title' => $this->title,
				'message' => $this->message

			]))
			->subject($this->title);
	}

	public function toArray()
	{
		return [
			'subject' => $this->title,
			'message' => $this->message,
			'icon' => 'fas fa-exclamation-circle'
		];
	}

}
