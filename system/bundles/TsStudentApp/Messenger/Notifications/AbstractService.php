<?php

namespace TsStudentApp\Messenger\Notifications;

use TsStudentApp\Service\LoggingService;

/**
 * @deprecated
 */
abstract class AbstractService {

	protected $config;

	protected $errors = [];

	public function __construct(array $config) {
		$this->config = $config;
	}

	/**
	 * Prüft ob eine Notification an das Device gesenden werden kann
	 *
	 * @param \Ext_TS_Inquiry_Contact_Login_Device $device
	 * @return bool
	 */
	abstract public function canNotify(\Ext_TS_Inquiry_Contact_Login_Device $device): bool;

	/**
	 * Notification an ein Device versenden
	 *
	 * @param \Ext_TS_Inquiry_Contact_Login_Device $device
	 * @param string $title
	 * @param string $message
	 * @param array $additional
	 * @param string $image
	 * @return mixed
	 */
	abstract public function notify(\Ext_TS_Inquiry_Contact_Login_Device $device, string $title, string $message, array $additional = [], string $image = "");

	/**
	 * @return \Monolog\Logger
	 */
	protected function getLogger() {

		/** @var LoggingService $loggingService */
		$loggingService = app()->make(LoggingService::class);

		return $loggingService->getLogger();

	}

	/**
	 * @return array
	 */
	public function getErrors() {
		return $this->errors;
	}
	
}
