<?php

namespace TsFlywire\Hook;

use TsFlywire\Service\SyncFiles;

class HourlyCronjobHook extends \Core\Service\Hook\AbstractHook {

	public function run() {

		if(\TcExternalApps\Service\AppService::hasApp(\TsFlywire\Handler\ExternalAppSync::APP_NAME)) {
			
			$oLog = SyncFiles::logger();

			// Standardwert: 4 Uhr
			$sHour = \System::d('flywire_sync_time', '4');

			$sCurrentHour = date('G');

			$oLog->info('Check time', array('hour'=>$sHour, 'current_hour'=>$sCurrentHour));

			//Aktualisierungszeitpunkt überprüfen
			if($sHour == $sCurrentHour) {
								
				$aConfig = [
					'ssh_host' => 'sftp.flywire.com',
					'ssh_port' => 22,
					'ssh_user' => \System::d('flywire_ssh_user'),
					'ssh_key' => \Util::getDocumentRoot().'../.ssh/flywire-production.key',
					'prefix' => \System::d('flywire_prefix'),
					'currency' => \System::d('flywire_currency'),
					'method_id' => \System::d('flywire_method_id')
				];

				$oLog->info('Flywire hook', (array)$aConfig);

				$oService = new \TsFlywire\Service\SyncFiles($aConfig);
				$oService->sync();

				\Ext_Gui2_Index_Stack::save(true);
				
			}
			
		}

	}

}