<?php

namespace Core\Handler\ParallelProcessing;

use Core\Exception\ParallelProcessing\TaskException;
use Illuminate\Events\NullDispatcher;

abstract class TypeHandler {

	/**
	 * @TODO Achtung: Hier darf kein weiterer Fehler/Exception (außer TaskException) passieren, sonst stirbt das ganze PP-Child unwiderruflich
	 *
     * Wird nach der execute Methode ausgeführt
     *
     * @param array $aData
     * @param bool $bExecuted
     */
    public function afterAction(array $aData, $bExecuted) { }
	
	/**
	 * @TODO Achtung: Hier darf kein weiterer Fehler/Exception (außer TaskException) passieren, sonst stirbt das ganze PP-Child unwiderruflich
	 *
	 * Bietet die Möglichkeit der Exception weitere Informationen beizufügen
	 * 
	 * @param array $aData
	 * @param \Core\Exception\ParallelProcessing\TaskException $oException
	 */
	public function handleException(array $aData, TaskException $oException) { }
	
	/**
	 * Task ausführen
	 *
	 * @param array $aData
	 * @param bool $bDebug
	 * @return bool
	 */
	abstract public function execute(array $data, $debug = false);

	/**
	 * Task ohne Events ausführen
	 *
	 * @param array $aData
	 * @param $bDebug
	 * @return bool
	 */
	final public function executeQuietly(array $aData, $bDebug = false) {

		$dispatcher = (app()->has('events')) ? app('events') : null;

		if ($dispatcher) {
			app()->instance('events', new NullDispatcher($dispatcher));
		}

		try {

			return $this->execute($aData, $bDebug);

		} finally {
			if ($dispatcher) {
				app()->instance('events', $dispatcher);
			}
		}

	}

	/**
	 * Gibt den Name für ein Label zurück
	 *
	 * @return string
	 */
	abstract public function getLabel();
	
	/**
	 * @param array $data
	 * @param array $errorData
	 * @return string
	 */
	public function getErrorDescription(array $data, array $errorData = []) {
		return '';
	}

	/**
	 * @TODO Achtung: Hier darf kein weiterer Fehler/Exception (außer TaskException) passieren, sonst stirbt das ganze PP-Child unwiderruflich
	 *
	 * Wenn Task wiederholt RewriteException wirft: Maximale Anzahl, bis Task in den Error-Stack eingetragen wird
	 *
	 * @return int
	 */
	public function getRewriteAttempts() {
		return 1;
	}

}