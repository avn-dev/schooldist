<?
// DEMO Klasse hier kann mit $oCol->event ein JS Event gestartet werden
class Ext_Gui2_View_Event_Edit extends Ext_Gui2_View_Event_Abstract {


	public function getEvent($mValue, $oColumn, $aResultData){

		return 'click';
	}

	public function getFunction($mValue, $oColumn, $aResultData){
		$aFunction = array();
		$aFunction['name'] = 'test';

		$aArgs = array();
		$aArgs[] = 1;
		$aArgs[] = 'abcdef';

		$aFunction['args'] = $aArgs;

		return $aFunction;
	}

}