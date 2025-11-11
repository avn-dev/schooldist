<?php

/**
 * @property string $name
 */
class Ext_TC_Marketing_Feedback_Questionary extends Ext_TC_Basic {

	// Tabellenname
	protected $_sTable = 'tc_feedback_questionaries';
	
	protected $_sTableAlias = 'tc_fqn';
		
	protected $_aJoinTables = array(
		'objects' => array(
			'table' => 'tc_feedback_questionaries_to_objects',
			'foreign_key_field' => 'object_id',
			'primary_key_field' => 'questionary_id'
		),
		'subobjects' => array(
			'table' => 'tc_feedback_questionaries_to_subobjects',
			'foreign_key_field' => 'subobject_id',
			'primary_key_field' => 'questionary_id'
		)
	);
	
	protected $_aJoinedObjects = array(
		'childs' => array(
			'class' => 'Ext_TC_Marketing_Feedback_Questionary_Child',
			'type' => 'child',
			'key' => 'questionnaire_id',
			'check_active' => true,
			'orderby' => 'position',
			'on_delete' => 'cascade'
		)
	);
	
    /**
     * @param bool $bPlural
     * @return string
     */
	public static function getSubObjectLabel(bool $bPlural=true) {
		
		if($bPlural === true) {
			$sLabel = L10N::t('Unterobjekte');
		} else {
			$sLabel = L10N::t('Unterobjekt');
		}
		
		return $sLabel;
	}

    /**
	 * Gibt alle Childs eines Fragebogens zurück
	 *
     * @return Ext_TC_Marketing_Feedback_Questionary_Child[]
     */
    public function getChilds() {
		$aQuestions = $this->getJoinedObjectChilds('childs', true);
		return $aQuestions;
	}

    /**
     * Gibt alle Fragen eines Fragebogens zurück inklusive
	 * des Rating
     *
     * array['question'] Ext_TC_Marketing_Feedback_Question
     * array['rating'] Ext_TC_Marketing_Feedback_Rating
	 * array['required_questions'] boolean Sagt aus ob Frage eine Pflichtfrage ist
     *
     * @return array (Siehe oben) 
     */
    public function getQuestionsRatings() {

        $aQuestions = array();

        $aQuestionaryChilds = $this->getChilds();
        foreach($aQuestionaryChilds as $oQuestionaryChild) {
			if($oQuestionaryChild->type !== 'heading') {
				$oQuestionGroup = $oQuestionaryChild->getQuestionGroup();
				$aQuestionGroupQuestions = $oQuestionGroup->getGroupQuestions();
				foreach($aQuestionGroupQuestions as $oQuestionGroupQuestion) {
					$oQuestion = $oQuestionGroupQuestion->getQuestion();
					$aQuestions[] = array(
						'question' => $oQuestion,
						'rating' => $oQuestion->getRating(),
						'child' => $oQuestionaryChild,
						'required_questions' => $oQuestionGroup->required_questions,
					);
				}
			}
        }

        return $aQuestions;
    }

	/**
	 * Überprüft ob die Subobjects die Journey beinhaltet
	 *
	 * @param $iJourneyId
	 * @return bool
	 */
	public function checkSubObjectsByJourneyId($iJourneyId) {
		return false;
	}

	/**
	 * Gibt Select-Options aller Questionary Childs zurück
	 *
	 * @return array
	 */
	public function getChildsSelectOptions() {

		$aQuestionChilds = $this->getChilds();
		$aChildsSelectOptions = array();
		foreach($aQuestionChilds as $oQuestionChild) {
			$aChildsSelectOptions[$oQuestionChild->id] = $oQuestionChild->getObject($oQuestionChild->type)->getName();
		}

		return $aChildsSelectOptions;
	}
		
	/**
	 * 
	 * @param null|string $sForeignIdField
	 * @param null|int $iForeignId
	 * @param array $aOptions
	 * @return Ext_TC_Marketing_Feedback_Questionary
	 */
	public function createCopy($sForeignIdField = null, $iForeignId = null, $aOptions = array()) {
		
		$oClone = parent::createCopy($sForeignIdField, $iForeignId, $aOptions);
		
		$aOldQuestionaryChilds = $this->getChilds();
		$aNewQuestionaryChilds = $oClone->getChilds();

		$aIds = array();
		$aRelationIds = array();
		foreach($aOldQuestionaryChilds as $oOldQuestionaryChild) {
			$aIds[$oOldQuestionaryChild->position] = $oOldQuestionaryChild->id; 
		}
		foreach($aNewQuestionaryChilds as $oNewQuestionaryChild) {			
			$aRelationIds[$aIds[$oNewQuestionaryChild->position]] = $oNewQuestionaryChild->id;		
		}		
		foreach($aNewQuestionaryChilds as $oNewQuestionaryChild) {
			if($oNewQuestionaryChild->parent_id == 0) {
				continue;
			}
			$iParentId = $oNewQuestionaryChild->parent_id;
			$oNewQuestionaryChild->parent_id = $aRelationIds[$iParentId];
			$oNewQuestionaryChild->save();
		}
		
		return $oClone;
	}
	
}
