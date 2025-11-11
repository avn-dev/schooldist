<?php

namespace TsSponsoring\Gui2\Data;

use Tc\Traits\Gui2\Import;
use TsSponsoring\Entity\Sponsor;

class SponsorData extends \Ext_Thebing_Gui2_Data {
	use Import;

	/**
	 * @param \Ext_Gui2 $oGui
	 * @return \Ext_Gui2_Dialog
	 */
	public static function getDialog(\Ext_Gui2 $oGui) {

		$aCountries = \Ext_Thebing_Data::getCountryList(true, true);
		$aSchools = \Ext_Thebing_Client::getSchoolList(true);
		$aSponsoringTypes = Sponsor::getSponsoringTypes();

		$aSponsoringTypes = \Ext_Gui2_Util::addLabelItem($aSponsoringTypes, $oGui->t('Sponsoring-Typ'));

		$sInterfaceLanguage	= \Ext_TC_System::getInterfaceLanguage();
		$aCorrespondenceLanguages = \Ext_Thebing_Data::getCorrespondenceLanguages(true, $sInterfaceLanguage);

		$oDialog = $oGui->createDialog($oGui->t('Sponsor "{name}" editieren'), $oGui->t('Neuen Sponsor anlegen'));

		$oTab = $oDialog->createTab($oGui->t('Daten', $oGui->gui_description));

		$oTab->setElement($oDialog->createRow($oGui->t('Name'), 'input', array(
			'db_column' => 'name',
			'db_alias' => 'ts_s',
			'required' => true,
		)));

		$oTab->setElement($oDialog->createRow($oGui->t('Abkürzung'), 'input', array(
			'db_column' => 'abbreviation',
			'db_alias' => 'ts_s',
			'required' => true,
		)));

		$oTab->setElement($oDialog->createRow($oGui->t('Schulen'),'select', array(
				'db_column' => 'schools',
				'db_alias' => '',
				'select_options' => $aSchools,
				'multiple' => 5,
				'jquery_multiple' => 1,
				'required' => true
			)
		));

		$oTab->setElement($oDialog->createRow($oGui->t('Sponsoring'), 'select', array(
			'db_column' => 'sponsoring',
			'db_alias' => 'ts_s',
			'select_options' => $aSponsoringTypes,
			'required' => true
		)));

		$oH3 = $oDialog->create('h4');
		$oH3->setElement($oGui->t('Adresse'));
		$oTab->setElement($oH3);

		$oTab->setElement($oDialog->createRow($oGui->t('Adresse'), 'input', array(
			'db_column' => 'address',
			'db_alias' => 'address'
		)));

		$oTab->setElement($oDialog->createRow($oGui->t('Adresszusatz'), 'input', array(
			'db_column' => 'address_addon',
			'db_alias' => 'address'
		)));

		$oTab->setElement($oDialog->createRow($oGui->t('PLZ'), 'input', array(
			'db_column' => 'zip',
			'db_alias' => 'address'
		)));

		$oTab->setElement($oDialog->createRow($oGui->t('Stadt'), 'input', array(
			'db_column' => 'city',
			'db_alias' => 'address'
		)));

		$oTab->setElement($oDialog->createRow($oGui->t('Bundesland'), 'input', array(
			'db_column' => 'state',
			'db_alias' => 'address'
		)));

		$oTab->setElement($oDialog->createRow($oGui->t('Land'), 'select', array(
			'db_column' => 'country_iso',
			'db_alias' => 'address',
			'select_options' => $aCountries,
		)));

		$oH3 = $oDialog->create('h4');
		$oH3->setElement($oGui->t('Sonstiges'));
		$oTab->setElement($oH3);

		$oTab->setElement($oDialog->createRow($oGui->t('Korrespondenzsprache'), 'select', array(
			'db_alias' => 'ts_s',
			'db_column' => 'language_iso',
			'required' => 1,
			'select_options' => $aCorrespondenceLanguages,
		)));

		$oTab->setElement($oDialog->createRow($oGui->t('Kommentar'), 'textarea', array(
			'db_alias' => 'ts_s',
			'db_column' => 'comment',
		)));

		$oDialog->setElement($oTab);

		$oTab = $oDialog->createTab($oGui->t('Zahlungsmodalitäten', $oGui->gui_description));

		$oValidityGui = new \Ext_TC_Validity_Gui2('ts_sponsors_payment_conditions_validity');
		$paymentConditions = \Ext_TC_Util::addEmptyItem(\Ext_TS_Payment_Condition::getSelectOptions());
		$oValidityGui->setValiditySelectSettings($paymentConditions);
		$oValidityGui->setWDBasic('\TsSponsoring\Entity\PaymenttermsValidity');
		$oValidityGui->parent_hash = $oGui->hash;
		$oValidityGui->foreign_key = 'sponsor_id';
		$oValidityGui->parent_primary_key = 'id';
		$oTab->setElement($oValidityGui);
		$oDialog->setElement($oTab);

		// Das macht keinen Sinn, da das alleine zeitlich von einer FG abhängt
//		$oTab = $oDialog->createTab($oGui->t('Storno', $oGui->gui_description));
//
//		$oCancenllationGroup = new \Ext_Thebing_Cancellation_Group();
//		$aCancellationTerms = $oCancenllationGroup->getList(true);
//
//		$oGui = new \Ext_TC_Validity_Gui2('ts_sponsors_cancellations_validity');
//		$oGui->setValiditySelectSettings($aCancellationTerms);
//		$oGui->setWDBasic('\TsSponsoring\Entity\CancellationValidity');
//
//		$oTab->setElement($oGui);
//		$oDialog->setElement($oTab);

		return $oDialog;

	}

	/**
	 * @return array $aStates
	 */
	public static function getStates() {

		$aStates = [
			1	=> \L10N::t('Aktiv'),
			0	=> \L10N::t('Inaktiv')
		];

		return $aStates;

	}

	// im Filter die Länder anzeigen, die schon in der Liste benutzt werden.
	public static function getUsedCountries() {

	}

	protected function getImportService(): \Ts\Service\Import\AbstractImport {
		return new \Ts\Service\Import\Sponsor();
	}

	protected function getImportDialogId() {
		return 'SPONSOR_IMPORT_';
	}

	protected function addSettingFields(\Ext_Gui2_Dialog $oDialog) {

		$oRow = $oDialog->createRow($this->t('Vorhandene Kontakte leeren'), 'checkbox', ['db_column'=>'settings', 'db_alias'=>'delete_existing']);
		$oDialog->setElement($oRow);

		$oRow = $oDialog->createRow($this->t('Vorhandene Einträge aktualisieren'), 'checkbox', ['db_column'=>'settings', 'db_alias'=>'update_existing']);
		$oDialog->setElement($oRow);

	}

}
