<?php

namespace Admin\Notifications;

use Admin\Entity\Device;
use Admin\Notifications\Channels\Messages\AdminMailMessage;
use Core\Interfaces\Notification\Queueable;
use Core\Service\NotificationService;
use Core\Traits\Notification\WithQueue;
use Illuminate\Notifications\Notification;

class NewDeviceNotification extends Notification implements Queueable
{
	use WithQueue;

	public function via($notifiable)
	{
		return ['admin-mail'];
	}

	public function __construct(
		private Device $device,
		private \User $user
	) {}

	public function toAdminMail($notifiable)
	{
		return (new AdminMailMessage('Core', 'system_notification', [
				'group_title' => NotificationService::translate('Waren Sie das?'),
				'message' => NotificationService::translate('Ein Zugriff auf ihr Benutzerkonto erfolgte über ein Gerät, das bislang nicht bekannt ist.').
					'<br/><br/>'.
					'<pre>'.
						print_r([
							'IP' => \Util::convertHtmlEntities($this->device->ip),
							'User-Agent' => \Util::convertHtmlEntities($this->device->user_agent),
							'Login' => $this->device->getLastLoginForUser($this->user)?->format('Y-m-d H:i:s')
						], true).
					'</pre>'
			]))
			->subject(NotificationService::translate('Neuer Login'));
	}
}