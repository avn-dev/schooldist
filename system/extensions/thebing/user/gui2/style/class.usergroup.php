<?php

class Ext_Thebing_User_Gui2_Style_UserGroup extends Ext_Gui2_View_Style_Abstract {

	/**
	 * @param mixed $mValue
	 * @param $oColumn
	 * @param array $aRowData
	 * @return string
	 */
	public function getStyle($mValue, &$oColumn, &$aRowData) {

		$oAccessUser = Ext_Thebing_Access_User::getInstance($aRowData['id']);
		$aAccessList = $oAccessUser->getAccessList();

		$sStyle = '';
		if(!empty($aAccessList)) {
			$sStyle = 'background-color: '.Ext_Thebing_Util::getColor('neutral');
		}

		return $sStyle;
	}

}