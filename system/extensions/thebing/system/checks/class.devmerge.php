<?php

/**
 * 14.03.12 Der Check sort dafür, dass alle Checks die für den Dev2 Merge zuständig sind in der korrekten Reihenfolge
 * ausgeführt werden
 */

class Ext_Thebing_System_Checks_DevMerge extends GlobalChecks{

	protected $_aErrors = array();
	
	public function getTitle(){
		$sTitle = 'New data structure';
		return $sTitle;
	}

	public function getDescription(){
		$sDescription = 'All further checks are performed automatically after confirming this notification. The process can take up to 4 hours.';
		return $sDescription;
	}

	public function executeCheck(){
		
		
		// Checks in der richtigen Reihenfolge
		$aChecks = array();
		$aChecks[] = 'Ext_Thebing_System_Checks_Productlines';
		$aChecks[] = 'Ext_Thebing_System_Checks_CustomerStructure';
		$aChecks[] = 'Ext_Thebing_System_Checks_InquiryStructure';
		$aChecks[] = 'Ext_Thebing_System_Checks_BookingStructure';
		$aChecks[] = 'Ext_Thebing_System_Checks_InsuranceStructure';
		$aChecks[] = 'Ext_Thebing_System_Checks_GroupStructure';
		$aChecks[] = 'Ext_Thebing_System_Checks_InquiryCache';
		$aChecks[] = 'Ext_Thebing_System_Checks_AccommodationCache';
		$aChecks[] = 'Ext_Thebing_System_Checks_StatisticCache';
		$aChecks[] = 'Ext_Thebing_System_Checks_SpecialCache';
		$aChecks[] = 'Ext_Thebing_System_Checks_FeedbackIndexes';
		$aChecks[] = 'Ext_Thebing_System_Checks_ResidentialAccommodationUpdate';
		$aChecks[] = 'Ext_Thebing_System_Checks_Fonts';
		$aChecks[] = 'Ext_Thebing_System_Checks_DBStructure';
		$aChecks[] = 'Ext_Thebing_System_Checks_CourseWeeksUnits';

		// Mail Schicken das Begonnen wird mti den Checks
		$this->_aErrors[] = 'START Merge';
		$this->_reportError();
	
		// Prüfen, ob alle Checks da sind
		$bCheckMissing = false;
		foreach($aChecks as $sCheck) {
			if(!class_exists($sCheck)) {
				$this->_aErrors[] = 'Class "'.$sCheck.'" is missing!';
				$bCheckMissing = true;
			}
		}
		
		// Falls ein Check nicht da ist, Report senden und abbrechen
		if($bCheckMissing === true) {
			$this->_reportError();
			return false;
		}

		$iStartTime = time();
		
		foreach($aChecks as $iCount => $sCheck) {

			set_time_limit(3600 * 4);				#4h limit
			ini_set("memory_limit", '2G');			#2GB

			$this->_aErrors = array();
			
			// Wann ist der Check gestrtet
			$iStartTimeCheck = time();
					
			$oCheck = new $sCheck();
			$bResult = $oCheck->executeCheck();
			
			// Laufzeit
			$sRuntime = time() - $iStartTimeCheck;
			$sMinutes = $sRuntime / 60;
			
			if($bResult){
				$this->_aErrors[] = 'Check erfolgreich: ' . $sCheck;
				$this->_aErrors[] = 'Laufzeit: ' . $sRuntime . ' s ('.$sMinutes.' min)';
				$this->_reportError();
			}else{
				$this->_aErrors[] = 'Check FEHLER: ' . $sCheck;
				$this->_reportError();
				return true;
			}

		}
		
		// Wenn alle Checks erfolgreich durchgeführt werden konnten
		$sRuntime = time() - $iStartTime;
		$sMinutes = $sRuntime / 60;
		
		$this->_aErrors = array();
		$this->_aErrors[] = ' ALLE Checks erfolgreich';
		$this->_aErrors[] = 'laufzeit: ' . $sRuntime . ' s ('.$sMinutes.' min)';
		$this->_reportError();

		return true;
		
	}

	protected function _reportError(){
		$oMail = new WDMail();
		$oMail->subject = 'DEV2 MERGE';

		$sText = '';
		$sText = $_SERVER['HTTP_HOST']."\n\n";
		$sText .= date('Y-m-d H:i:s')."\n\n";
		$sText .= print_r($this->_aErrors, 1)."\n\n";

		$oMail->text = $sText;

		$oMail->send(array('m.durmaz@thebing.com'));

	}
	
}
