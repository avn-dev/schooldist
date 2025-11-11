<?php

/**
 * @property int $id
 * @property string $changed (TIMESTAMP)
 * @property string $created (TIMESTAMP)
 * @property string $valid_until (DATE)
 * @property int $active
 * @property int $editor_id
 * @property int $creator_id
 * @property int $topic_id
 * @property string $question_type
 * @property string $dependency_on
 * @property int $overall_satisfaction
 * @property int $quantity_stars
 * @property int $rating_id
 * @property string $accommodation_provider TS
 */
class Ext_TC_Marketing_Feedback_Question extends Ext_TC_Basic {

	/**
	 * @var string
	 */
	protected $_sTable = 'tc_feedback_questions';

	/**
	 * @var string
	 */
	protected $_sTableAlias = 'tc_fq';

	/**
	 * @var array
	 */
	protected $_aJoinTables = array(
		'questions_tc_i18n' => array( 
			'table' => 'tc_feedback_questions_i18n',
	 		'foreign_key_field' => array('language_iso', 'question'),
	 		'primary_key_field' => 'question_id'
		),
		'dependency_objects' => array(
			'table' => 'tc_feedback_questions_to_dependency_objects',
	 		'foreign_key_field' => 'object_id',
	 		'primary_key_field' => 'question_id',
			'autoload' => false
		),
		'dependency_subobjects' => array(
			'table' => 'tc_feedback_questions_to_dependency_subobjects',
	 		'foreign_key_field' => 'subobject_id',
	 		'primary_key_field' => 'question_id',
			'autoload' => false
		)
	);

	/**
	 * @inheritdoc
	 */
	public function validate($bThrowExceptions = false) {

		$mValidate = parent::validate($bThrowExceptions);

		if($mValidate === true) {
			if(
				$this->dependency_on !== $this->_aOriginalData['dependency_on'] &&
				$this->checkProcessUsage()
			) {
				// Abhängigkeit darf niemals verändert werden wenn Frage bereits irgendwo beantwortet wurde
				$mValidate = ['dependency_on' => 'QUESTION_DEPENDENCY_ALREADY_USED'];
			}
		}

		return $mValidate;

	}

	/**
	 * Prüfen, ob die Frage in irgendeinem Feedback-Prozess verwendet wurde
	 *
	 * @return bool
	 */
	private function checkProcessUsage() {

		$sSql = "
			SELECT
				COUNT(`tc_fqp`.`id`)
			FROM
				`tc_feedback_questions` `tc_fq` INNER JOIN
				`tc_feedback_questionaries_childs_questions_groups_questions` `tc_fqcqgq` ON
					`tc_fqcqgq`.`question_id` = `tc_fq`.`id` INNER JOIN
				`tc_feedback_questionaries_processes_results` `tc_fqpr` ON
					`tc_fqpr`.`questionary_question_group_question_id` = `tc_fqcqgq`.`id` AND
					`tc_fqpr`.`active` = 1 INNER JOIN
				`tc_feedback_questionaries_processes` `tc_fqp` ON
					`tc_fqp`.`id` = `tc_fqpr`.`questionary_process_id` AND
					`tc_fqp`.`active` = 1
			WHERE
				`tc_fq`.`id` = :id
		";

		$iCount = DB::getQueryOne($sSql, $this->_aData);

		return !empty($iCount);

	}

	/**
     * @param string $sLanguage
     * @return string
     */
    public function getQuestion($sLanguage = '') {
		$sQuestion = $this->getI18NName('questions_tc_i18n', 'question', $sLanguage);
		return $sQuestion;
	}

	/**
	 * @return Ext_TC_Marketing_Feedback_Rating
	 */
	public function getRating() {
		$oRating = Ext_TC_Marketing_Feedback_Rating::getInstance($this->rating_id);
		return $oRating;
	}

	/**
	 * Liefert alle Fragetypen
	 *
     * @param string $sLanguage
     * @param bool $bEmptyItem
     * @return array
     */
    public static function getQuestionTypes($sLanguage = '', $bEmptyItem = false) {
		
		$aReturn = array(
			'yes_no' => Ext_TC_L10N::t('Ja/Nein', $sLanguage),
			'rating' => Ext_TC_L10N::t('Skala', $sLanguage),
			'stars'	=> Ext_TC_L10N::t('Sterne', $sLanguage),
			'textfield'	=> Ext_TC_L10N::t('Textfeld', $sLanguage)
		);
		
		if($bEmptyItem) {
			$aReturn = Ext_TC_Util::addEmptyItem($aReturn);
		}
		
		return $aReturn;
	}

    /**
     * Liefert alle Abhänigkeiten
     *
     * @param string $sLanguage
     * @return array
     */
    public static function getDependencies($sLanguage = '') {
		
		$aReturn = array(
			'course_category' => Ext_TC_L10N::t('Kurskategorie', $sLanguage),
			'course' => Ext_TC_L10N::t('Kurs', $sLanguage),
			'accommodation_category' => Ext_TC_L10N::t('Unterkunftskategorie', $sLanguage),
			'meal' => Ext_TC_L10N::t('Verpflegung', $sLanguage),
			'transfer' => Ext_TC_L10N::t('Transfer gebucht', $sLanguage)
		);		
		
		return $aReturn;
	}

    /**
     * Liefert eine Auswahl von Fragen für ein Select
     *
     * @param string $sLanguage
     * @param bool $bAddEmptyItem
     * @return array
     */
    public static function getSelectOptions($sLanguage = '', $bAddEmptyItem = false) {

		$oTemp = new self();
		$aList = (array)$oTemp->getObjectList();
		
		$aReturn = array();
		/** @var $oQuestion Ext_TC_Marketing_Feedback_Question */
		foreach($aList as $oQuestion) {
			$aReturn[$oQuestion->id] = $oQuestion->getQuestion($sLanguage);
		}
		
		if($bAddEmptyItem) {
			$aReturn = Ext_TC_Util::addEmptyItem($aReturn);
		}
		
		return $aReturn;
	}

}