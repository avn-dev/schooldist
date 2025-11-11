<?php

namespace Admin\Notifications\Channels;

use Admin\Notifications\Channels\Messages\AdminMailMessage;
use Core\Interfaces\Notification\Queueable;
use Core\Notifications\Channels\MessageTransport;
use Core\Service\NotificationService;
use Illuminate\Notifications\Notification;
use Psr\Log\LoggerInterface;

class AdminMailChannel
{
	const CHANNEL_KEY = 'admin-mail';

	private function logger(): LoggerInterface
	{
		return NotificationService::getLogger('AdminMailChannel');
	}

	/**
	 * Send the given notification.
	 *
	 * @param object|null $notifiable
	 * @param \Illuminate\Notifications\Notification $notification
	 * @return MessageTransport
	 */
	public function send(?object $notifiable, Notification|AdminMailMessage $notification): MessageTransport
	{
		if ($notification instanceof Notification) {
			if (!method_exists($notification, 'toAdminMail')) {
				$this->logger()->warning('Notification not available for channel', ['notification' => $notification::class]);
				return new MessageTransport(false, ['Notification not available for channel']);
			}
			$message = $notification->toAdminMail($notifiable);

			if (!$message instanceof AdminMailMessage) {
				throw new \RuntimeException(sprintf('Please return instance of "%s" in [%s::toAdminMail()]', AdminMailMessage::class, $notification::class));
			}
		} else {
			$message = $notification;
		}

		if ($notifiable instanceof \WDBasic && !empty($to = $notifiable->routeNotificationFor('mail', $notification))) {
			if (is_array($to) && !empty($to[0])) {
				$message->to(email: $to[0], name: $to[1]);
			} else if (is_string($to) && !empty($to)) {
				$message->to(email: $to);
			}
		}

		try {
			if ($notification instanceof Queueable && $notification->shouldQueue()) {

				$transport = $this->writeToQueue($message, $notification->getQueuePriority());

			} else {

				$transport = $this->sendMessage($message);

				$this->logger()->info('Sending mail message successfully', []);

			}
		} catch (\Throwable $e) {
			$this->logger()->error('Sending message failed', ['throwable' => $e->getMessage(), 'file' => $e->getFile(), 'line' => $e->getLine()]);

			$transport = (new MessageTransport(success: false, errors: [$e->getMessage()]));
		}

		return $transport;
	}

	private function writeToQueue(AdminMailMessage $message, int $prio = 1): MessageTransport
	{
		if (empty($payload = $message->toArray())) {
			throw new \RuntimeException('Missing message payload for queue');
		}

		$this->logger()->info('Writing mail message to queue', ['payload' => $payload]);

		$stack = ['channel' => self::CHANNEL_KEY, 'message' => $message::class, 'payload' => $payload];

		$queueId = \Core\Entity\ParallelProcessing\Stack::getRepository()
			->writeToStack('core/notification-send', $stack, $prio);

		return (new MessageTransport(success: true))->queue($queueId);
	}

	private function sendMessage(AdminMailMessage $message): MessageTransport
	{
		$email = new \Admin\Helper\Email($message->getBundle());
		if (!empty($subject = $message->getSubject())) {
			$email->setSubject($subject);
		}

		try {
			$sent = $email->send($message->getTemplateFile(), $message->getTo(), $message->getTemplateData());

			$transport = new MessageTransport(success: $sent);

		} catch (\Throwable $e) {
			$transport = new MessageTransport(success: false, errors: [$e->getMessage()]);
		}

		return $transport;
	}
}
