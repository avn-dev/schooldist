<?php

class Ext_TC_System_CronJob_Update_ExchangeRate extends Ext_TC_System_CronJob_Update {
	
	public $bIgnoreExecutionError = true;
	
	/**
	 * gibt den Klassennamen zurück
	 */
	public function getClassName() {
		return get_class($this);
	}

	protected function getUpdateData(){
		$aPostData = array();

		return $aPostData;
	}

	public function executeUpdate(){
		
		$oLog = Log::getLogger('exchangerate');
		
		try {			
	
			$oTemp = new Ext_TC_Exchangerate_Table();
			$aExchangerateTables = $oTemp->getArrayList(true);
			
			foreach($aExchangerateTables as $iId => $sValue) {
				
				$iId = (int) $iId;
				
				$oExchangerate = Ext_TC_Exchangerate_Table::getInstance($iId);
				$sHour = $oExchangerate->update_time;
				
				$sCurrentHour = date('G');

				$oLog->addInfo('Check time', array('hour'=>$sHour, 'current_hour'=>$sCurrentHour));
				
				//Aktualisierungszeitpunkt überprüfen
				if($sHour == $sCurrentHour) {
					
					//XML neu auslesen
					$mUpdated = $oExchangerate->update();			

					$oLog->addInfo('Executed', array('updated'=>$mUpdated));

					if($mUpdated === false) {
						Ext_TC_Util::reportError('TC Cronjob - Fehler - ExchangeRates', print_r($oExchangerate, 1));
					}
					
				}
				
			}
			
		} catch (Exception $e) {
			
			$oLog->addInfo('Exception', array('message'=>$e->getMessage()));
			
			Ext_TC_Util::reportError('TC Cronjob - Fehler - ExchangeRates', $e);

		}

	}

}