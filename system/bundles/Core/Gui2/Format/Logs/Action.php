<?php

namespace Core\Gui2\Format\Logs;

class Action extends \Ext_Gui2_View_Format_Abstract {
	
	private $aMessages;
	
	public function __construct() {
		$this->aMessages = \Log::getLogMessages();
	}
	
	/**
	 * @param string $mValue
	 * @param object $oColumn
	 * @param array $aResultData
	 * @return string
	 */
	public function format($mValue, &$oColumn = null, &$aResultData = null) {
				
		if(array_key_exists($mValue, $this->aMessages)) {
			
			$mValue = $this->aMessages[$mValue];
			
			if(!empty($aResultData['additional'])) {
				$aAdditional = json_decode($aResultData['additional'], true);
				
				array_walk(
					$aAdditional, 
					function($sValue, $sKey) use(&$mValue) {
						$mValue = str_replace('{'.$sKey.'}', $sValue, $mValue);
					}

				);
			}
			
			return $mValue;
		}
		
		return $mValue;
	}
	
}

