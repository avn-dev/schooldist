<?php

class Ext_Thebing_Gui2_Format_Date_Time extends Ext_Gui2_View_Format_Date_Time {

	public function format($mValue, &$oColumn = null, &$aResultData = null) {

		if($mValue == NULL){
			return '';
		}
		
		if(empty($aResultData['school_id'])){
			$aResultData['school_id'] = \Core\Handler\SessionHandler::getInstance()->get('sid');
		}

		$this->format = Ext_Thebing_Format::getDateFormat($aResultData['school_id']).' %H:%M';

		$mValue = parent::format($mValue, $oColumn, $aResultData);

		return $mValue;

	}

}
