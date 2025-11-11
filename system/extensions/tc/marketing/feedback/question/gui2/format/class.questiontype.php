<?php

class Ext_TC_Marketing_Feedback_Question_Gui2_Format_QuestionType extends Ext_Gui2_View_Format_Abstract {

	/**
	 * @param $mValue
	 * @param null $oColumn
	 * @param null $aResultData
	 * @return string
	 */
	public function format($mValue, &$oColumn = null, &$aResultData = null) {
		
		$sReturn = '';
		$aQuestionTypes = (array) Ext_TC_Factory::executeStatic('Ext_TC_Marketing_Feedback_Question', 'getQuestionTypes');
		
		if(isset($aQuestionTypes[$mValue])) {
			$sReturn = $aQuestionTypes[$mValue];
		}
		
		return $sReturn;
	}
	
}
