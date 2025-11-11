<?php

namespace Core\Handler\ParallelProcessing;

use Core\Exception\ParallelProcessing\RewriteException;
use Core\Notifications\Channels\MessageTransport;
use Core\Service\NotificationService;
use Psr\Log\LoggerInterface;
use Communication\Enums\MessageStatus;
use Communication\Exceptions\Mail\AccountConnectionLocked;
use Communication\Services\ConnectionLock;

class SendNotification extends TypeHandler
{
	public function getLabel()
	{
		return \L10N::t('Kommunikation: Nachricht verarbeiten');
	}

	public function getRewriteAttempts() {
		return 3;
	}

	private function logger(): LoggerInterface
	{
		return NotificationService::getLogger('Queue');
	}

	public function execute(array $data, $debug = false) {

		$channel = \Illuminate\Support\Facades\Notification::driver($data['channel']);
		$message = \Factory::executeStatic($data['message'], 'fromArray', [$data['payload']]);

		if (empty($message)) {
			throw new \RuntimeException(sprintf('Cannot retrieve message from payload [%s, %s]', $data['channel'], $data['message']));
		}

		$transport = $this->send($channel, $message);

		if (!$transport->successfully()) {
			foreach ($transport->getErrors() as $error) {
				if ($error instanceof \Throwable) {
					$this->tryRewrite($message, $data, $error);
				}
			}

			return false;
		}

		return true;
	}

	private function send(object $channel, object $message): MessageTransport
	{
		$transport = $channel->send(null, $message);

		if (!$transport instanceof MessageTransport) {
			$transport = new MessageTransport((is_bool($transport)) ? $transport : true);
			$this->logger()->warning('Invalid channel return value', ['channel' => $message->getChannel()]);
		}

		return $transport;
	}

	private function tryRewrite(object $message, array $data, \Throwable $e): void
	{
		if (
			$e instanceof AccountConnectionLocked ||
			// Falls doch mal ein Microsoft-Fehler durchkommt
			str_contains($e->getMessage(), 'connections limit exceeded')
		) {
			sleep(ConnectionLock::LOCK_DURATION * 0.5);

			$this->logger()->info('Rewrite notification', ['message' => $e->getMessage(), 'payload' => $data]);

			// Zu viele Verbindungen, den Task erneut in den Stack schreiben
			throw new RewriteException(message: $e->getMessage(), previous: $e);
		}

		$this->logger()->error('Notification could not be sent', ['message' => $e->getMessage(), 'file' => $e->getFile(), 'line' => $e->getLine(), 'payload' => $data]);

		if (method_exists($message, 'getLog') && !empty($log = $message->getLog())) {
			$log->status = MessageStatus::FAILED->value;
			$log->save();
		}

		throw $e;
	}

}
