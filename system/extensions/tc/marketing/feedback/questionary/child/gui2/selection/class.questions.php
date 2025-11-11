<?php

class Ext_TC_Marketing_Feedback_Questionary_Child_Gui2_Selection_Questions extends Ext_Gui2_View_Selection_Abstract {

	/**
	 * @param array $aSelectedIds
	 * @param array $aSaveField
	 * @param WDBasic $oWDBasic
	 * @return array
	 */
	public function getOptions($aSelectedIds, $aSaveField, &$oWDBasic)
    {
		$oObject = $oWDBasic->getObject();
		
		// Fragen des Themas holen
		$aReturn = array();
		if($oObject->topic_id > 0) {
			$oTopic = Ext_TC_Marketing_Feedback_Topic::getInstance($oObject->topic_id);
			$aQuestions = $oTopic->getAllocatedQuestions();
			foreach($aQuestions as $oQuestion) {
				$aReturn[$oQuestion->id] = $oQuestion->getQuestion();
			}
		}

		return $aReturn;
	}
	
}
