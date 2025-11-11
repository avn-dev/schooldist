<?php

namespace TsStudentApp\Notifications;

use Ts\Notifications\Channels\Messages\AdminMailMessage;
use Illuminate\Notifications\Notification;
use Tc\Facades\EventManager;
use TcFrontend\Traits\WithInputCleanUp;
use TsStudentApp\Messenger\Thread\AbstractThread;

class AppMessageNotification extends Notification
{
	use WithInputCleanUp;

	public function via($notifiable)
	{
		return ['admin-mail', 'database'];
	}

	public function __construct(
		private AbstractThread $thread,
		private \Ext_TC_Communication_Message $message
	) {}

	public function toAdminMail($notifiable)
	{
		$receiver = $this->thread->getEntity();
		$content = nl2br($this->cleanUp($this->message->content));

		if ($notifiable::class === $receiver::class) {
			// Weiterleitung an Empfänger
			$message = sprintf(
				EventManager::l10n()->translate('Schüler <strong>%s</strong> hat Ihnen eine Nachricht über die Schüler-App gesendet: <p>%s</p>'),
				$this->getSenderName(),
				$content
			);
		} else {
			$message = sprintf(
				EventManager::l10n()->translate('Schüler <strong>%s</strong> hat über die Schüler-App eine Nachricht an <strong>%s</strong> versendet: <p>%s</p>'),
				$this->getSenderName(),
				$this->getReceiverName(),
				$content
			);
		}

		return (new AdminMailMessage('TsStudentApp', 'app_message', [
				'message' => $message,
				//'date' => (new \Ext_Thebing_Gui2_Format_Date_Time())->formatByValue($this->message->created)
			]))
			->school($this->thread->getInquiry()->getSchool())
			->subject(EventManager::l10n()->translate('WG: Eine neue Nachricht ist über die Schüler-App eingegangen'));
	}

	public function toArray(): array
	{
		$data = [
			'group_title' => EventManager::l10n()->translate('Schüler-App'),
			'message' => sprintf(
				EventManager::l10n()->translate('Schüler <strong>%s</strong> hat über die Schüler-App eine Nachricht an <strong>%s</strong> versendet: <p>%s</p>'),
				$this->getSenderName(),
				$this->getReceiverName(),
				nl2br($this->cleanUp($this->message->content))
			)
		];

		return $data;
	}

	private function getSenderName(): string
	{
		$inquiry = $this->thread->getInquiry();
		return sprintf('%s (%s)', $inquiry->getTraveller()->getName(), $inquiry->getNumber());
	}

	private function getReceiverName(): string
	{
		$receiver = $this->thread->getEntity();

		if ($receiver instanceof \Ext_Thebing_School) {
			return $receiver->ext_1;
		}

		return $receiver->getName();
	}
}