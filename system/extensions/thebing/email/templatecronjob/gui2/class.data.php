<?php

class Ext_Thebing_Email_TemplateCronjob_Gui2_Data extends Ext_TC_Communication_AutomaticTemplate_Gui2_Data {

	/**
	 * @inheritdoc
	 */
	public function getSelectOptionsTypes() {

		$aTypes = parent::getSelectOptionsTypes();

//		$aTypes[1] = L10N::t('Buchungsmail', 'Thebing » Admin » E-mail Cronjob'); sollte entfernt werden 16.03.11
//		$aTypes[2] = L10N::t('Werbemail', 'Thebing » Admin » E-mail Cronjob');
//		$aTypes[3] = L10N::t('Registrierungs-E-Mail', 'Thebing » Admin » E-mail Cronjob');
//		$aTypes[4] = L10N::t('Anfragenmail', 'Thebing » Admin » E-mail Cronjob');
//
//		$aTypes[6] = L10N::t('Kreditkartenzahlung - Kunde', 'Thebing » Admin » E-mail Cronjob');
//		$aTypes[7] = L10N::t('Kreditkartenzahlung - Schule', 'Thebing » Admin » E-mail Cronjob');
//
//		if(Ext_Thebing_Access::hasRight('thebing_marketing_agencies_tab_login')) {
//			$aTypes[5] = L10N::t('Agentur-Portal-E-Mail', 'Thebing » Admin » E-mail Cronjob');
//		}
//		$aTypes[8] = L10N::t('Schüler Zugangscode', 'Thebing » Admin » E-mail Cronjob');

		//$aTypes['reminder_mail'] = $this->t('Bezahlerinnerung');

		if(Ext_Thebing_Access::hasLicenceRight('thebing_admin_email_templates_automatic_cronjob')) {
			$aTypes['booking_mail'] = $this->t('Buchungs-E-Mail');
			$aTypes['enquiry_mail'] = $this->t('Anfrage-E-Mail');
			$aTypes['birthday_mail'] = $this->t('Geburtstags-E-Mail');
			//$aTypes['activity_mail'] = $this->t('Aktivitäten-E-Mail');
		}

		return $aTypes;

	}

	/**
	 * @inheritdoc
	 */
	public function getSelectOptionsEvents() {

		$aEvents = [
			'created' => $this->t('Erstellungsdatum'),
			'service_start' => $this->t('erster Leistungstag'),
			'service_end' => $this->t('letzter Leistungstag'),
			'course_start' => $this->t('erster Kurstag'),
			'course_end' => $this->t('letzter Kurstag'),
			'reminder_date' => $this->t('Fälligkeitsdatum'),
			'follow_up_date' => $this->t('Nachhaken')
		];

		return $aEvents;

	}

}
