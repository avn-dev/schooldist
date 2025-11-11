<?php

namespace Communication\Notifications\Channels;

use Admin\Facades\Admin;
use Communication\Dto\ChannelConfig;
use Communication\Enums\MessageStatus;
use Communication\Exceptions\Sms\SmsGatewayException;
use Communication\Interfaces\CommunicationChannel;
use Communication\Interfaces\Notifications\NotificationRoute;
use Communication\Notifications\Channels\Messages\SmsMessage;
use Communication\Traits\Channel\WithCommunication;
use Core\Interfaces\Notification\Queueable;
use Core\Notifications\Channels\MessageTransport;
use Core\Notifications\Recipient;
use Core\Service\NotificationService;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Arr;
use Psr\Log\LoggerInterface;

class SmsChannel implements CommunicationChannel
{
	use WithCommunication;

	const CHANNEL_KEY = 'sms';

	private function logger(): LoggerInterface
	{
		return NotificationService::getLogger('SmsChannel');
	}

	public function getCommunicationConfig(): ChannelConfig
	{
		$default = [
			'icon' => 'fas fa-sms',
			'text' => Admin::translate('SMS', 'Communication'),
			'content_types' => [ChannelConfig::CONTENT_TEXT],
			'message_per_recipient' => true,
			'fields' => [
				ChannelConfig::FIELD_TO => ['allow_custom' => true, 'routes_selection' => true],
				ChannelConfig::FIELD_TEMPLATE => [],
				ChannelConfig::FIELD_SUBJECT => ['reaches_recipient' => false],
			],
			'actions' => [
				ChannelConfig::ACTION_FORWARD => [],
				ChannelConfig::ACTION_RESEND => ['direction' => 'out'],
				ChannelConfig::ACTION_DELETE => [],
			]
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
			// ['+49123456789', 'Fidelo Software GmbH']
			[$route, ] = $route;
		}

		return (new \WDValidate())
			->value($route)
			->on('PHONE_ITU')
			->execute();
	}

	/**
	 * Send the given notification.
	 *
	 * @param object|null $notifiable
	 * @param Notification|SmsMessage $notification
	 * @return MessageTransport
	 */
	public function send(?object $notifiable, Notification|SmsMessage $notification): MessageTransport
	{
		if ($notification instanceof Notification) {
			if (!method_exists($notification, 'toSms')) {
				$this->logger()->warning('Notification not available for channel', ['notification' => $notification::class]);
				return new MessageTransport(false, ['Notification not available for channel']);
			}

			$message = $notification->toSms($notifiable);

			if ($message === null) {
				// Vermutlich absichtlich keine Nachricht
				$this->logger()->warning('No sms message given', ['notification' => $notification::class]);
				return new MessageTransport(false);
			} else if (!$message instanceof SmsMessage) {
				throw new \RuntimeException(sprintf('Please return instance of "%s" in [%s::toMail()]', SmsMessage::class, $notification::class));
			}
		} else {
			$message = $notification;
		}

		$log = $message->getLog();

		if (!$log) {
			// Log generieren
			$message->log($log = $this->buildLog(self::CHANNEL_KEY, $message));
		}

		$this->appendConversationCode($log);

		// TMC auch in der Nachricht setzen
		$message->content($log->content);

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
				$to = $to->toNotificationRoute('sms');
			}

			$model = $notifiable instanceof \WDBasic ? $notifiable : null;

			if (is_array($to) && !empty($to[0])) {
				$addReceiver($to[0], $to[1], $model);
			} else if (is_string($to) && !empty($to)) {
				$addReceiver($to, null, $model);
			}
		}

		if (empty($message->getTo())) {
			// Wenn z.b. $notifiable gar nicht für SMS-Nachrichten vorgesehen ist darf hier auch kein Log geschrieben werden
			$this->logger()->warning('Sending message failed (No receivers)', $this->buildLoggerPayload($log, ['notifiable' => ($notifiable instanceof \WDBasic ? $notifiable::class : null), 'notifiable_id' => $notifiable?->id, 'notification' => $notification::class]));
			return new MessageTransport(success: false, errors: ['No receivers']);
		}

		$log->type = self::CHANNEL_KEY;
		$log->date = time();
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

					$transport = $this->sendMessage($message, $log);

					$log->status = MessageStatus::SENT->value;
					$log->unseen = 0;
					$log->sent = 1;

				}
			} catch (\Throwable $e) {
				$this->logger()->error('Sending message failed', $this->buildLoggerPayload($log, ['throwable' => $e->getMessage(), 'file' => $e->getFile(), 'line' => $e->getLine()]));
				$log->status = MessageStatus::FAILED->value;

				$transport->success(success: false, errors: [
					($e instanceof SmsGatewayException) ? $e->toReadableString() : $e
				]);
			}
		}

		$this->finishLog($log, $transport, $this->logger());

		return $transport->log($log, $log->isDraft());
	}

	private function writeToQueue(SmsMessage $message, int $prio = 1): MessageTransport
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

	private function sendMessage(SmsMessage $message, \Ext_TC_Communication_Message $log): MessageTransport
	{
		[, , $senderName] = $message->getFrom();
		$recipients = $message->getTo();

		$transports = [];
		foreach ($recipients as $recipient) {

			try {
				[$content, ] = $message->getContent();

				$gateway = new \Ext_TC_Communication_SMS_Gateway();
				$gateway->setRecipient($recipient->getRoute());
				$gateway->setMessage($content);

				if (!empty($senderName)) {
					$gateway->setSender($senderName);
				}

				$response = $gateway->send();

				if ($response === 'SENT') {
					return new MessageTransport(true);
				}

				throw new SmsGatewayException($response);

			} catch (\Throwable $e) {

				if ($e instanceof SmsGatewayException) {
					$this->logger()->error('Sending message failed', $this->buildLoggerPayload($log, ['recipient' => $recipient->toArray(), 'response' => $e->getResponse()]));
					$e = $e->toReadableString();
				} else{
					$this->logger()->error('Sending message failed', $this->buildLoggerPayload($log, ['recipient' => $recipient->toArray(), 'throwable' => $e->getMessage(), 'file' => $e->getFile(), 'line' => $e->getLine()]));
				}

				$transports[] = new MessageTransport(success: false, errors: [$e]);
			}
		}

		$success = Arr::first($transports, fn (MessageTransport $transport) => $transport->successfully());

		if (!$success) {
			return Arr::first($transports);
		}

		// Sobald eine der Nachrichten erfolgreich zugestellt wurde gilt die Nachricht als erfolgreich gesendet
		return $success;
	}

}