<?php

namespace Gui2\Service;

/**
 * Monitoring-Service
 */
class MonitoringService {

	/**
	 * @var string
	 */
	protected $_sTask;

	/**
	 * @var string
	 */
	protected $_sAction;

	/**
	 * @var string
	 */
	protected $_sHash;

	/**
	 * @var float
	 */
	protected $_fDuration;

	/**
	 * @var float
	 */
	protected $_fVersion;

	/**
	 * @var float
	 */
	protected $_fStart;

	/**
	 * @var string
	 */
	protected $_sLogFile;

	/**
	 * @param array $aRequest
	 * @param null  $fStart
	 */
	public function __construct(array $aRequest, $fStart=null) {
		
		if($fStart === null) {
			$this->_fStart = microtime(true);
		} else {
			$this->_fStart = $fStart;
		}

		$this->_sHash = $aRequest['hash'];
		$this->_sAction = $aRequest['action'] ?? null;
		$this->_sTask = $aRequest['task'] ?? null;

		$this->_sLogFile = \Util::getDocumentRoot().'storage/gui2/logs/monitoring.log';
		
	}

	/**
	 * Action nachträglich ändern
	 * @param string $sAction
	 */
	public function setAction($sAction) {
		$this->_sAction = $sAction;
	}
	
	/**
	 * Action nachträglich ändern
	 * @param string $sAction
	 */
	public function setTask($sTask) {
		$this->_sTask = $sTask;
	}
	
	/**
	 * Logeintrag speichern
	 */
	public function save() {

		$rFile = fopen($this->_sLogFile, 'a');

		$this->_fDuration = microtime(true) - $this->_fStart;

		$oDb = \DB::getDefaultConnection();
		
		$aData = array(
			'timestamp' => gmdate('Y-m-d H:i:s'),
			'hash' => $this->_sHash,
			'task' => $this->_sTask,
			'action' => $this->_sAction,
			'ip' => $_SERVER['REMOTE_ADDR'],
			'version' => \System::d('version'),
			'licence' => \System::d('license'),
			'duration' => $this->_fDuration,
			'queries' => $oDb->getQueryCount(),
			'php_version' => \Util::getPHPVersion()
		);

		fwrite($rFile, json_encode($aData)."\n");
		fclose($rFile);

	}
	
}