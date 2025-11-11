<?php

namespace Poll\Entity\Question;

use Poll\Entity\Question;

class Condition extends \WDBasic {

	protected $_sTable = 'poll_questions_conditions';
	protected $_sTableAlias = 'pqc';

	protected $_aFormat = array(
		'question_id' => array('required' => true)
	);

	/**
	 * Gibt den Namen der entsprechenden Frage zurück
	 * @return string
	 */	
	public function getItemName() {

		$oQuestion = Question::getInstance($this->question_id);

		$oPoll = $oQuestion->getJoinedObject('poll');
		
		$aLanguages = $oPoll->getLanguages();
		$sLanguage = reset($aLanguages);
		
		$sField = $sLanguage.'_title';
		
		return $oQuestion->$sField;

	}
	
}