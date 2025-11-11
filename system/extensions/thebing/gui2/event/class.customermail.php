<?php

class Ext_Thebing_Gui2_Event_Customermail extends Ext_Gui2_View_Event_Abstract {

	public $sAdditional = null;
	
	public function __construct(Ext_Gui2 $oGui=null) {
		
		// Es gibt eine Ableitung, in der das Absichtlich gesetzt ist
		if($this->sAdditional !== null) {
			return;
		}

		if ($oGui) {
			// Additional vom erstbesten Kommunikation-Icon ermitteln
			foreach($oGui->getBarList() as $iPosition => $oBar) {

				$aElements = $oBar->getElements();

				foreach ($aElements as $oElement) {
					if(
						$oElement instanceof Ext_Gui2_Bar_Icon &&
						$oElement->action == 'communication'
					) {
						$this->sAdditional = $oElement->additional;
						break 2;
					}
				}
			}
		}

		// Fallback
		if($this->sAdditional === null) {
			$this->sAdditional = 'inbox';
		}
		
	}
	
	public function getEvent($mValue, $oColumn, $aResultData){

		if(
			!empty($mValue)
		) {
			return 'click';
		}
	}

	public function getFunction($mValue, $oColumn, $aResultData){

		$aFunction = array();
		$aFunction['name'] = 'openCommunicationDialog';

		$aArgs = array();
		$aArgs[] = 'communication';
		$aArgs[] = $this->sAdditional;
		$aArgs[] = 'request';

		$aFunction['args'] = $aArgs;

		return $aFunction;
	}

}