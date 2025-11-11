<?php

require_once(\Util::getDocumentRoot()."system/extensions/counter/counter.inc.php");

class counter_frontend {

	function executeHook($strHook, &$mixInput) {
		global $_VARS;

		switch($strHook) {
			case "stats_insert":

				$objCounter = new Counter();
				$objCounter->updateCounter();			

				break;
			
			default:
				break;
		}

	}
	
}

\System::wd()->addHook('stats_insert', 'counter');

?>