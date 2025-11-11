<?php

class Ext_TS_System_Checks_Hubspot_ExistingEmailAlready extends GlobalChecks {

	/**
	 * @return string
	 */
	public function getTitle() {
		return 'Sets the action for when an inquiry gets created with an already existing email in hubspot.';
	}

	/**
	 * @return string
	 */
	public function getDescription() {
		return self::getTitle();
	}

	/**
	 * Wenn ein Wert zum hinzufügen der E-Mail bereits eingegeben wurde vor dieser Ändernung, dann "new_contact" als Wert
	 * setzen, ansonsten den Default Wert "new_deal"
	 *
	 * @return boolean
	 */
	public function executeCheck()
	{

		set_time_limit(3600);
		ini_set('memory_limit', '4G');

		$hubspotAdditionalMultipleEmails = System::d('hubspot_additional_multiple_emails');
		$alreadyExistingContactAction = System::d('hubspot_already_existing_contact_action');
		if (empty($alreadyExistingContactAction)) {
			// Wenn überhaupt ein Default gesetzt werden muss.
			if (!empty($hubspotAdditionalMultipleEmails)) {
				// Bei Kunden die vor dieser Änderung bereits den String eingegeben haben den Default wie davor setzen.
				$alreadyExistingContactAction = 'new_contact';
			} else {
				// Default sonst
				$alreadyExistingContactAction = 'new_deal';
			}
		}

		System::s('hubspot_already_existing_contact_action', $alreadyExistingContactAction);

		return parent::executeCheck();
	}

}