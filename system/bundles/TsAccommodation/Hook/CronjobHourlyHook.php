<?php

namespace TsAccommodation\Hook;

use Core\Service\Hook\AbstractHook;

class CronjobHourlyHook extends AbstractHook {

	public function run($aDebug) {

		try {

			// Alle Reservierungen deaktivieren, die nur bis gestern gültig waren
			$dYesterday = \Carbon\Carbon::yesterday();

			$aExpiredReservations = \Ext_Thebing_Accommodation_Allocation::getRepository()
				->getExpiredReservations($dYesterday);

			$aAffectedRows = [];
			// Auf WDBasic-Objekte umgebaut damit der Hook in der save() ausgeführt wird
			foreach ($aExpiredReservations as $oReservation) {
				$oReservation->bSkipUpdatePaymentStack = true;
				$oReservation->bPaymentGenerationDeleteCheck = false;

				$oReservation->status = 1;
				$oReservation->save();

				$aAffectedRows[] = $oReservation->id;
			}

			$oLog = \Log::getLogger('cronjob', 'Reservations');
			$oLog->info('Deactivate expired reservations', ['affected_rows' => $aAffectedRows]);

		} catch (\Throwable $e) {
			\Ext_TC_Util::reportError(__METHOD__, $e->getMessage()."\n\n".$e->getTraceAsString());
		}
		
	}

}
