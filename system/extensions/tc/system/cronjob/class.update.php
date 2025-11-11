<?php

class Ext_TC_System_Cronjob_Update {

    protected $_sExecutionTable = 'tc_system_cronjobs';
    protected $_sServerStatusClass = 'Ext_TC_System_CronJob_Status';

    protected $sRequestFile = '/system/extensions/tc/system/cronjob/request/request.php';

	// Ausführungsperiode (min)
	public $iCronjobPeriodInMinutes = 1440; // 1 Tag 1440
	// Check Cronjob Period
	public $bUseCronjobPeriod		= false; // false
	// Ignore Execution Errors
	public $bIgnoreExecutionError	= false; // false
	// Timestamp des Cronjobs
	public $iExecutionTime			= 0;

    public $bDebug                  = false;
    
	/**
	 * gibt den Klassennamen zurück
	 */
	public function getClassName() {
		return get_class($this);
	}

	static protected function log($sAction, $aAdditional=array()) {
		
		$oLog = Log::getLogger('cronjob');
		$oLog->addInfo($sAction, $aAdditional);

	}

	static protected function logError($sAction, $aAdditional=array()) {
		
		$oLog = Log::getLogger('cronjob');
		$oLog->addError($sAction, $aAdditional);

	}
	
	/**
	 * bereitet die POST Daten auf welche an die Externenserver gehen
	 */
	protected function getUpdateData(){
		$aPostData = array();
		return $aPostData;
	}

	/**
	 * Führt das Update durch
	 */
	public function executeUpdate(){
		//Ext_TC_Util::reportError('Externeserver Updatescript - DEBUG WRONG Execute', print_r($_VARS, 1));
	}

	/**
	 * Bereitet den Start des Cronjobs auf der Installation vor
	 * @todo Rückgabe einbauen, die request.php muss Feedbach ausgeben
	 */
	final static public function evaluateData($bDebug = false){
		global $_VARS;

		// Wichtig, weil das ansonsten nicht passiert und die Server normalerweise UTC-Zeit haben!
		Ext_TC_Util::getAndSetTimezone();

		// Das ist sehr wichtig, weil der eingehende CURL-Request sofort die Verbindung beendet! (synchron)
		ignore_user_abort(true);

		// Ping request
		if(empty($_VARS['class'])) {
			return 'ping_successful';
		}
		
		self::log('START '.$_VARS['class'], (array)$_VARS);
		
		if(!self::checkAccessKey($_VARS['update_access'])){
			Ext_TC_Util::reportError('TC Cronjob - Falscher AccessKey!', print_r(array('VARS'=> $_VARS, 'CLASS' => get_called_class()), 1));
			self::logError($_VARS['class'].' - Wrong access key', (array)$_VARS);
			return 'Wrong_access_key';
		}

		$sReturn = '';

		if(class_exists($_VARS['class'])) {

			/** @var static $oUpdate */
			$oUpdate = new $_VARS['class']();

			// Ausführungszeit des Cronjobs
			$oUpdate->iExecutionTime = time();
            
            if($bDebug){
                $oUpdate->bDebug = $bDebug;
            }

			// Prüft ob Cronjob ausgeführt werden darf und macht DB Eintrag
			$iInsertId = $oUpdate->checkExecution();

			if($iInsertId > 0) {
				try {

					// Cronjob ausführen
					$oUpdate->executeUpdate();

					// Cronjob als erledigt markieren in der DB
					$oUpdate->finishExecution($iInsertId);
                    $sReturn = 'success';

				} catch (\Throwable $e) {

					// Bei Timeout, Speicherüberlauf und Segfault wird es KEINE Benachrichtigung geben!
					// Der einzige Hinweis auf sowas lässt sich nur im Server-Log (PHP-FPM, nginx/apache2 errorlog) finden.
					$sReturn = 'update_class_exception';

					self::log($sReturn, ['message' => $e->getMessage(), 'file' => $e->getFile(), 'line' => $e->getLine()]);
					Ext_TC_Util::reportError('TC Cronjob - Fehler beim Ausführen des Cronjobs!', print_r($e, 1).print_r($_VARS, 1));

				}
			}
		} else {
            $sReturn = 'update_class_not_found';
            // schwachsinniger fehlermeldung
            // da die checks in core früher da sind passiert das immer wenn neue cronjobs angelegt werden
			//Ext_TC_Util::reportError('Externeserver Updatescript - Update Klasse existiert nicht', print_r($_VARS, 1));
		}

		self::log('END '.$_VARS['class'], array('return' => $sReturn, 'request' => $_VARS));

        return $sReturn;
	}

	/**
	 * Überprüft einen Schlüssel der zur Berechtigungsprüfung genutzt wird
	 */
	static public function checkAccessKey($sKey){
		global $system_data;

		$oWDAES = new WDAES();
		$sUrl = $oWDAES->decrypt($sKey, $system_data['license']);

		$sUrl = str_replace('https://', '', $sUrl);
		$sUrl = str_replace('http://', '', $sUrl);
		$sUrl = str_replace('www.', '', $sUrl);
		$aTemp = explode('/', $sUrl);
		$sUrl = reset($aTemp);

		if($sUrl == $_SERVER['HTTP_HOST']){
			return true;
		}

		return false; 
	}

	/**
	 * Prüft ob Cronjob ausgeführt werden darf
	 */
	protected function checkExecution(){

        if($this->bDebug){
            return 1;
        }
        
		$sSql = "
			SELECT
				`ksu`.*,
				UNIX_TIMESTAMP(`ksu`.`time_start`) `time_start`,
				UNIX_TIMESTAMP(`ksu`.`time_end`) `time_end`
			FROM
				#table `ksu`
			WHERE
				`ksu`.`class_name` = :class_name
			ORDER BY
				`ksu`.`time_start` DESC
			LIMIT 1
				";
		$aSql = array();
		$aSql['class_name'] = get_class($this);
        $aSql['table']      = $this->_sExecutionTable;

		$aResult = DB::getQueryRow($sSql, $aSql);

		if(
			isset($aResult['time_end']) &&
			$aResult['time_end'] == 0
		){
			// Fehler ist aufgetreten
			if($this->bIgnoreExecutionError) {
				// Periode kann ignoriert werden da fehler
				return $this->insertExecutionStatus();

			} else {
				Ext_TC_Util::reportError('TC Cronjob - Cronjob abgebrochen! ('.$_SERVER['HTTP_HOST'].')', print_r($aResult,1));

				return 0;
			}

		} else {

			// Wurde erfolgreich beendet
			$bCheck = $this->checkPeriod($aResult['time_end']);

			if($bCheck) {
				return $this->insertExecutionStatus();
			} else {
				return 0;
			}

		}

	} 

	/**
	 * Prüft ob Cronjob erneut ausgeführt werden darf
	 */
	final protected function checkPeriod($iLastExecution){

		if(!$this->bUseCronjobPeriod){
			return true;
		} elseif((int)$iLastExecution == 0) {
			// Wurde noch nie gestartet und wird jetzt das 1. mal gestartet
			return true;
		} else {

			$iTimeDiffSec = $this->iExecutionTime - (int)$iLastExecution;

			$iTimeDiffMin = $iTimeDiffSec / 60;

			if($iTimeDiffMin >= $this->iCronjobPeriodInMinutes) {
				return true;
			} else {
				return false;
			}

		}
	}

	/**
	 * Setzt den Cronjob auf aktiv in der DB
	 */
	final protected function insertExecutionStatus(){

        $sClass = $this->_sServerStatusClass;
        
		$oCronjobUpdate = new $sClass();

		$oCronjobUpdate->class_name = get_class($this);

		$oCronjobUpdate->time_start = $this->iExecutionTime;

		$bValidate = $oCronjobUpdate->validate();

		if($bValidate === true){
            $oCronjobUpdate->save();

			$iId = $oCronjobUpdate->id;

			return $iId;
		} else {
			Ext_TC_Util::reportError('Externeserver Updatescript - Ext_Thebing_System_Server_Status Validate Error', print_r($bValidate, 1));
		}

	}

	/**
	 * Setzt den Cronjob auf erledigt
	 */
	protected function finishExecution($iStatusId){
		if($iStatusId > 0 && !$this->bDebug){
            $sClass = $this->_sServerStatusClass;
			$oCronjobUpdate = new $sClass($iStatusId);
			$oCronjobUpdate->time_end = time();
			$oCronjobUpdate->save();
		}
	}

}
