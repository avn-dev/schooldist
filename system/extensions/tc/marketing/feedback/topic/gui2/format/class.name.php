<?php

class Ext_TC_Marketing_Feedback_Topic_Gui2_Format_Name extends Ext_Gui2_View_Format_Abstract {

	/**
	 * @param $mValue
	 * @param null $oColumn
	 * @param null $aResultData
	 * @return string
	 */
	public function format($mValue, &$oColumn = null, &$aResultData = null) {

		$iTopic = (int)$mValue;
		$oTopic = Ext_TC_Marketing_Feedback_Topic::getInstance($iTopic);
		
		return $oTopic->getName();
	}
	
}