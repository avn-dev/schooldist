<?php

use \Core\Helper\DateTime;

class Ext_Thebing_Examination_Templates extends Ext_Thebing_Basic {

	protected $_sTable = 'kolumbus_examination_templates';

	protected $_sTableAlias = 'kext';

	protected $_aJoinTables = array(
		'sections' => array(
			'table' => 'ts_examination_templates_sectioncategories',
			'foreign_key_field' => 'examination_sectioncategory_id',
			'primary_key_field' => 'examination_template_id',
			'sort_column' => 'sort_order',
		),
		'courses' => array(
			'table' => 'kolumbus_examination_templates_courses',
			'foreign_key_field' => 'course_id',
			'primary_key_field' => 'examination_template_id'
		)
	);

	protected $_aJoinedObjects = [
		/*'terms' => [
			'class' => 'Ext_Thebing_Examination_Templates_Terms',
			'key' => 'template_id',
			'type' => 'child'
		],*/
		'terms_fix' => [
			'class' => 'Ext_Thebing_Examination_Templates_Terms',
			'key' => 'template_id',
			'type' => 'child',
			'on_delete' => 'cascade',
			'static_key_fields' => [
				'type' => 'fix'
			],
		],
		'terms_individual' => [
			'class' => 'Ext_Thebing_Examination_Templates_Terms',
			'key' => 'template_id',
			'type' => 'child',
			'on_delete' => 'cascade',
			'static_key_fields' => [
				'type' => 'individual'
			]
		],

	];

	public function manipulateSqlParts(&$aSqlParts, $sView=null) {
		parent::manipulateSqlParts($aSqlParts, $sView);

		$aSqlParts['select'] .= "
			,GROUP_CONCAT(DISTINCT `ktc`.`name_short`) `course_list`
			,`kpt`.`name` `template_name`
		";

		$aSqlParts['from'] .= " LEFT JOIN
			`kolumbus_pdf_templates` `kpt` ON
				`kpt`.`id` = `kext`.`pdf_template_id` AND
				`kpt`.`active` = 1 LEFT JOIN
			`kolumbus_tuition_courses` `ktc` ON
				`ktc`.`id` = `courses`.`course_id` AND
				`ktc`.`active` = 1
		";
	}

	/**
	 * @return Ext_Thebing_Examination_Templates_Terms[]
	 */
	public function getTerms() {
		return array_merge($this->getJoinedObjectChilds('terms_fix'), $this->getJoinedObjectChilds('terms_individual'));
	}

	/**
	 * Prüfungstermine dieser Vorlage ermitteln, innerhalb des Kursleistungszeitraums
	 *
	 * @see Ext_Thebing_Examination_Templates_Terms::getExaminationDates()
	 * @param \Core\Helper\DateTime $dFrom
	 * @param \Core\Helper\DateTime $dUntil
	 * @return \Core\Helper\DateTime[]
	 */
	public function getExaminationDates(DateTime $dFrom, DateTime $dUntil) {

		$aDates = [];
		$aTerms = $this->getTerms();

		foreach($aTerms as $oTerm) {
			$aTermDates = $oTerm->getExaminationDates($dFrom, $dUntil);
			foreach($aTermDates as $dTermDate) {
				$aDates[] = $dTermDate;
			}
		}

		return $aDates;

	}

}
