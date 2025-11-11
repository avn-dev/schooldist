<?php

/**
 * Selection-Klasse für den jeweiligen User
 */
class Ext_TC_User_Selection_EmailAccount extends Ext_Gui2_View_Selection_Abstract {
	
	public function getOptions($aSelectedIds, $aSaveField, &$oWDBasic) {
		
		$sUserEmail = $oWDBasic->email;
		$sStandardEmail = Ext_TC_Factory::executeStatic('Ext_TC_Object', 'getStandardEmailAddress');

		$aReturn = array(
			//'user' => sprintf(Ext_TC_Communication::t('Benutzer-E-Mail-Adresse: %s'), $sUserEmail),
			'system' => sprintf(Ext_TC_Communication::t('Standard-E-Mail-Adresse: %s'), $sStandardEmail)
		);
		
		$aEmailAccounts = Ext_TC_Communication_EmailAccount::getSelectOptions(true, $oWDBasic->id);
		foreach($aEmailAccounts as $iId => $sMail) {
			$aReturn[$iId] = sprintf(Ext_TC_Communication::t('Sonstige-E-Mail-Adresse: %s'), $sMail);
		}
		
		return $aReturn;
		
	}
	
}