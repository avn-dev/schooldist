<?php

class Ext_Thebing_Gui2_Selection_Saisons extends Ext_Gui2_View_Selection_Abstract {

	protected $iSchoolIdField;
	protected $bAddEmptyItem;

	public function __construct($sSchoolIdField=null, $bAddEmptyItem=true) {
		$this->iSchoolIdField = $sSchoolIdField;
		$this->bAddEmptyItem = $bAddEmptyItem;
	}
	
    public function getOptions($aSelectedIds, $aSaveField, &$oWDBasic) {
		global $user_data;

		if(empty($this->iSchoolIdField)) {
			$iClientId	= (int)$user_data['client'];
			$iSchoolId	= (int)\Core\Handler\SessionHandler::getInstance()->get('sid');
		} else {
			$iSchoolId = $oWDBasic->{$this->iSchoolIdField};
		}
		
		$sInterfaceLanguage = Ext_Thebing_School::fetchInterfaceLanguage();
		
		$oSaison	= Ext_Thebing_Marketing_Saison::getInstance();
		$aSaisons = $oSaison->getSaisonList($iSchoolId, $iClientId, $sInterfaceLanguage, true, false);

		if($this->bAddEmptyItem === true) {
			$aSaisons = Ext_Thebing_Util::addEmptyItem($aSaisons);
		}

		return $aSaisons;
	}

}
