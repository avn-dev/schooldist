<?php

class Ext_TC_Marketing_Feedback_Questionary_Child extends Ext_TC_Basic {

	// Tabellenname
	protected $_sTable = 'tc_feedback_questionaries_childs';

	protected $_sTableAlias = 'tc_fqnc';

    protected $_aFormat = array(
        'type' => array(
            'required' => true,
            'validate' => 'REGEX',
            'validate_value' => '(heading|question)'
        )
    );

    protected $_aJoinedObjects = array(
		'question' => array(
			'class' => 'Ext_TC_Marketing_Feedback_Questionary_Child_Question_Group',
			'key' => 'child_id',
			'type' => 'child',
            'on_delete' => 'cascade'
		),
		'heading' => array(
			'class' => 'Ext_TC_Marketing_Feedback_Questionary_Child_Heading',
			'key' => 'child_id',
			'type' => 'child'
		),
		'childs' => array(
			'class' => 'Ext_TC_Marketing_Feedback_Questionary_Child',
			'key' => 'parent_id',
			'type' => 'child',
			'orderby' => 'position',
			'cloneable' => false,
			'check_active' => true
		)
	);

    /**
     * Liefert das jeweilige Objekt (Frage/Überschrift)
     * Falls nix gefunden wird, wird ein leeres Objekt zurückgegeben.
     *
     * @param string $sJoinedObjectKey
     * @return mixed|null|WDBasic
     */
    public function getObject($sJoinedObjectKey = '') {

		$oObject = null;
		
		if($sJoinedObjectKey == '') {
			$sJoinedObjectKey = $this->type;
		}

		if(!empty($sJoinedObjectKey)) {
			$aObjects = $this->getJoinedObjectChilds($sJoinedObjectKey, true);
			if(empty($aObjects)) {
				$oObject = $this->getJoinedObjectChild($sJoinedObjectKey);
			} else {
				$oObject = reset($aObjects);
			}
		}

		return $oObject;
	}

    /**
     * Liefert die jeweiligen Fragen
     *
     * @return Ext_TC_Marketing_Feedback_Questionary_Child_Question_Group
     */
    public function getQuestionGroup() {
        $oQuestionGroup = $this->getObject('question');
        return $oQuestionGroup;
    }

    /**
     * Liefert die jeweiligen Überschriften
     *
     * @return Ext_TC_Marketing_Feedback_Questionary_Child_Heading
     */
    public function getHeading() {
        $oHeading = $this->getObject('heading');
        return $oHeading;
    }

	/**
	 * Gibt den direkten Parent zurück
	 *
	 * @return Ext_TC_Marketing_Feedback_Questionary_Child
	 */
	public function getParent() {
		$oChild = Ext_TC_Marketing_Feedback_Questionary_Child::getRepository()->findOneBy(array('id' => $this->parent_id));
		return $oChild;
	}

	/**
	 * Gibt alle Parents bis zur Ebene 0 zurück
	 *
	 * @return Ext_TC_Marketing_Feedback_Questionary_Child[]
	 */
	public function getParents() {

		$oChild = $this;
		$aReturn = array();

		$aStack = [];
		
		while($oChild->parent_id > 0) {
			if(isset($aStack[$oChild->parent_id])) {
				// Endlosschleife verhindern wenn Einträge falsch verknüpft sind
				throw new \RuntimeException('Prevent endless loop!');
			}
			
			$aStack[$oChild->parent_id] = $oChild->parent_id;
			
			$oChild = Ext_TC_Marketing_Feedback_Questionary_Child::getRepository()->findOneBy(array('id' => $oChild->parent_id));
			$aReturn[] = $oChild;			
		}

		return $aReturn;
	}

	/**
	 * Gibt die Childs bis zur letzten Ebene zurück
	 *
	 * @return Ext_TC_Marketing_Feedback_Questionary_Child[]
	 */
	public function getChilds() {

		$aReturn = array();

		$aChilds = $this->getJoinedObjectChilds('childs', true);
		/** @var $oChild Ext_TC_Marketing_Feedback_Questionary_Child */
		foreach($aChilds as $oChild) {
			$aReturn[] = $oChild;
			$aChildChilds = $oChild->getChilds();
			foreach($aChildChilds as $oChildChild) {
				$aReturn[] = $oChildChild;
			}
		}

		return $aReturn;
	}

	/**
	 * Löscht das Objekt inklusive
	 * allen Childs rekursive
	 *
	 * @return array|bool|mixed|void
	 */
	public function delete() {

		$mRetVal = parent::delete();

		if($mRetVal === true) {
			$aChilds = Ext_TC_Marketing_Feedback_Questionary_Child::getRepository()->findBy(array('parent_id' => $this->id));
			/** @var $oChild Ext_TC_Marketing_Feedback_Questionary_Child */
			foreach($aChilds as $oChild) {
				$mRetVal = $oChild->delete();
				if($mRetVal !== true) {
					break;
				}
			}
		}

		return $mRetVal;
	}

	/**
	 * Gibt den zugewiesenen Fragebogenkatalog zurück
	 *
	 * @return Ext_TC_Marketing_Feedback_Questionary
	 */
	public function getQuestionary() {
		$oQuestionary = Ext_TC_Marketing_Feedback_Questionary::getInstance($this->questionnaire_id);
		return $oQuestionary;
	}

	/**
	 * Setzt den Cloneable Wert aus den JoinedObjects auf true
	 * damit die Childs beim Child-Duplizieren Button mit dupliziert werden
	 *
	 * @param null $sForeignIdField
	 * @param null $iForeignId
	 * @param array $aOptions
	 * @return static $oClone
	 */
	public function createCopy($sForeignIdField = null, $iForeignId = null, $aOptions = array()) {

		$bCloneable = false;
		if(isset($aOptions['cloneable'])) {
			$bCloneable = $aOptions['cloneable'];
		}
		$this->_aJoinedObjects['childs']['cloneable'] = $bCloneable;

		$oClone = parent::createCopy($sForeignIdField, $iForeignId, $aOptions);
		
		return $oClone;
		
	}
	
}

	
	
	
