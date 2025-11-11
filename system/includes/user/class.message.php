<?php

/**
 * Nachrichten von Benutzer an Benutzer
 * @deprecated see \Core\Service\NotificationService
 */
class User_Message {

	/**
	 * @deprecated see \Core\Service\NotificationService::sendToUser()
	 * @param $iRecipient
	 * @param $sMessage
	 * @param $sType
	 * @return false|object
	 */
	public static function sendToUser($iRecipient, $sMessage, \Core\Enums\AlertLevel $alertLevel = \Core\Enums\AlertLevel::INFO) {

		/* @var \User $user */
		$user = \Factory::getInstance(\User::class, $iRecipient);

		\Core\Service\NotificationService::sendToUser($user, $sMessage, $alertLevel);

	}

}
