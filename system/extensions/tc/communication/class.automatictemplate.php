<?php

class Ext_TC_Communication_AutomaticTemplate extends Ext_TC_Basic {

	protected $_sTable = 'tc_communication_automatictemplates';
	protected $_sTableAlias = 'tc_cat';

	protected $_aFormat = array(
		'name' => array(
			'required' => true
		),
		'days' => [
			'validate' => 'INT_NOTNEGATIVE'
		],
		'days_after_last_message' => [
			'validate' => 'INT_NOTNEGATIVE'
		],
		'to' => array(
			'validate' => 'MAIL'
		),
		'cc' => array(
			'validate' => 'MAIL'
		),
		'bcc' => array(
			'validate' => 'MAIL'
		)
	);

	protected $_aJoinedObjects = array(
		'template' => array(
			'class'=>'Ext_TC_Communication_Template',
			'key' => 'layout_id',
			'type' => 'parent',
			'check_active' => true,
			'query' => true
	 	)
	);

	protected $_aJoinTables = array(
		'recipients' => array(
			'table' => 'tc_communication_automatictemplates_recipients',
			'foreign_key_field' => 'recipient',
			'primary_key_field' => 'template_id'
		)
	);

	/**
	 * @return array
	 */
	public static function getTypesWithExecutionTime() {
		return [];
	}

	/**
	 * @return array
	 */
	public static function getTypesWithCondition() {
		return [];
	}

	/**
	 * Prüfen, ob die Stunde zur Ausführung passt
	 *
	 * @param int $iHour
	 * @return bool
	 */
	public function checkExecutionHour($iHour) {

		// Registrierungs-E-Mail ausschließen
		if(!in_array($this->type, static::getTypesWithExecutionTime())) {
			return false;
		}

		if($iHour == $this->execution_time) {
			return true;
		}

		return false;

	}

	/**
	 * Wrapper-Methode für unterschiedlische Implementierung auf TS
	 */
	public static function getSelectOptionTemplates() {
		$aTemplates = Ext_TC_Communication_Template::getSelectOptions('email', array('application'=>'automatic'));
		return $aTemplates;
	}

	/**
	 * Liefert alle automischen Templates anhand des angegebenen Filters
	 *
	 * @param $sType
	 * @param string $sLanguage
	 * @param mixed $mObjects Filtern nach Objekten (Büros); alle Objekte müssen übereinstimmen!
	 * @return array
	 */
	public static function search($sType = null, $sLanguage = null, $mObjects = null)
	{
		$aTemplates = array();
		$sSqlForm = '';

		// Auf die E-Mail/SMS-Templates joinen, da ID für Sprache und Objekte benötigt
		if(
			!empty($sLanguage) ||
			!empty($mObjects))
		{
			$sSqlForm .= "
				INNER JOIN
				`tc_communication_templates` `tc_ct` ON
					`tc_cat`.`layout_id` = `tc_ct`.`id`
			";
		}

		// Auf die Sprache joinen (Filter)
		if(!empty($sLanguage)) {
			$sSqlForm .= "
				INNER JOIN
				`tc_communication_templates_languages` `tc_ctl` ON
					`tc_ctl`.`template_id` = `tc_ct`.`id` AND
					`tc_ctl`.`language_iso` = :language
			";
		}

		// Auf irgendein Objekt joinen (größter gemeiner Teiler nicht gewünscht!)
		if(!empty($mObjects)) {
			$sSqlForm .= "
				INNER JOIN
				`tc_communication_templates_to_objects` `tc_ctto` ON
					`tc_ctto`.`template_id` IN ( :objects )
			";
		}

		$sSql = "
			SELECT
				`tc_cat`.*
			FROM
				`tc_communication_automatictemplates` `tc_cat` $sSqlForm
			WHERE
				`tc_cat`.`active` = 1
		";

		// Auf Typ filtern
		if(!empty($sType)) {
			$sSql .= " AND `tc_cat`.`type` = :type ";
		}

		$aSql = array(
			'type' => $sType,
			'language' => $sLanguage,
			'objects' => $mObjects
		);

		$aResult = DB::getPreparedQueryData($sSql, $aSql);

		foreach((array)$aResult as $aData) {
			unset($aData['objects']);
			$aTemplates[] = static::getObjectFromArray($aData);
		}

		return $aTemplates;
	}

	public static function getSelectOptions($bForSelect = false, $sType = null, $sLanguage = null, $mObject = null)
	{
		$aTemplates = array();
		$aTemplateObjs = static::search($sType, $sLanguage, $mObject);

		foreach((array)$aTemplateObjs as $oTemplate){
			$aTemplates[$oTemplate->id] = $oTemplate->name;
		}

		if($bForSelect) {
			$aTemplates = Ext_TC_Util::addEmptyItem($aTemplates);
		}

		return $aTemplates;
	}

	/**
	 * Liefert das zu dem automatischen Template zugehörige Template
	 *
	 * @return Ext_TC_Communication_Template
	 */
	public function getTemplate() {
		return Ext_TC_Communication_Template::getInstance($this->layout_id);
	}

	public static function getSelectOptionsRecipients()
	{
		$sSubObjectLabel = Ext_TC_Factory::executeStatic('Ext_TC_Object', 'getSubObjectLabel', array(false));
		return array(
			'subobject' => Ext_TC_Communication::t($sSubObjectLabel),
			'customer' => Ext_TC_Communication::t('Kunde'),
			'individual' => Ext_TC_Communication::t('Individuell')
		);
	}
	
	public function manipulateSqlParts(&$aSqlParts, $sView=null) {
		parent::manipulateSqlParts($aSqlParts, $sView);
		
		$aSqlParts['select'] .= "
			, GROUP_CONCAT(DISTINCT `recipients`.`recipient` ORDER BY `recipients`.`recipient` SEPARATOR ',') `recipient_ids`
		";
		
	}
	
}