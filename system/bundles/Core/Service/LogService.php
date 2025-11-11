<?php

namespace Core\Service;

/**
 * Diese Klasse verwaltet die Bundles.
 */
class LogService {

    // Wrapper-Methoden

    /**
     * @deprecated
     * @param $sMessage
     * @param $aAdditional
     */
    public function addInfo($sMessage, $aAdditional = null) {
        $this->info($sMessage, $aAdditional);
    }

    /**
     * @deprecated
     * @param $sMessage
     * @param $aAdditional
     */
    public function addError($sMessage, $aAdditional = null) {
        $this->error($sMessage, $aAdditional);
    }

    /**
     * @deprecated
     * @param $sMessage
     * @param $aAdditional
     */
    public function addDebug($sMessage, $aAdditional = null) {
        $this->debug($sMessage, $aAdditional);
    }

    public function info($sMessage, $aAdditional = null){
        $this->add(LOG_INFO, $sMessage, $aAdditional);
    }

    public function error($sMessage, $aAdditional = null){
        $this->add(LOG_ERR, $sMessage, $aAdditional);
    }

    public function debug($sMessage, $aAdditional = null){
        $this->add(LOG_DEBUG, $sMessage, $aAdditional);
    }

	public function add($iPriority, $sMessage, $aAdditional = null) {
		
		if($aAdditional !== null) {
			$sMessage .= ' '.json_encode($aAdditional);
		}
		
		syslog($iPriority, $sMessage);
	}


	
}
