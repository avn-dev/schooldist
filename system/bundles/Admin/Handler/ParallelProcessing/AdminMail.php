<?php

namespace Admin\Handler\ParallelProcessing;

use Admin\Notifications\Channels\Messages\AdminMailMessage;
use Communication\Exceptions\Mail\AccountConnectionLocked;
use Communication\Services\ConnectionLock;
use Core\Exception\ParallelProcessing\RewriteException;
use Core\Handler\ParallelProcessing\TypeHandler;
use Core\Service\NotificationService;
use Psr\Log\LoggerInterface;

/**
 * @deprecated \Core\Handler\ParallelProcessing\SendNotification
 */
class AdminMail extends TypeHandler
{
	/**
	 * Gibt den Name für ein Label zurück
	 *
	 * @return string
	 */
	public function getLabel()
	{
		return \L10N::t('Admin-Mail', 'Framework');
	}

	public function getRewriteAttempts() {
		return 3;
	}

	private function logger(): LoggerInterface
	{
		return NotificationService::getLogger('Queue');
	}

    /**
	 *  
     * @param  array $data
     * @param bool $debug
     * @return bool
     */
	public function execute(array $data, $debug = false)
	{
		$message = AdminMailMessage::fromArray($data);

		$email = new \Admin\Helper\Email($message->getBundle());
		if (!empty($subject = $message->getSubject())) {
			$email->setSubject($subject);
		}

		try {
			$sent = $email->send($message->getTemplateFile(), $message->getTo(), $message->getTemplateData());
		} catch (\Throwable $e) {
			$this->tryRewrite($data, $e);
		}

		return $sent;
	}

	private function tryRewrite(array $data, \Throwable $e): void
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

		throw $e;
	}

}