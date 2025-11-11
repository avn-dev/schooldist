<?php

namespace Core\Notifications;

use Admin\Notifications\Channels\Messages\AdminMailMessage;
use Core\Service\NotificationService;
use Illuminate\Notifications\Notification;

class AnnouncementNotification extends Notification
{
	private ?string $image = null;

	public function __construct(
		private string $title,
		private string $message
	) {}

	public function via($notifiable)
	{
		return ['admin-mail', 'database'];
	}

	public static function getGroupTitle(array $data): string
	{
		return NotificationService::translate('Ankündigungen');
	}

	public function image(string $image): static
	{
		$this->image = $image;
		return $this;
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
		$additional = [];
		if ($this->image) {
			[$originalWidth, $originalHeight] = getimagesize($this->image);
			if (!empty($originalWidth) && !empty($originalHeight)) {
				$additional['image'] = $this->image;
				$additional['image_height'] = $originalHeight;
				$additional['image_width'] = $originalWidth;
			}
		}

		return array_merge(
			['subject' => $this->title, 'message' => $this->message, 'icon' => 'far fa-lightbulb'],
			$additional
		);
	}

}
