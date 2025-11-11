<?php

namespace TsStudentApp\Messenger\Notifications;

/**
 * @deprecated see \Communication\Notifications\Channels\AppChannel
 */
class Apns extends AbstractService
{
	public function canNotify(\Ext_TS_Inquiry_Contact_Login_Device $device): bool
	{
		return $device->push_permission && !empty($device->apns_token);
	}

	public function notify(\Ext_TS_Inquiry_Contact_Login_Device $device, string $title, string $message, array $additional = [], string $image = "")
	{
		$logger = \TsStudentApp\Service\LoggingService::getLogger();
		$identifier = \System::d('communication.app.app_identifier');

		if (empty($identifier)) {
			throw new \RuntimeException('No APNS identifier');
		}

		$production = $device->app_environment === 'production'; // TODO Nicht optimal gelöst, da das vom Build in iOS abhängt
		$object = new \Communication\Services\Api\Office\SendApnsNotification(
			$identifier, $device->apns_token, $title, $message, $additional, $production, $image
		);

		$api = new \Licence\Service\Office\Api();
		$response = $api->request($object);

		if (!$response->isSuccessful()) {
			$logger->error('APNS request error', $response->all());
			return false;
		}

		return true;
	}
}