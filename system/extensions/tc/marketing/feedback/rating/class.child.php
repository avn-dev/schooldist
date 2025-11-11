<?php

/**
 * @property $rating_id
 * @property $description
 * @property $rating
 */
class Ext_TC_Marketing_Feedback_Rating_Child extends Ext_TC_Basic {

	// Tabellenname
	protected $_sTable = 'tc_feedback_ratings_childs';
	
	protected $_sTableAlias = 'tc_frc';
	
	protected $_aFormat = array(
		'rating' => array(
			'validate'	=> 'INT_NOTNEGATIVE'
		)
	);
	
	protected $_aJoinTables = array(
		'childs_tc_i18n' => array( 
			'table' => 'tc_feedback_ratings_childs_i18n',
	 		'foreign_key_field' => array('language_iso', 'description'),
	 		'primary_key_field' => 'child_id',
			'i18n' => true
		)		
	);
	
	/**
	 * Liefert den Namen des Child-Elementes
	 *
	 * @param string $sLanguage
	 * @return string
	 */
	public function getName($sLanguage = '') {
		$sName = $this->getI18NName('childs_tc_i18n', 'description', $sLanguage);
		return $sName;
	}
	
}
