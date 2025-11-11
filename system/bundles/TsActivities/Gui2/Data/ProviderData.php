<?php

namespace TsActivities\Gui2\Data;

class ProviderData extends \Ext_Thebing_Gui2_Data {

	/**
	 * @param \Ext_Gui2 $oGui
	 * @return \Ext_Gui2_Dialog
	 * @throws \Exception
	 */
	public static function getDialog(\Ext_Gui2 $oGui) {

		$aCountries = \Ext_Thebing_Data::getCountryList(true, true);

		// Dialog
		$oDialog = $oGui->createDialog($oGui->t('Anbieter "{contact.lastname}, {contact.firstname}" editieren'), $oGui->t('Neuen Anbieter anlegen'));

		// Tab Einstellungen
		$oTab = $oDialog->createTab($oGui->t('Einstellungen'));

		$oTab->aOptions	= [
			'section' => 'requirements'
		];

		$oTab->setElement($oDialog->createRow($oGui->t('Firma'), 'input', [
			'db_alias' => 'contact',
			'db_column' => 'address_company',
			'required'	=> true,
		]));

		$oTab->setElement($oDialog->createRow($oGui->t('Nachname'), 'input', [
			'db_alias' => 'contact',
			'db_column' => 'lastname',
			'required'	=> true,
		]));

		$oTab->setElement($oDialog->createRow($oGui->t('Vorname'), 'input', [
			'db_alias' => 'contact',
			'db_column' => 'firstname',
			'required'	=> true,
		]));

		$oH3 = $oDialog->create('h4')->setElement($oGui->t('Adresse'));
		$oTab->setElement($oH3);

		$oTab->setElement($oDialog->createRow($oGui->t('Adresse'), 'input', [
			'db_alias' => 'contact',
			'db_column' => 'address_address',
			'required'	=> true,
		]));

		$oTab->setElement($oDialog->createRow($oGui->t('Adresszusatz'), 'input', [
			'db_alias' => 'contact',
			'db_column' => 'address_address_additional'
		]));

		$oTab->setElement($oDialog->createRow($oGui->t('PLZ'), 'input', [
			'db_alias' => 'contact',
			'db_column' => 'address_zip'
		]));

		$oTab->setElement($oDialog->createRow($oGui->t('Stadt'), 'input', [
			'db_alias' => 'contact',
			'db_column' => 'address_city'
		]));

		$oTab->setElement($oDialog->createRow($oGui->t('Land'), 'select', [
			'db_alias' => 'contact',
			'db_column' => 'address_country_iso',
			'select_options' => $aCountries
		]));

		$oH3 = $oDialog->create('h4')->setElement($oGui->t('Kontaktdaten'));
		$oTab->setElement($oH3);

		$oTab->setElement($oDialog->createRow($oGui->t('Telefon Büro'), 'input', [
			'db_alias' => 'contact',
			'db_column' => 'detail_phone_office'
		]));

		$oTab->setElement($oDialog->createRow($oGui->t('Telefon Privat'), 'input', [
			'db_alias' => 'contact',
			'db_column' => 'detail_phone_private'
		]));

		$oTab->setElement($oDialog->createRow($oGui->t('Skype'), 'input', [
			'db_alias' => 'contact',
			'db_column' => 'detail_skype'
		]));

		$oTab->setElement($oDialog->createRow($oGui->t('E-Mail'), 'input', [
			'db_alias' => 'contact',
			'db_column' => 'email',
			'required'	=> true,
		]));

		$oH3 = $oDialog->create('h4')->setElement($oGui->t('Sonstiges'));
		$oTab->setElement($oH3);

		$oTab->setElement($oDialog->createRow($oGui->t('Internetseite'), 'input', [
			'db_alias' => 'contact',
			'db_column' => 'detail_website'
		]));

		$oTab->setElement($oDialog->createRow($oGui->t('Kommentar'), 'textarea', [
			'db_alias' => 'contact',
			'db_column' => 'detail_comment'
		]));

		$oDialog->setElement($oTab);

		return $oDialog;

	}
}