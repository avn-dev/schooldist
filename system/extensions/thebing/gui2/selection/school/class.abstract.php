<?php

abstract class Ext_Thebing_Gui2_Selection_School_Abstract extends Ext_Gui2_View_Selection_Abstract {

	protected $iSchoolIdField;
	protected $bAddEmptyItem;
	
	/**
	 * @var Ext_Thebing_School
	 */
	protected $oSchool;

	public function __construct($sSchoolIdField=null, $bAddEmptyItem=true) {
		$this->iSchoolIdField = $sSchoolIdField;
		$this->bAddEmptyItem = $bAddEmptyItem;
	}

	protected function getSchool($oWDBasic) {
		global $user_data;

		if(empty($this->iSchoolIdField)) {
			$iClientId	= (int)$user_data['client'];
			$iSchoolId	= (int)\Core\Handler\SessionHandler::getInstance()->get('sid');
		} else {
			$iSchoolId = $oWDBasic->{$this->iSchoolIdField};
		}
		
		$this->oSchool = Ext_Thebing_School::getInstance($iSchoolId);
		
	}
	 
	public function getOptions($aSelectedIds, $aSaveField, &$oWDBasic) {
	
		$this->getSchool($oWDBasic);
		
		$aReturn = $this->getSchoolOptions($aSelectedIds, $aSaveField, $oWDBasic);

		if($this->bAddEmptyItem === true) {
			$aReturn = Ext_Thebing_Util::addEmptyItem($aReturn);
		}

		return $aReturn;
	}
	
}
