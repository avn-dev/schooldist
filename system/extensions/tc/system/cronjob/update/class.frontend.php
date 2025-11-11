<?php

/**
 * Klasse, die vom Cronjob aufgerufen wird um den Frontend-Elemente zu aktualisieren.
 */
class Ext_TC_System_CronJob_Update_Frontend extends Ext_TC_System_CronJob_Update {

	/**
	 * @var bool
	 */
	public $bIgnoreExecutionError = true;

	/**
	 * Für Schulsoftware
	 *
	 * @var bool
	 */
	public $bCheckExecutionHour = true;

	/**
	 * @inheritdoc
	 */
	public function executeUpdate() {

		try {
			
			$iExecuteHour = $this->getExecuteHour();
			$iCurrentHour = (int)date('G');

			set_time_limit(3600 * 3);

			if(
				!$this->bCheckExecutionHour ||
				$iCurrentHour == $iExecuteHour
			) {

				$oCombinationProcessing = new Ext_TC_Frontend_Combination_Helper_ParallelProcessing();
				$oCombinationProcessing->updateAll();

			}

		} catch(Exception $e) {
			Ext_TC_Util::reportError('TC Cronjob - Fehler - Update Frontend', $e);
		}

	}

	/**
	 * @return int
	 */
	protected function getExecuteHour() {
		$oConfig = \Factory::getInstance('Ext_TC_Config');
		$iExecuteHour = (int)$oConfig->getValue('execution_time_frontend_refreshing');

		return $iExecuteHour;
	}

}