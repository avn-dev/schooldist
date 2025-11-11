<?php

class Ext_TC_Marketing_Feedback_Questionary_Child_Gui2_Format_Type extends Ext_Gui2_View_Format_Abstract {

	/**
	 * @param $mValue
	 * @param null $oColumn
	 * @param null $aResultData
	 * @return string
	 */
	public function format($mValue, &$oColumn = null, &$aResultData = null) {

		$oQuestionaryChild = Ext_TC_Marketing_Feedback_Questionary_Child::getInstance((int)$aResultData['id']);

		$aParents = $oQuestionaryChild->getParents();
		$sSpaces = '';
		for($iCnt = 0; $iCnt < count($aParents) * 4; $iCnt++) {
			$sSpaces .= '&nbsp';
		}

		$oObject = $oQuestionaryChild->getObject($mValue);
		$sReturn = '';
		if(is_object($oObject)) {
			$sReturn = $sSpaces . $oObject->getName();
		}
		
		return $sReturn;
	}

	/**
	 * @param null $oColumn
	 * @param null $aResultData
	 * @return array|bool
	 */
	public function getTitle(&$oColumn = null, &$aResultData = null) {

		$aReturn = array();

		$iChild = (int)$aResultData['id'];
		$oChild = Ext_TC_Marketing_Feedback_Questionary_Child::getInstance($iChild);
		
		if($oChild->type == 'question') {

			$oQuestionGroup = $oChild->getQuestionGroup();
			$aGroupQuestions = $oQuestionGroup->getGroupQuestions();

			if(!empty($aGroupQuestions)) {
				$sQuestions = '<ul>';
				foreach($aGroupQuestions as $oGroupQuestion) {
					$oQuestion = $oGroupQuestion->getQuestion();
					$sQuestions .= '<li>'.$oQuestion->getQuestion().'</li>';
				}
				$sQuestions .= '</ul>';
				$aReturn['content'] = (string)$sQuestions;
				$aReturn['tooltip'] = true;
			}

		}
		
		return $aReturn;
	}
	
}