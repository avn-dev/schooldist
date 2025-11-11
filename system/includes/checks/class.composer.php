<?php

class Checks_Composer extends GlobalChecks {
	
	public function executeCheck() {
		
		$oUpdate = new Update();
		$mSuccess = $oUpdate->executeComposerUpdate();

		return $mSuccess;

	}
	
	public function getTitle() {
		return 'Updating external components';
	}
	
	public function getDescription() {
		return 'The process can take several minutes.';
	}

	
}
