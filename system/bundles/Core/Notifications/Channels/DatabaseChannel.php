<?php

namespace Core\Notifications\Channels;

use Admin\Facades\Admin;
use Admin\Interfaces\Notification\AdminButton;
use Core\Entity\System\UserNotification;
use Core\Interfaces\HasAlertLevel;
use Core\Interfaces\HasAttachments;
use Core\Interfaces\HasButtons;
use Core\Interfaces\HasIcon;
use Core\Service\NotificationService;
use Illuminate\Notifications\Channels\DatabaseChannel as BaseDatabaseChannel;
use Illuminate\Notifications\Notification;
use Psr\Log\LoggerInterface;

/**
 * Ersetzt \Illuminate\Notifications\Channels\DatabaseChannel
 */
class DatabaseChannel extends BaseDatabaseChannel
{
	private function logger(): LoggerInterface
	{
		return NotificationService::getLogger('DatabaseChannel');
	}

	public function send($notifiable, Notification $notification)
	{
		if (null === ($route = $notifiable->routeNotificationFor('database', $notification))) {
			$this->logger()->info('No database route', ['notifiable' => $notifiable::class, 'notifiable_id' => $notifiable->id]);
			return null;
		}

		$payload = $this->buildPayload($notifiable, $notification);

		if (empty($payload['data'])) {
			$this->logger()->info('No payload data', ['notifiable' => $notifiable::class, 'notifiable_id' => $notifiable->id, 'notification' => $notification::class]);
			return null;
		}

		unset($payload['id']);

		$payload['data'] = json_encode($payload['data']);
		$payload['notifiable'] = $notifiable->id;

		/* @var UserNotification $model */
		$model = $route->create($payload);

		NotificationService::getLogger('DatabaseChannel')->info(
			'New notification',
			['notifiable' => $notifiable::class, 'notifiable_id' => $notifiable->id, 'notification_id' => $model->id, 'payload' => $payload]
		);

		return $model;
	}

	protected function getData($notifiable, Notification $notification)
	{
		$data = self::bindNotificationPayload($notification, parent::getData($notifiable, $notification));
		return $data;
	}

	public static function toEntity(Notification $notification): UserNotification
	{
		$data = self::bindNotificationPayload($notification, $notification->toArray());
		$entity = new UserNotification();
		$entity->created = date('Y-m-d H:i:s');
		$entity->type = $notification::class;
		$entity->setDataArray($data);
		return $entity;
	}

	private static function bindNotificationPayload(Notification $notification, array $data): array
	{
		if ($notification instanceof HasAttachments) {
			foreach ($notification->getAttachments() as $attachment) {
				$data['attachments'][] = [
					'file' => $attachment->getUrl(),
					'name' => $attachment->getFileName(),
					'icon' => $attachment->getIcon() ?? 'far fa-file-alt'
				];
			}
		}

		if ($notification instanceof HasButtons) {
			$buttons = array_filter($notification->getButtons(), fn ($button) => $button instanceof AdminButton);

			foreach ($buttons as $button) {
				$array = $button->toArray(Admin::instance());

				$key = md5($button::class.'|'.json_encode($array));

				$data['buttons'][] = ['key' => $key, 'text' => $button->getTitle(), 'class' => $button::class, 'payload' => $array];
			}
		}

		if ($notification instanceof HasIcon && empty($data['icon']) && !empty($icon = $notification->getIcon())) {
			$data['icon'] = $icon;
		}

		if ($notification instanceof HasAlertLevel && empty($data['alert']) && !empty($alert = $notification->getAlertLevel())) {
			$data['alert'] = $alert;
		}

		return $data;
	}
}
