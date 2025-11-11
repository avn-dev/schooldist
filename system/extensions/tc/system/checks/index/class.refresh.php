<?php

abstract class Ext_TC_System_Checks_Index_Refresh extends GlobalChecks {

	abstract public function getIndexName();

	public function executeCheck() {

		set_time_limit(3600);
		ini_set('memory_limit', '1024M');

		try {
			Ext_Gui2_Config_Parser::clearWDCache();

			$oGenerator = new Ext_Gui2_Index_Generator($this->getIndexName());
			$oGenerator->fillStack();

		} catch(Exception $exc) {
			__pout($exc);
		}

		return true;

	}

}
