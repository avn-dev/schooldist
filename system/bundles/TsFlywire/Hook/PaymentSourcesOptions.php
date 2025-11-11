<?php

namespace TsFlywire\Hook;

class PaymentSourcesOptions extends \Core\Service\Hook\AbstractHook {

	public function run(array &$options) {

		if (!\TcExternalApps\Service\AppService::hasApp(\TsFlywire\Handler\ExternalAppSync::APP_NAME)) {
			return;
		}

		$options['flywire'] = 'Flywire';
	}

}