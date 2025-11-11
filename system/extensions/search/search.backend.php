<?php

class search_backend {
	
	function executeHook($strHook, &$mixInput) {

		switch($strHook) {
			case "tc_cronjobs_hourly_execute":
				
				$oLog = \Log::getLogger('search');
				
				$sHour = '1';
				$sCurrentHour = date('G');

				$oLog->addInfo('Cronjob: Check time', array('hour'=>$sHour, 'current_hour'=>$sCurrentHour));
				
				//Aktualisierungszeitpunkt überprüfen
				if($sHour === $sCurrentHour) {
					
					$oRepository = \Core\Entity\ParallelProcessing\Stack::getRepository();
					/* @var $oRepository \Core\Entity\ParallelProcessing\StackRepository */
					
					$oLog->addInfo('Cronjob: Start update index');
					
					$oRepo = \Site::getRepository();
					$aSites = $oRepo->findAll();

					foreach($aSites as $oSite) {

						$oLog->addInfo('Cronjob: Add to stack', [$oSite->name]);
						$oRepository->writeToStack('search/index', ['site_id'=>$oSite->id], 10);

					}

					$oLog->addInfo('Cronjob: End update index');
					
				}
				
				break;
		}
		
	}

}

System::wd()->addHook('tc_cronjobs_hourly_execute', 'search');