<?php

namespace Communication\Notifications\Channels;

use Admin\Facades\Admin;
use Communication\Dto\ChannelConfig;
use Communication\Enums\MessageStatus;
use Communication\Exceptions\App\ApnsGatewayException;
use Communication\Interfaces\CommunicationChannel;
use Communication\Interfaces\Notifications\NotificationRoute;
use Communication\Notifications\Channels\Messages\AppMessage;
use Communication\Services\Api\Office\SendApnsNotification;
use Communication\Traits\Channel\WithCommunication;
use Core\Interfaces\Notification\Queueable;
use Core\Notifications\Channels\MessageTransport;
use Core\Notifications\Recipient;
use Core\Service\NotificationService;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Arr;
use Kreait\Firebase\Factory;
use Kreait\Firebase\Messaging\CloudMessage;
use Psr\Log\LoggerInterface;

class AppChannel implements CommunicationChannel
{
	use WithCommunication;

	const CHANNEL_KEY = 'app';

	private function logger(): LoggerInterface
	{
		return NotificationService::getLogger('AppChannel');
	}

	public function getCommunicationConfig(): ChannelConfig
	{
		$default = [
			'icon' => 'fas fa-mobile-alt',
			'text' => Admin::translate('App', 'Communication'),
			'content_types' => [ChannelConfig::CONTENT_TEXT],
			'message_per_recipient' => true,
			'fields' => [
				ChannelConfig::FIELD_TO => ['allow_custom' => false, 'routes_selection' => false],
				ChannelConfig::FIELD_TEMPLATE => [],
				ChannelConfig::FIELD_SUBJECT => ['reaches_recipient' => true],
				ChannelConfig::FIELD_ATTACHMENTS => [],
			],
			'actions' => [
				ChannelConfig::ACTION_REPLY => ['history' => false],
				ChannelConfig::ACTION_FORWARD => [],
				ChannelConfig::ACTION_RESEND => ['direction' => 'out'],
				ChannelConfig::ACTION_DELETE => [],
				ChannelConfig::ACTION_OBSERVE => [],
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
			// ['dev@fidelo.com', 'Fidelo Software GmbH (Developer)']
			[$route, ] = $route;
		}

		return is_string($route) &&
			preg_match('/^(android:|ios:).+$/', $route);
	}

	/**
	 * Send the given notification.
	 *
	 * @param object|null $notifiable
	 * @param Notification|AppMessage $notification
	 * @return MessageTransport
	 */
	public function send(?object $notifiable, Notification|AppMessage $notification): MessageTransport
	{
		if ($notification instanceof Notification) {
			if (!method_exists($notification, 'toApp')) {
				$this->logger()->warning('Notification not available for channel', ['notification' => $notification::class]);
				return new MessageTransport(false, ['Notification not available for channel']);
			}

			$message = $notification->toApp($notifiable);

			if ($message === null) {
				// Vermutlich absichtlich keine Nachricht
				$this->logger()->warning('No app message given', ['notification' => $notification::class]);
				return new MessageTransport(false);
			} else if (!$message instanceof AppMessage) {
				throw new \RuntimeException(sprintf('Please return instance of "%s" in [%s::toMail()]', AppMessage::class, $notification::class));
			}
		} else {
			$message = $notification;
		}

		$log = $message->getLog();

		if (!$log) {
			$message->log($log = $this->buildLog(self::CHANNEL_KEY, $message));
		}

		$this->appendConversationCode($log);

		// TMC auch in der Nachricht setzen
		$message->content($log->content);

		if ($notifiable && !empty($devices = $notifiable->routeNotificationFor(self::CHANNEL_KEY, $notification))) {

			$addReceiver = function ($route, $name = null, \WDBasic $model = null) use ($log, $message) {
				/* @var \Ext_TC_Communication_Message_Address $address */
				$address = $log->getJoinedObjectChild('addresses');
				$address->type = 'to';
				$address->address = $route;
				$address->name = $name;

				if ($model) {
					$log->addRelation($model);
					$address->addRelation($model);
				}

				$message->to(new Recipient(route: $route, name: $name, model: $model));
			};

			$model = $notifiable instanceof \WDBasic ? $notifiable : null;

			foreach ($devices as $to) {
				if ($to instanceof NotificationRoute) {
					$to = $to->toNotificationRoute(self::CHANNEL_KEY);
				}

				if (is_array($to) && !empty($to[0])) {
					$addReceiver($to[0], $to[1], $model);
				} else if (is_string($to) && !empty($to)) {
					$addReceiver($to, null, $model);
				}
			}
		}

		if (empty($message->getTo())) {
			// Wenn z.b. $notifiable gar nicht für App-Nachrichten vorgesehen ist darf hier auch kein Log geschrieben werden
			$this->logger()->warning('Sending message failed (No receivers)', $this->buildLoggerPayload($log, ['notifiable' => ($notifiable instanceof \WDBasic ? $notifiable::class : null), 'notifiable_id' => $notifiable?->id, 'notification' => $notification::class]));
			return new MessageTransport(success: false, errors: ['No receivers']);
		}

		$log->type = self::CHANNEL_KEY;
		$log->status = null;
		$log->date = time();

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

					if ($transport->successfully()) {
						$log->status = MessageStatus::SENT->value;
						$log->unseen = 0;
						$log->sent = 1;

						$log->app_index = $this->generateIndexEntries($log);
					} else {
						$log->status = MessageStatus::FAILED->value;
					}

				}
			} catch (\Throwable $e) {
				if ($e instanceof ApnsGatewayException) {
					$this->logger()->error('Sending message failed', $this->buildLoggerPayload($log, ['response' => $e->getResponse()]));
				} else{
					$this->logger()->error('Sending message failed', $this->buildLoggerPayload($log, ['throwable' => $e->getMessage(), 'file' => $e->getFile(), 'line' => $e->getLine()]));
				}

				$log->status = MessageStatus::FAILED->value;

				$transport->success(success: false, errors: [$e]);
			}
		}

		$this->finishLog($log, $transport, $this->logger());

		return $transport->log($log, $log->isDraft());
	}

	private function writeToQueue(AppMessage $message, int $prio = 1): MessageTransport
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

	private function sendMessage(AppMessage $message, \Ext_TC_Communication_Message $log): MessageTransport
	{
		$to = $message->getTo();

		$transports = [];

		foreach ($to as $recipient) {
			/* @var Recipient $recipient */

			try {
				preg_match('/^(android:|ios:)(.+)$/', $recipient->getRoute(), $matches);

				if (empty($matches[1]) && empty($matches[2])) {
					throw new \InvalidArgumentException('Invalid recipient route given');
				}

				$os = str_replace(':', '', $matches[1]);
				$token = $matches[2];

				$messageTransport = match ($os) {
					'android' => $this->sendAndroidMessage($token, $message),
					'ios' => $this->sendiOsMessage($token, $message),
				};

				$transports[] = $messageTransport;

			} catch (\Throwable $e) {

				if ($e instanceof ApnsGatewayException) {
					$this->logger()->error('Sending message failed', $this->buildLoggerPayload($log, ['recipient' => $recipient->toArray(), 'response' => $e->getResponse()]));
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

	private function sendAndroidMessage(string $token, AppMessage $message): MessageTransport
	{
		$credentials = storage_path('firebase_credentials.json');

		if (!file_exists($credentials)) {
			throw new \RuntimeException('Missing firebase_credentials.json file');
		}

		[$content, ] = $message->getContent();

		$factory = (new Factory)->withServiceAccount($credentials);

		$messaging = $factory->createMessaging();

		// TODO 'click_action' => 'FCM_PLUGIN_ACTIVITY'
		$message = CloudMessage::withTarget('token', $token)
			->withNotification(\Kreait\Firebase\Messaging\Notification::create($message->getSubject(), $content, $message->getImageUrl()))
			->withData($message->getAdditionalData())
			->withHighestPossiblePriority()
			->withDefaultSounds();

		$messaging->send($message);

		return new MessageTransport(success: true);
	}

	private function sendiOsMessage(string $token, AppMessage $message): MessageTransport
	{
		$identifier = \System::d('communication.app.app_identifier');

		if (empty($identifier)) {
			throw new \RuntimeException('No APNS identifier');
		}

		[$content, ] = $message->getContent();

		$production = true; // TODO

		$operation = new SendApnsNotification(
			$identifier, $token, (string)$message->getSubject(), $content, $message->getAdditionalData(), $production, (string)$message->getImageUrl()
		);

		$response = (new \Licence\Service\Office\Api())->request($operation);

		if (!$response->isSuccessful()) {
			throw new ApnsGatewayException($response->all());
		}

		return new MessageTransport(success: true);
	}

	private function generateIndexEntries(\Ext_TC_Communication_Message $log): array
	{
		$unique = function (array $relations) {
			return array_values(array_map('unserialize', array_unique(array_map('serialize', $relations))));
		};

		$from = $unique(Arr::flatten(array_map(fn ($address) => $address->relations, $log->getAddresses('from')), 1));
		$to = $unique(Arr::flatten(array_map(fn ($address) => $address->relations, $log->getAddresses('to')), 1));

		if (empty($from)) {
			// TODO absolut unsauber - damit der Index auch bei automatischen Nachrichten befüllt wird.
			$from = [['relation' => \Factory::getClassName(\User::class), 'relation_id' => 0]];
		}

		$combinations = collect($from)->crossJoin($to);

		$index = [];
		foreach ($combinations as $combination) {
			$index[] = [
				'device_relation' => $combination[1]['relation'],
				'device_relation_id' => (int)$combination[1]['relation_id'],
				'thread_relation' => $combination[0]['relation'],
				'thread_relation_id'  => (int)$combination[0]['relation_id'],
			];
		}

		return array_values(array_map('unserialize', array_unique(array_map('serialize', $index))));
	}

}