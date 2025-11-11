<?php

/**
 * @TODO $questionary_question_group_question_id auf $question_id umstellen,
 * da Fragebogen ohnehin nur eine Frage einmal haben kann
 *
 * @property int $id
 * @property string $changed (TIMESTAMP)
 * @property string $created (TIMESTAMP)
 * @property int $active
 * @property int $questionary_question_group_question_id
 * @property int $dependency_id
 * @property string $answer
 * @property int $questionary_process_id
 */
class Ext_TC_Marketing_Feedback_Questionary_Process_Result extends Ext_TC_Basic {

	protected $_sTable = 'tc_feedback_questionaries_processes_results';

	protected $_sTableAlias = 'tc_fqpv';

	/**
	 * @param array $aQuestionChild
	 * @return string
	 */
	public function getAnswer(array $aQuestionChild) {

		switch($aQuestionChild['questionType']) {
			case 'yes_no':
				$oFormat = new Ext_TC_Gui2_Format_YesNo();
				return $oFormat->formatByValue($this->answer);
			case 'rating':
				$oRating = Ext_TC_Marketing_Feedback_Rating::getInstance($aQuestionChild['questionRatingId']);
				$oRatingChild = $oRating->getChildByRating($this->answer);
				// Da ein Rating-Feld kein Pflichtfeld sein muss, kann das hier auch null sein #9188
				if($oRatingChild !== null) {
					return $oRatingChild->getName().' ('.$oRatingChild->rating.')';
				}
				return '';
			default:
				return $this->answer;
		}

	}

}
