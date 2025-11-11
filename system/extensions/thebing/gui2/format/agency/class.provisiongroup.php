<?php

class Ext_Thebing_Gui2_Format_Agency_Provisiongroup extends Ext_Thebing_Gui2_Format_Format {

	public function format($mValue, &$oColumn = null, &$aResultData = null){
		global $user_data;

		$oClient = Ext_Thebing_Client::getInstance($user_data['client']);

		$aProvisionGroups = $oClient->getProvisionGroups(true);
		$mValue = $aProvisionGroups[$mValue];
	

		return $mValue;

	}

}
