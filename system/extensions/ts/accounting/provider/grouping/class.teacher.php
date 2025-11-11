<?php

use Communication\Interfaces\Model\CommunicationSubObject;

/**
 * @method Ext_Thebing_Teacher_Payment[] getPayments()
 */
class Ext_TS_Accounting_Provider_Grouping_Teacher extends Ext_TS_Accounting_Provider_Grouping_Abstract {

	protected $_sTable = 'ts_teachers_payments_groupings';

	protected $_sTableAlias = 'ts_tpg';

	protected $_aJoinedObjects = array(
		'payments' => array(
			'class' => 'Ext_Thebing_Teacher_Payment',
			'key' => 'grouping_id',
			'type' => 'child',
			'check_active' => true,
			'on_delete' => 'cascade'
		)
	);

	public function __get($sName)
	{
		Ext_Gui2_Index_Registry::set($this);
		
		// Da der Kommunikationsdialog dumm ist, lastname aus Teacher-Klasse holen
		if($sName === 'lastname') {
			$oTeacher = Ext_Thebing_Teacher::getInstance($this->teacher_id);
			return $oTeacher->lastname;
		} else {
			return parent::__get($sName);
		}
	}

	public function manipulateSqlParts(&$aSqlParts, $sView=null) {

		$aSqlParts['select'] .= "
			,
			CONCAT(`kt`.`lastname`, ', ', `kt`.`firstname`) `teacher_name`,
			`kt`.`account_holder`,
			`kt`.`account_number`,
			`kt`.`adress_of_bank`,
			`kt`.`name_of_bank`
		";
		
		$oSchool = Ext_Thebing_School::getSchoolFromSession();
		$aSqlParts['from'] .= "
			INNER JOIN `ts_teachers` `kt` ON
				`ts_tpg`.`teacher_id` = `kt`.`id` JOIN
			`ts_teachers_to_schools` `ts_ts` ON
				kt.id = `ts_ts`.`teacher_id` AND
				`ts_ts`.`school_id` = ".(int)$oSchool->id."
		";
		
	}

	public function getOldPlaceholderObject(SmartyWrapper $oSmarty=null) {
		$oTeacher = $this->getItem();
		$oPlaceholder = new Ext_TS_Accounting_Provider_Grouping_Teacher_Placeholder($oTeacher, $this);
		return $oPlaceholder;
	}

	public function getItem() {
		return Ext_Thebing_Teacher::getInstance($this->teacher_id);
	}

	public function getType() {
		return 'teacher';
	}

	public function getCommunicationDefaultApplication(): string
	{
		return \TsAccounting\Communication\Application\TeacherPayments::class;
	}

	public function getCommunicationLabel(\Tc\Service\LanguageAbstract $l10n): string
	{
		return $l10n->translate('Lehrerzahlung');
	}

	public function getCommunicationSubObject(): CommunicationSubObject
	{
		return $this->getItem()->getSchool();
	}
}
