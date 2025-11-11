<?php

class Ext_Gui2_View_Format_Selection extends Ext_Gui2_View_Format_Abstract {

	public $aSelectOptions = array();

	public function  __construct($aSelection=array()) {
		$this->aSelectOptions = $aSelection;
	}

	public function format($mValue, &$oColumn = null, &$aResultData = null){

		$aSelection = array();

		$aTemp = $this->aSelectOptions;

		if(empty($aTemp)){
			$aSelection[0] = L10N::t('No');
			$aSelection[1] = L10N::t('Yes');
		} else if(is_array($aTemp)) {
			$aSelection = $this->aSelectOptions;
		} else if($aTemp instanceof Ext_Gui2_View_Selection_Abstract){
			$oWDBasic = new stdClass();
			$aTemp->setGui($this->oGui);
			$aSelection = $aTemp->getOptions(array(), array(), $oWDBasic);
		}

		$mValue = (string)($aSelection[$mValue] ?? '');

		return $mValue;

	}

}