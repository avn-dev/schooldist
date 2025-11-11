<?php

namespace TsAccounting\Hook;

use Core\Service\Hook\AbstractHook;

class CronjobHourlyHook extends AbstractHook {

	public function run($aDebug) {

		try {
			\TsAccounting\Service\AutomationService::startDocumentRelease(true);
		} catch(\Exception $ex) {
			\Ext_Thebing_Util::reportError('Automatische Freigabe fehlgeschlagen', $ex);
		}

		try {
			\TsAccounting\Service\AutomationService::startPaymentRelease();
		} catch(\Exception $ex) {
			\Ext_Thebing_Util::reportError('Automatische Freigabe fehlgeschlagen', $ex);
		}

		try {
			\TsAccounting\Service\AutomationService::startBookingstackExport();
		} catch(\Exception $ex) {
			\Ext_Thebing_Util::reportError('Automatische Weiterverarbeitung fehlgeschlagen', $ex);
		}

	}

}
