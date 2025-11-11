<?php

/**
 * @param string $id
 * @param string $editor_id
 * @param string $creator_id
 * @param string $child_id
 * @param string $topic_id
 * @param string $required_questions
 */
class Ext_TC_Marketing_Feedback_Questionary_Child_Question_Group extends Ext_TC_Basic {

    /**
     * @var string
     */
    protected $_sTable = 'tc_feedback_questionaries_childs_questions_groups';

    /**
     * @var string
     */
    protected $_sTableAlias = 'tc_fqcqg';

    /**
     * @var array
     */
    protected $_aFormat = array(
		'topic_id' => array(
			'format' => 'DATE'
		)
	);

    /**
     * @var array
     */
    protected $_aJoinedObjects = array(
		'topic' => array(
			'class' => 'Ext_TC_Marketing_Feedback_Topic',
			'key' => 'topic_id',
			'type' => 'parent',
			'check_active' => true
		),
		'group_questions' => array(
			'class' => 'Ext_TC_Marketing_Feedback_Questionary_Child_Question_Group_Question',
			'key' => 'questionary_question_group_id',
			'type' => 'child',
			'check_active' => true,
            'on_delete' => 'cascade',
			'orderby' => 'position'
		)
	);

    /**
     * @param string $sLanguage
     * @return mixed|string
     */
    public function getName($sLanguage = '') {

		$oTopic = $this->getTopic();
		$sReturn = Ext_TC_L10N::t('Frage', $sLanguage) . ' : ' . $oTopic->getName($sLanguage);

		return $sReturn;
	}
	
	/**
	 * Liefert das ausgewählte Thema der Frage
     *
	 * @return Ext_TC_Marketing_Feedback_Topic
	 */
	public function getTopic() {
		$oTopic = $this->getJoinedObject('topic');
		return $oTopic;
	}

	/**
	 * Liefert die zugeordneten Fragen
	 *
	 * @return Ext_TC_Marketing_Feedback_Questionary_Child_Question_Group_Question[]
	 */
	public function getGroupQuestions() {
		$aRatings = $this->getJoinedObjectChilds('group_questions', true);
		return $aRatings;
	}

	/**
	 * Liefert ein GroupQuestion zu einer Question-Id
	 *
	 * @param $iQuestionId
	 * @return Ext_TC_Marketing_Feedback_Questionary_Child_Question_Group_Question
	 */
	public function getGroupQuestionByQuestionId($iQuestionId) {
		$oRating = $this->getJoinedObjectChildByValue('group_questions', 'question_id', $iQuestionId);
		return $oRating;
	}

	/**
	 * Generiert ein neues GroupQuestion Objekt
	 *
	 * @return Ext_TC_Marketing_Feedback_Questionary_Child_Question_Group_Question
	 */
	public function generateGroupQuestion() {
		$oRating = $this->getJoinedObjectChild('group_questions', 0);
		return $oRating;
	}

	/**
	 * Löscht ein GroupQuestion anhand der GroupQuestion-Id
	 *
	 * @param $iGroupQuestion
	 */
	public function removeGroupQuestionById($iGroupQuestion) {
		$this->removeJoinedObjectChildByKey('group_questions', $iGroupQuestion);
	}

	/**
	 * Liefert das Questionary Child Objekt zurück
	 *
	 * @return Ext_TC_Marketing_Feedback_Questionary_Child
	 */
	public function getChild() {
		$oChild = Ext_TC_Marketing_Feedback_Questionary_Child::getInstance($this->child_id);
		return $oChild;
	}

}

	
	
	
