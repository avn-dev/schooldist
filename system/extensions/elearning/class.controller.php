<?php

class Ext_Elearning_Controller extends MVC_Abstract_Controller {

	/**
	 * Default Zugriffsrecht 
	 */
	protected $_sAccessRight = null;
	protected $_sInterface = 'frontend';
	
	public function checkAnswer() {
		
		$answer = new Ext_Elearning_Exam_Answer($this->_oRequest->get('answer_id'));
		
		$return = false;
		
		if(
			(
				$this->_oRequest->get('type') === 'correct' &&
				$answer->correct == 1	
			) ||
			(
				$this->_oRequest->get('type') !== 'correct' &&
				$answer->correct != 1
			)
		) {
			$return = true;
		}
		
		$this->set('answer_id', $answer->id);
		$this->set('return', $return);
		 
	}
		
}