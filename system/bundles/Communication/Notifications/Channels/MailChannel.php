<?php

namespace Communication\Notifications\Channels;

use Admin\Facades\Admin;
use Communication\Dto\ChannelConfig;
use Communication\Enums\MessageStatus;
use Communication\Exceptions\MessageTransportFailed;
use Communication\Interfaces\CommunicationChannel;
use Communication\Interfaces\Notifications\NotificationRoute;
use Communication\Notifications\Channels\Messages\MailMessage;
use Communication\Traits\Channel\WithCommunication;
use Core\Interfaces\Notification\Queueable;
use Core\Notifications\Channels\MessageTransport;
use Core\Notifications\Recipient;
use Core\Service\NotificationService;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Arr;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mime\Address;

class MailChannel implements CommunicationChannel
{
	use WithCommunication;

	const CHANNEL_KEY = 'mail';

	private function logger(): LoggerInterface
	{
		return NotificationService::getLogger('MailChannel');
	}

	public function getCommunicationConfig(): ChannelConfig
	{
		$default = [
			'icon' => 'far fa-envelope',
			'text' => Admin::translate('E-Mail', 'Communication'),
		];

		return new ChannelConfig([
			...$default,
			...$this->config,
		]);
	}

	public function validateRoute($route): bool
	{
		if ($route instanceof Recipient) {
			$route = $route->getRoute();
		} else if (is_array($route)) {
			// ['dev@fidelo.com', 'Fidelo Software GmbH (Developer)']
			[$route, ] = $route;
		}

		return \Util::checkEmailMx($route);
	}

	/**
	 * Send the given notification.
	 *
	 * @param object|null $notifiable
	 * @param Notification|MailMessage $notification
	 * @return MessageTransport
	 */
	public function send(?object $notifiable, Notification|MailMessage $notification): MessageTransport
	{
		if ($notification instanceof Notification) {
			if (!method_exists($notification, 'toMail')) {
				$this->logger()->warning('Notification not available for channel', ['notification' => $notification::class]);
				return new MessageTransport(false, ['Notification not available for channel']);
			}

			$message = $notification->toMail($notifiable);

			if ($message === null) {
				// Vermutlich absichtlich keine Nachricht
				$this->logger()->warning('No mail message given', ['notification' => $notification::class]);
				return new MessageTransport(false);
			} else if (!$message instanceof MailMessage) {
				throw new \RuntimeException(sprintf('Please return instance of "%s" in [%s::toMail()]', MailMessage::class, $notification::class));
			}
		} else {
			$message = $notification;
		}

		$log = $message->getLog();

		if (!$log) {
			// Log generieren
			$message->log($log = $this->buildLog('email', $message));
		}

		// Fallback
		if (empty($message->getFrom())) {
			/* @var $fromAddress \Ext_TC_Communication_Message_Address */
			$fromAddress = Arr::first($log->getAddresses('from'));

			if ($fromAddress) {
				/* @var \Ext_TC_Communication_EmailAccount $account */
				$account = $fromAddress->searchRelations(\Ext_TC_Communication_EmailAccount::class)->first();
				/* @var \Ext_TC_User $user */
				$user = $fromAddress->searchRelations(\Ext_TC_User::class)->first();

				if ($account) {
					$message->from($account, $user, !empty($fromAddress->name) ? $fromAddress->name : null);
				}
			}
		}

		$this->appendConversationCode($log);

		// TMC auch in der Nachricht setzen
		$message->subject($log->subject);
		$message->content($log->content, $message->getContent()[1]);

		if ($notifiable && !empty($to = $notifiable->routeNotificationFor(self::CHANNEL_KEY, $notification))) {
			$addReceiver = function ($route, $name = null, \WDBasic $model = null) use ($log, $message) {
				/* @var \Ext_TC_Communication_Message_Address $address */
				$address = $log->getJoinedObjectChild('addresses');
				$address->type = 'to';
				$address->address = $route;
				$address->name = $name;

				if ($model) {
					$address->addRelation($model);
					$log->addRelation($model);
				}

				$message->to(new Recipient(route: $route, name: $name, model: $model));
			};

			if ($to instanceof NotificationRoute) {
				$to = $to->toNotificationRoute('mail');
			}

			$model = $notifiable instanceof \WDBasic ? $notifiable : null;

			if (is_array($to) && !empty($to[0])) {
				$addReceiver($to[0], $to[1], $model);
			} else if (is_string($to) && !empty($to)) {
				$addReceiver($to, null, $model);
			}
		}

		if (empty($message->getTo()) && empty($message->getCc()) && empty($message->getBcc())) {
			// Wenn z.b. $notifiable gar nicht für E-Mail-Nachrichten vorgesehen ist darf hier auch kein Log geschrieben werden
			$this->logger()->warning('Sending message failed (No receivers)', $this->buildLoggerPayload($log, ['notifiable' => ($notifiable instanceof \WDBasic ? $notifiable::class : null), 'notifiable_id' => $notifiable?->id, 'notification' => $notification::class]));
			return new MessageTransport(success: false, errors: ['No receivers']);
		}

		$log->type = 'email';
		$log->date = time();
		$log->sent = 0;
		$log->status = null;

		$transport = new MessageTransport(success: true);

		if ($message->getSendMode() === \Ext_TC_Communication::SEND_MODE_AUTOMATIC) {

			$log->status = MessageStatus::SENDING->value;

			if (!$this->communicationMode) {
				// Wenn man nicht in dem Kommunikationsdialog ist hier schon speichern damit
				// der Log auf jeden Fall existiert. Im Dialog wird im Fehlerfall ein Error angezeigt
				$log->save();
			}

			try {
				if ($notification instanceof Queueable && $notification->shouldQueue()) {

					$transport = $this->writeToQueue($message, $notification->getQueuePriority());

				} else {

					$sentMessage = $this->sendMessage($message);

					$log->status = MessageStatus::SENT->value;
					// Message-Id speichern damit diese beim Imap-Sync nicht erneut angelegt wird
					$log->imap_message_id = $sentMessage->getMessageId();
					$log->unseen = 0;
					$log->sent = 1;
				}
			} catch (\Throwable $e) {
				$this->logger()->error('Sending message failed', $this->buildLoggerPayload($log, ['throwable' => $e->getMessage(), 'file' => $e->getFile(), 'line' => $e->getLine()]));
				$log->status = MessageStatus::FAILED->value;

				$transport->success(success: false, errors: [$e]);
			}
		}

		$this->finishLog($log, $transport, $this->logger());

		return $transport->log($log, draft: $log->isDraft());
	}

	private function writeToQueue(MailMessage $message, int $prio = 1): MessageTransport
	{
		$log = $message->getLog();

		if (!$log->exist() || $log->status !== $log->getOriginalData('status')) {
			$log->save();
		}

		[$queueId, $payload] = NotificationService::writeToQueue(self::CHANNEL_KEY, $message, $prio);

		$this->logger()->info('Writing message to queue', $this->buildLoggerPayload($log, ['payload' => $payload]));

		return (new MessageTransport(true))
			->queue($queueId);
	}

	private function sendMessage(MailMessage $message): SentMessage
	{
		$mail = new \Ext_TC_Communication_WDMail();
		$mail->setThrowablesEnabled(true);

		[$account, $user, $senderName] = $message->getFrom();

		if ($account) {
			if ($senderName) {
				$account->sFromName = $senderName;
			}
			$mail->sender_object = $account;
		}

		if ($user) {
			$mail->from_user = $user;
		}

		$to = array_map(fn (Recipient $payload) => new Address($payload->getRoute(), (string)$payload->getName()), $message->getTo());
		$mail->cc = array_map(fn (Recipient $payload) => new Address($payload->getRoute(), (string)$payload->getName()), $message->getCc());
		$mail->bcc = array_map(fn (Recipient $payload) => new Address($payload->getRoute(), (string)$payload->getName()), $message->getBcc());

		$mail->subject = (string)$message->getSubject();

		[$content, $contentType] = $message->getContent();

		if ($contentType === 'html') {
			$mail->html = (string)$content;
		} else {
			$mail->text = (string)$content;
		}

		$attachments = [];
		foreach ($message->getAttachments() as $attachment) {
			$attachments[$attachment->getFilePath()] = $attachment->getFileName();
		}
		$mail->attachments = $attachments;

		$success = $mail->send($to);

		if (!$success) {
			throw new MessageTransportFailed('Ext_TC_Communication_WDMail::send() returned false');
		}

		return $mail->message;
	}

}