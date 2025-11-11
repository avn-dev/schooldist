<?php

class Ext_TC_User_Format_Email extends Ext_TC_Gui2_Format { 

	public function format($mValue, &$oColumn = null, &$aResultData = null){

		$iId = (int) $aResultData['id'];
		
		$oUser = Ext_TC_User::getInstance($iId);
		
		$sStandardEmail = Ext_TC_Factory::executeStatic('Ext_TC_Object', 'getStandardEmailAddress');

		$sUserEmail = $oUser->email;
		$aReturn = array(
			'user' =>$sUserEmail,
			'system' => $sStandardEmail
		);
		
		$aEmailAccounts = Ext_TC_Communication_EmailAccount::getSelectOptions(true, $oUser->id);
		foreach($aEmailAccounts as $iId => $sMail) {
			$aReturn[$iId] = $sMail;
		}
		
		$sReturn = $aReturn[$oUser->send_email_account];
		
		return $sReturn;
		

	}
}