<?php

class Ext_Gui2_View_Format_UserName extends Ext_Gui2_View_Format_Name {

	protected $_bGetById;

	public function __construct($bGetById=false)
	{
		$this->_bGetById = $bGetById;
	}

	public function format($mValue, &$oColumn = null, &$aResultData = null){

		// Refferenz umgehen da wir 2 Username spalten haben
		$aTemp = [];
		if(!empty($aResultData)) {
			// Wenn 'lastname' und 'firstname' in $aResultData existieren dann diese benutzen
			$aTemp = array_intersect_key($aResultData, array_flip(['lastname', 'firstname']));
		}

		if(
			(
				!isset($aTemp['lastname']) &&
				!isset($aTemp['firstname'])
			) ||
			$this->_bGetById
		) {
			if($mValue > 0) {
				// TODO Das sollte unter keinen Umständen mehr ausgeführt werden
				$oUser = User::getInstance((int)$mValue);
				$aTemp['lastname'] = $oUser->lastname;
				$aTemp['firstname'] = $oUser->firstname;
			} else {
				$aTemp['lastname'] = '';
				$aTemp['firstname'] = '';
			}
		}
		
		$sName = parent::format($mValue, $oColumn, $aTemp);

		return $sName;

	}

}
