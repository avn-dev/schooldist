<?php

namespace TsStudentApp\Service;

use Illuminate\Support\Facades\Hash;
use TcFrontend\Service\Auth\Token;
use TsStudentApp\Device;

class LoggingService {

	const LOGGER_FILE = 'ts-student-app';

	/**
	 * Log page action
	 *
	 * @param AccessService $accessService
	 * @param string $page
	 * @param string $action
	 */
	public function pageAction(AccessService $accessService, string $page, string $action) {
		\DB::insertData('ts_student_app_page_log', [
			'token_hash' => md5($accessService->getAccessToken()),
			'page_key' => $page,
			'page_action' => $action,
		]);
	}

	/**
	 * Log device
	 *
	 * @param \Ext_TS_Inquiry_Contact_Login $login
	 * @param Device $device
	 * @param string $appVersion
	 */
	public function device(\Ext_TS_Inquiry_Contact_Login $login, Device $device, string $appVersion, string $appEnvironment = null) {

		$loginDevice = $device->getLoginDevice($login);

		if($loginDevice === null) {
			$loginDevice = new \Ext_TS_Inquiry_Contact_Login_Device();
			$loginDevice->login_id = $login->getId();
			$loginDevice->app_id = $device->appId;
		}

		$loginDevice->app_version = $appVersion;
		$loginDevice->app_environment = $appEnvironment;
		$loginDevice->os = $device->os;
		$loginDevice->os_version = $device->version;
		$loginDevice->last_action = time();

		if (!empty($device->additional)) {
			$loginDevice->additional = json_encode($device->additional);
		}

		$loginDevice->save();

		$device->id = $loginDevice->getId();
	}

	public function error(\Throwable $e) {
		$message = sprintf('%s: %s', $e instanceof \Exception ? 'Exception' : 'Error', $e->getMessage()).$e->getMessage();
		$this->getLogger()->error($message, [
			'message' => $e->getMessage(),
			'type' => get_class($e),
			'file' => $e->getFile(),
			'line' => $e->getLine(),
			'trace' => $e->getTraceAsString()
		]);
	}

	public function info($message, array $context = []) {
		$context['headers'] = app('request')?->headers->all();
		$this->getLogger()->info($message, $context);
	}

	/**
	 * Get Monolog logger
	 *
	 * @return \Monolog\Logger
	 */
	public static function getLogger() {
		return \Log::getLogger(self::LOGGER_FILE);
	}

}
