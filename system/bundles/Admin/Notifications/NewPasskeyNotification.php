<?php

namespace Admin\Notifications;

use Admin\Entity\User\Passkey;
use Admin\Notifications\Channels\Messages\AdminMailMessage;
use Core\Interfaces\Notification\Queueable;
use Core\Service\NotificationService;
use Core\Traits\Notification\WithQueue;
use Illuminate\Notifications\Notification;

class NewPasskeyNotification extends Notification implements Queueable
{
	use WithQueue;

	public function via($notifiable)
	{
		return ['admin-mail'];
	}

	public function __construct(
		private Passkey $passkey,
		private array $clientInfo
	) {}

	public function toAdminMail($notifiable)
	{
		return (new AdminMailMessage('Core', 'system_notification', [
				'group_title' => NotificationService::translate('Passkeys'),
				'message' => NotificationService::translate('Ein neuer Passkey wurde für Ihren Account erstellt.').
					'<br/><br/>'.
					'<pre>'.
						print_r($this->clientInfo, true),
					'</pre>'
			]))
			->subject(NotificationService::translate('Neuer Passkey erstellt'));
	}
}