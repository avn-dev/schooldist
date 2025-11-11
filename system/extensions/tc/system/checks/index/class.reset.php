<?php

abstract class Ext_TC_System_Checks_Index_Reset extends GlobalChecks {

	protected $bFillStack = true;

	abstract public function getIndexName();

	/**
	 * @param bool $bFillStack
	 */
	public function setStackFilling($bFillStack) {
		$this->bFillStack = $bFillStack;
	}

	public function executeCheck() {

		set_time_limit(3600);
		ini_set('memory_limit', '6G');

		try {
			Ext_Gui2_Config_Parser::clearWDCache();

			$oGenerator = new Ext_Gui2_Index_Generator($this->getIndexName());
			$oGenerator->createIndexNewAndAddStack($this->bFillStack);

		} catch(Exception $exc) {
			__pout($exc->getMessage());
			__pout($exc);
		}

		return true;

	}

}