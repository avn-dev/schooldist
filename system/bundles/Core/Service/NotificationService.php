<?php

namespace Core\Service;

use Core\Enums\AlertLevel;
use Core\Interfaces\Notification\Queueable;
use Core\Notifications\ToastrNotification;
use Psr\Log\LoggerInterface;

class NotificationService
{
	const LOG_FILE = 'notifications';

	const L10N_PATH = 'Fidelo » Notifications';

	public static function getLogger(string $channel = 'Log')
	{
		return \Log::getLogger(self::LOG_FILE, $channel);
	}

	/**
	 * @param string $translate
	 * @param string|null $language
	 * @return string
	 * @throws \Exception
	 */
	public static function translate(string $translate, string $language = null): string
	{
		$l10n = \L10N::getInstance($language ?? \System::getInterfaceLanguage());
		return $l10n->translate($translate, self::L10N_PATH);
	}

	/**
	 * @param string $message
	 * @return void
	 */
	public static function sendToOnlineUsers(string $message): void
	{
		$activeUsers = \Access_Backend::getActiveUser();
		$access = \Access::getInstance();

		foreach ($activeUsers as $userData) {
			if(
				$access instanceof \Access_Backend &&
				$userData['userid'] == $access->id
			) {
				// Nachricht nicht an sich selbst verschicken
				continue;
			}

			/* @var \User $user */
			$user = \Factory::getInstance(\User::class, (int)$userData['userid']);
			self::sendToUser($user, $message);
		}
	}

	/**
	 * @param \User $user
	 * @param string $message
	 * @param string $type
	 * @param array $channels
	 * @return void
	 */
	public static function sendToUser(\User $user, string $message, AlertLevel $alertLevel = AlertLevel::INFO, array $channels = ['database']): void
	{
		$notification = new ToastrNotification($message, $alertLevel);

		if ($alertLevel === AlertLevel::DANGER) {
			$notification->persist();
		}

		$access = \Access::getInstance();
		if (
			$access instanceof \Access_Backend &&
			(int)$access->getUser()->id !== (int)$user->id
		) {
			$notification->sender($access->getUser());
		}

		$user->notifyNow($notification, $channels);
	}

	public static function writeToQueue(string $channel, $message, int $prio = 1): array
	{
		if (empty($payload = $message->toArray())) {
			throw new \RuntimeException('Missing message payload for queue');
		}

		$stack = ['channel' => $channel, 'message' => $message::class, 'payload' => $payload];

		$queueId = \Core\Entity\ParallelProcessing\Stack::getRepository()
			->writeToStack('core/notification-send', $stack, $prio);

		return [$queueId, $payload];
	}
}
