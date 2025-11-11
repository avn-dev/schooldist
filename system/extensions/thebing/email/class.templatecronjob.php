<?php

class Ext_Thebing_Email_TemplateCronjob extends Ext_TC_Communication_AutomaticTemplate {

	protected $_aJoinedObjects = array(
		'template' => array(
			'class' => 'Ext_Thebing_Email_Template',
			'key' => 'layout_id',
			'type' => 'parent',
			'check_active' => true,
			'query' => true
	 	)
	);

	/**
	 * @return Ext_Thebing_Email_Template
	 */
	public function getTemplate() {
		return Ext_Thebing_Email_Template::getInstance($this->layout_id);
	}

	/**
	 * @inheritdoc
	 */
	public static function getTypesWithExecutionTime() {
		return [
			'booking_mail',
			'enquiry_mail',
			'birthday_mail'
		];
	}

	/**
	 * @inheritdoc
	 */
	public static function getTypesWithCondition() {
		return [
			'booking_mail' => ['created', 'service_start', 'service_end', 'course_start', 'course_end', 'reminder_date'],
			'enquiry_mail' => ['created', 'follow_up_date']
		];
	}

	/**
	 * @return array
	 */
	public static function getSelectOptionTemplates() {
		#$aSchoolIds = array_keys(Ext_Thebing_Client::getFirstClient()->getSchools(true));
		// Alle Vorlagen auflisten. Automatische E-Mail wird dann nur für die in der E-Mail-Vorlage verfügbaren Schulen versendet.
		/*$aSchoolIds = [];
		$oTemplate = new Ext_Thebing_Email_Template(0);
		$aTemplates = $oTemplate->getList($aSchoolIds, ['cronjob', 'student_login'], null);*/

		$aTemplates = Ext_TC_Communication_Template::getSelectOptions('email', [
			'application' => ['cronjob', 'student_login']
		]);

		return $aTemplates;
	}

	/**
	 * To-Feld von createMailDataArray() mit Einstellungen (Empfänger) dieses Objekts überschreiben
	 *
	 * @see \Ext_Thebing_Mail::createMailDataArray()
	 * @param array|null $aMailData
	 * @param Ext_Thebing_School $oSchool
	 * @param array $aExcludedRecipients
	 * @return array|null
	 */
	public function modifyMailDataArray(array $aMailData = null, Ext_Thebing_School $oSchool, array $aExcludedRecipients = []) {

		if($aMailData === null) {
			return null;
		}

		$aRecipients = $this->recipients;
		if (!empty($aExcludedRecipients)) {
			// Möglichkeit, bestimmte Empfänger trotz Einstellung auszuschließen
			$aRecipients = array_diff($aRecipients, $aExcludedRecipients);
		}

		$aTo = [];

		// Kunde als Empfänger hinzufügen (stand vorher schon drin)
		if(
			!empty($aMailData['to']) &&
			in_array('customer', $aRecipients)
		) {
			$aTo[] = $aMailData['to'];
		}

		// Schule als Empfänger hinzufügen
		if(
			in_array('subobject', $aRecipients) &&
			!empty($oSchool->email)
		) {
			$aTo[] = $oSchool->email;
		}

		// Individueller Empfänger
		if(
			in_array('individual', $aRecipients) &&
			!empty($this->to)
		) {
			$aTo[] = $this->to;
		}

		// Es gibt keinen Empfänger, also Abbruch
		if(empty($aTo)) {
			return null;
		}

		$aMailData['to'] = $aTo;

		$aMailData['template_cronjob_id'] = $this->id;

		return $aMailData;
	}

}
