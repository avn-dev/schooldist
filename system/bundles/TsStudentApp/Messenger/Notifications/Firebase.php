<?php

namespace TsStudentApp\Messenger\Notifications;

use Core\Service\NotificationService;
use Kreait\Firebase\Factory;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;

/**
 * @deprecated see \Communication\Notifications\Channels\AppChannel
 */
class Firebase extends AbstractService {

	const INVALID_TOKENS = [
		'BLACKLISTED' // Emulator?
	];

	/**
	 * Nur Devices mit einem FCM-Token
	 *
	 * @param \Ext_TS_Inquiry_Contact_Login_Device $device
	 * @return bool
	 */
	public function canNotify(\Ext_TS_Inquiry_Contact_Login_Device $device): bool {
		return !empty($device->fcm_token) && $device->push_permission && !in_array($device->fcm_token, self::INVALID_TOKENS);
	}

	/**
	 * Notification an ein Device versenden
	 *
	 * @param \Ext_TS_Inquiry_Contact_Login_Device $device
	 * @param string $title
	 * @param string $message
	 * @param array $additional
	 * @param string $image
	 * @return bool
	 */
	public function notify(\Ext_TS_Inquiry_Contact_Login_Device $device, string $title, string $message, array $additional = [], string $image = "") {

		if(!$this->canNotify($device)) {
			throw new \RuntimeException(sprintf('No fcm token or no push permission given for device "%s"!', $device->generateKey()));
		}

		$credentials = storage_path('firebase_credentials.json');

		if (!file_exists($credentials)) {
			throw new \RuntimeException('Missing firebase_credentials.json file');
		}

		$factory = (new Factory)->withServiceAccount($credentials);

		$messaging = $factory->createMessaging();

		// TODO 'click_action' => 'FCM_PLUGIN_ACTIVITY'
		$message = CloudMessage::withTarget('token', $device->fcm_token)
			->withNotification(Notification::create($title, $message, $image))
			->withData($additional)
			->withHighestPossiblePriority()
			->withDefaultSounds();

		try {
			$messaging->send($message);
		} catch (\Throwable $e) {
			NotificationService::getLogger('Firebase')->error('Message could not be sent', ['message' => $e->getMessage()]);
			// TODO je nach Exception verfeinern https://github.com/kreait/firebase-php/tree/7.x/src/Firebase/Exception/Messaging
			$this->errors[] = $e->getMessage();
			return false;
		}

		return true;
	}

}
