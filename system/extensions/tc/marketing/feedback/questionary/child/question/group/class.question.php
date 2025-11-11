<?php

class Ext_TC_Marketing_Feedback_Questionary_Child_Question_Group_Question extends Ext_TC_Basic {

	/**
	 * @var string
	 */
	protected $_sTable = 'tc_feedback_questionaries_childs_questions_groups_questions';

	/**
	 * @var string
	 */
	protected $_sTableAlias = 'tc_fqcqgq';

	/**
	 * @return Ext_TC_Marketing_Feedback_Question
	 */
	public function getQuestion() {
		$oQuestion = Ext_TC_Marketing_Feedback_Question::getInstance($this->question_id);
		return $oQuestion;
	}

	/**
	 * Gibt die zugehörige Gruppe zurück
	 *
	 * @return Ext_TC_Marketing_Feedback_Questionary_Child_Question_Group
	 */
	public function getGroup() {
		$oGroup = Ext_TC_Marketing_Feedback_Questionary_Child_Question_Group::getInstance($this->questionary_question_group_id);
		return $oGroup;
	}

}
