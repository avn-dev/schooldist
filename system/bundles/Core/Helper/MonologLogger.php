<?php

namespace Core\Helper;

/**
 * Monolog(>2.0) hat Methoden umbenannt
 *
 * @todo: Umstellen auf \Monolog\Logger
 * @package Core\Helper
 */
class MonologLogger extends \Monolog\Logger {

    /**
     * @deprecated
     * @param $sMessage
     * @param array $aContext
     */
    public function addInfo($sMessage, array $aContext = []): void {
        $this->info($sMessage, $aContext);
    }

    /**
     * @deprecated
     * @param $sMessage
     * @param array $aContext
     */
    public function addError($sMessage, array $aContext = []): void {
        $this->error($sMessage, $aContext);
    }

    /**
     * @deprecated
     * @param $sMessage
     * @param array $aContext
     */
    public function addWarning($sMessage, array $aContext = []): void {
        $this->warning($sMessage, $aContext);
    }

    /**
     * @deprecated
     * @param $sMessage
     * @param array $aContext
     */
    public function addNotice($sMessage, array $aContext = []): void {
        $this->notice($sMessage, $aContext);
    }

    /**
     * @deprecated
     * @param $sMessage
     * @param array $aContext
     */
    public function addDebug($sMessage, array $aContext = []): void {
        $this->debug($sMessage, $aContext);
    }

}
