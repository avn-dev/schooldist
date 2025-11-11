<?php

namespace TsFrontend\Hook;

use Core\Service\Hook\AbstractHook;

class TrackingCookieHook extends AbstractHook {

	public function run($pageData, \MVC_Request $request) {

		$trackingKey = \System::d('ts_registration_form_tracking_key');
		if ($trackingKey) {
			if($request->filled($trackingKey)) {
				$cookieHandler = \Core\Handler\CookieHandler::getInstance();
				$cookieHandler->setValue($trackingKey, $request->get($trackingKey), 0);
			}
		}
		
	}

}
