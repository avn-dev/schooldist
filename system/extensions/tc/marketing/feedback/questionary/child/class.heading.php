<?php

/**
 * @property string $id
 * @property string $editor_id
 * @property string $creator_id
 * @property string $child_id
 * @property string $type
 */
class Ext_TC_Marketing_Feedback_Questionary_Child_Heading extends Ext_TC_Basic {

    /**
     * @var string
     */
    protected $_sTable = 'tc_feedback_questionaries_childs_headings';

    /**
     * @var string
     */
    protected $_sTableAlias = 'tc_fqnch';

    /**
     * @var array
     */
    protected $_aJoinTables = array(
		'headings_tc_i18n' => array( 
			'table' => 'tc_feedback_questionaries_childs_headings_i18n',
	 		'foreign_key_field' => array('language_iso', 'heading'),
	 		'primary_key_field' => 'topic_id',
			'i18n' => true
		)
	);

	/**
	 * Liefert den Namen der Überschrift
	 *
	 * @param string $sLanguage
	 * @param bool $bSetLabel
	 * @return string
	 */
	public function getName($sLanguage = '', $bSetLabel = true) {

		$aHeadings = Ext_TC_Util::getHeadingTypes();

		$sReturn = $this->getI18NName('headings_tc_i18n', 'heading', $sLanguage);
		if($bSetLabel) {
			$sReturn = Ext_TC_L10N::t('Überschrift', $sLanguage).' '. $aHeadings[$this->type] . ' : ' . $sReturn;
		}

		return $sReturn;
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