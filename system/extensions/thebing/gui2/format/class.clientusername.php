<?php

class Ext_Thebing_Gui2_Format_ClientUsername extends Ext_Gui2_View_Format_UserName {

	/**
	 * @param $mValue
	 * @param null $oColumn
	 * @param null $aResultData
	 * @return mixed|string
	 */
	public function format($mValue, &$oColumn = null, &$aResultData = null) {

		$sName = '';

		if(!empty($mValue)) {

			$oUser = Ext_Thebing_Client::getInstance();
			$aUsers = $oUser->getUsers();

			foreach($aUsers as $iId => $sUser) {
				if((int)$mValue === $iId) {
					$sName = $sUser;
					break;
				}
			}

		}

		return $sName;

	}

}