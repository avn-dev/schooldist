<?php

namespace Core\Notifications;

use Admin\Notifications\Channels\Messages\AdminMailMessage;
use Illuminate\Notifications\Notification;

class AdminNotification extends Notification
{
	private string $bundle = 'Core';

	private string $templatefile = 'system_notification';

	private array $templateData = [];

	public function __construct(
		private string $subject,
		private string $message,
	) {}

	public function via(): array
	{
		return ['admin-mail'];
	}

	public function bundle(string $bundle, string $templatefile = null): static
	{
		$this->bundle = $bundle;
		if (!empty($templatefile)) {
			$this->templatefile = $templatefile;
		}
		return $this;
	}

	public function templateData(array $data): static
	{
		$this->templateData = array_merge($this->templateData, $data);
		return $this;
	}

	public function toAdminMail()
	{
		$templateData = array_merge($this->templateData, ['message' => $this->message]);

		return (new AdminMailMessage(bundle: $this->bundle, templateFile: $this->templatefile, templateData: $templateData))
			->subject($this->subject);
	}
}