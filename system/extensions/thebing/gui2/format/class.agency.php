<?php

class Ext_Thebing_Gui2_Format_Agency extends Ext_Gui2_View_Format_Abstract {

	public function format($mValue, &$oColumn = null, &$aResultData = null){

		$oClient = Ext_Thebing_System::getClient();
		$aAgencies = $oClient->getAgencies(true);//Ext_Thebing_Agency::getList(true, true);

		return $aAgencies[$mValue];

	}

}