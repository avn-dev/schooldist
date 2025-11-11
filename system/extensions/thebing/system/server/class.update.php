<?php

/*
 * Schickt Informationen an ALLE Externen Server
 */
class Ext_Thebing_System_Server_Update extends Ext_TC_System_Cronjob_Update {

    protected $_sExecutionTable = 'kolumbus_server_update';
    protected $_sServerStatusClass = 'Ext_Thebing_System_Server_Status';
	protected $sExecutionTimeField = null;

	/**
	 * Prüft, ob der Job aufgeführt werden darf
	 * @return boolean
	 */
	protected function checkExecution() {

		$bExecute = true;
		try {

			$mExecuteHour = $this->_getExecuteHour();
			$iCurrentHour = (int)date('G');

			if(
				Ext_Thebing_Util::isLocalSystem() ||
				$mExecuteHour === null ||
				$iCurrentHour === $mExecuteHour || 
				$this->bDebug
			) {
				$bExecute = true;
			} else {
				$bExecute = false;
			}

		} catch(Exception $e) {
			Ext_TC_Util::reportError('Externeserver Updatescript - Fehler', $e);
		}

		if($bExecute) {
            $bExecute = parent::checkExecution();
		} else {
			// With 0, cronjob doesn't write any database entry
			$bExecute = false;
		}

		return $bExecute;

	}

	/**
	 * gibt den Zeitpunkt zurück, an dem der Cronjob durchlaufen soll
	 * default: 0
	 * @return int
	 */
	protected function _getExecuteHour() {
		
		$iExecuteHour = null;
		
		$oClient = Ext_Thebing_Client::getFirstClient();
		$sExecutionTimeField = $this->sExecutionTimeField;
		if(!empty($sExecutionTimeField)) {
			$iExecuteHour = (int)$oClient->$sExecutionTimeField;
		}

		return $iExecuteHour;
	}

}