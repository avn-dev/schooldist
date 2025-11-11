<?php

abstract class Ext_Gui2_View_Event_Abstract implements Ext_Gui2_View_Event_Interface {

	protected $aOption;
	
	public function __construct(Ext_Gui2 $oGui=null) {
	}
	
	public function __get($sOption){

		return $this->aOption[$sOption];
	}

	public function __set($sOption, $mValue){
		$this->aOption[$sOption] = $mValue;
	}

	// setzt den eventhandler
	public function getEvent($mValue, $oColumn, $aResultData){
		return 'click';
	}

	// setzt die JS Funktion
	public function getFunction($mValue, $oColumn, $aResultData){
		$aFunction = array();
		$aFunction['name'] = 'testAlert';

		$aArgs = array();
		$aArgs[] = 'alert123';

		$aFunction['args'] = $aArgs;

		return $aFunction;
	}


}
