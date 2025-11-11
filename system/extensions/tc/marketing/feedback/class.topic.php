<?php

class Ext_TC_Marketing_Feedback_Topic extends Ext_TC_Basic {

	// Tabellenname
	protected $_sTable = 'tc_feedback_topics';

	protected $_sTableAlias = 'tc_ft';
		
	protected $_aJoinTables = array(
		'topics_tc_i18n' => array( 
			'table' => 'tc_feedback_topics_i18n',
	 		'foreign_key_field' => array('language_iso', 'name'),
	 		'primary_key_field' => 'topic_id'
		)		
	);
	
	protected $_aJoinedObjects = array(
		'questions' => array(
			'class' => 'Ext_TC_Marketing_Feedback_Question',
			'type' => 'child',
			'key' => 'topic_id',
			'check_active' => true,
			'cloneable' => false
		)
	);
	
	/**
	 * Liefert den Namen des Themas
	 *
	 * @param string $sLanguage
	 * @return string
	 */
	public function getName($sLanguage = '') {
		$sName = $this->getI18NName('topics_tc_i18n', 'name', $sLanguage);
		return $sName;
	}
	
	/**
	 * Liefert alle Fragen, die diesem Thema zugeortnet wurden
	 *
	 * @return Ext_TC_Marketing_Feedback_Question[]
	 */
	public function getAllocatedQuestions() {
		$aQuestions = $this->getJoinedObjectChilds('questions', true);	
		return $aQuestions;
	}

	/**
	 * Liefert eine Auswahl von Themen für ein Select
	 *
	 * @param string $sLanguage
	 * @param bool $bAddEmptyItem
	 * @return array
	 */
	public static function getSelectOptions($sLanguage = '', $bAddEmptyItem = false) {
		$oTemp = new self();
		$aList = (array) $oTemp->getObjectList();
		
		$aReturn = array();
		foreach($aList as $oTopic) {
			$aReturn[$oTopic->id] = $oTopic->getName($sLanguage);
		}
		
		if($bAddEmptyItem) {
			$aReturn = Ext_TC_Util::addEmptyItem($aReturn);
		}
		
		return $aReturn;
	}
	
}