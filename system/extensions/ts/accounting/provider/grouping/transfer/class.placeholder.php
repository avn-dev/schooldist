<?php

/**
 * Platzhalter für Bezahlungsübersichten beim Bezahlen von Transferanbietern
 */
class Ext_TS_Accounting_Provider_Grouping_Transfer_Placeholder extends Ext_Thebing_Placeholder
{
	/** @var Ext_TS_Accounting_Provider_Grouping_Abstract */
	protected $_oGrouping;

	public function  __construct($oItem = null, $oGrouping = null) {
		$this->_oItem = $oItem;
		$this->_oGrouping = $oGrouping;
	}

	public function getPlaceholders($sType = '') {

		$aPlaceholders = array(
			array(
				'section' => L10N::t('Generelle Platzhalter', 'Thebing » Placeholder'),
				'placeholders' => array(
					'today' => L10N::t('Heute', 'Thebing » Placeholder'),
					'school_name' => L10N::t('Schule', 'Thebing » Placeholder'),
					'school_address' => L10N::t('Adresse der Schule', 'Thebing » Placeholder'),
					'school_address_addon' => L10N::t('Adresszusatz der Schule', 'Thebing » Placeholder'),
					'school_zip' => L10N::t('PLZ der Schule', 'Thebing » Placeholder'),
					'school_city' => L10N::t('Stadt der Schule', 'Thebing » Placeholder'),
					'school_country' => L10N::t('Land der Schule', 'Thebing » Placeholder'),
					'school_url' => L10N::t('URL der Schule', 'Thebing » Placeholder'),
					'school_phone' => L10N::t('Telefon der Schule', 'Thebing » Placeholder'),
					'school_phone2'	=> L10N::t('Telefon 2 der Schule', 'Thebing » Placeholder'),
					'school_email' => L10N::t('E-Mail der Schule', 'Thebing » Placeholder'),
				)
			),
			array(
				'section' => L10N::t('Transferanbieter bezahlen', Ext_TS_Accounting_Provider_Grouping_Abstract::sL10N),
				'placeholders' => array(
					'item_salutation' => L10N::t('Anrede', Ext_TS_Accounting_Provider_Grouping_Abstract::sL10N),
					'item_lastname' => L10N::t('Nachname', Ext_TS_Accounting_Provider_Grouping_Abstract::sL10N),
					'item_firstname' => L10N::t('Vorname', Ext_TS_Accounting_Provider_Grouping_Abstract::sL10N),
					'item_name'	=> L10N::t('Vorname und Nachname', Ext_TS_Accounting_Provider_Grouping_Abstract::sL10N),
					'item_address' => L10N::t('Addresse', Ext_TS_Accounting_Provider_Grouping_Abstract::sL10N),
					//'item_address_addon' => L10N::t('Adresszusatz', Ext_TS_Accounting_Provider_Grouping_Abstract::sL10N),
					'item_zip' => L10N::t('ZIP', Ext_TS_Accounting_Provider_Grouping_Abstract::sL10N),
					'item_city'	=> L10N::t('Stadt', Ext_TS_Accounting_Provider_Grouping_Abstract::sL10N),
					'item_state' => L10N::t('Bundesland', Ext_TS_Accounting_Provider_Grouping_Abstract::sL10N),
					'item_country' => L10N::t('Land', Ext_TS_Accounting_Provider_Grouping_Abstract::sL10N),
					'item_email' => L10N::t('E-Mail', Ext_TS_Accounting_Provider_Grouping_Abstract::sL10N),
					'item_phone' => L10N::t('Telefon', Ext_TS_Accounting_Provider_Grouping_Abstract::sL10N),
					'item_mobile_phone' => L10N::t('Handy', Ext_TS_Accounting_Provider_Grouping_Abstract::sL10N),
					'item_bank'	=> L10N::t('Bank', Ext_TS_Accounting_Provider_Grouping_Abstract::sL10N),
					'item_bank_code' => L10N::t('Banknummer', Ext_TS_Accounting_Provider_Grouping_Abstract::sL10N),
					'item_bank_account'	=> L10N::t('Konto', Ext_TS_Accounting_Provider_Grouping_Abstract::sL10N),
					'item_account_holder' => L10N::t('Kontoinhaber', Ext_TS_Accounting_Provider_Grouping_Abstract::sL10N),
					'selected_date' => L10N::t('Datum der Bezahlung', Ext_TS_Accounting_Provider_Grouping_Abstract::sL10N),
					'payment_comment' => L10N::t('Zahlungskommentar', Ext_TS_Accounting_Provider_Grouping_Abstract::sL10N),
					'single_payment_note' => L10N::t('Kommentar der Position', Ext_TS_Accounting_Provider_Grouping_Abstract::sL10N)
				)
			)
		);

		// Platzhalter in der Kommunikation nicht einbauen, da dafür die Daten fehlen
		if(!$this->bCommunication) {
			$aPlaceholders[1]['placeholders']['provider_payment_overview'] = L10N::t('Zahlübersicht', Ext_TS_Accounting_Provider_Grouping_Abstract::sL10N);
		}

		return $aPlaceholders;
	}

	protected function _getReplaceValue($sPlaceholder, array $aPlaceholder) {

		switch($sPlaceholder) {
			case 'item_salutation':
				$sValue = Ext_TS_Contact::getSalutationForFrontend($this->_oItem->gender, $this->getLanguageObject());
				break;
			case 'item_lastname':
				$sValue = $this->_oItem->lastname;
				break;
			case 'item_firstname':
				$sValue = $this->_oItem->firstname;
				break;
			case 'item_name':
				$sValue = $this->_oItem->name;
				break;
			case 'item_address':
				$sValue = $this->_oItem->street;
				break;
//			case 'item_address_addon':
//				$sValue = $this->_oItem->additional_address;
//				break;
			case 'item_zip':
				$sValue = $this->_oItem->zip;
				break;
			case 'item_city':
				$sValue = $this->_oItem->city;
				break;
			case 'item_email':
				$sValue = $this->_oItem->email;
				break;
			case 'item_phone':
				$sValue = $this->_oItem->phone;
				break;
			case 'item_mobile_phone':
				$sValue = $this->_oItem->mobile_phone;
				break;
			case 'item_state':
				$sValue = $this->_oItem->state;
				break;
			case 'item_country':
				if($this->_oItem instanceof Ext_Thebing_Accommodation) {
					$sValue = $this->_oItem->ext_66;
				} else {
					$sCountry = $this->_oItem->country_iso;
					$oFormat = new Ext_Thebing_Gui2_Format_Country($this->sTemplateLanguage);
					$sValue = $oFormat->format($sCountry, $oDummy, $oDummy);
				}
				break;
			case 'item_bank':
				$sValue = $this->_oItem->bank_name;
				break;
			case 'item_bank_code':
				$sValue = $this->_oItem->bank_code;
				break;
			case 'item_bank_account':
				$sValue = $this->_oItem->bank_account_number;
				break;
			case 'item_account_holder':
				$sValue = $this->_oItem->bank_account_holder;
				break;
			case 'selected_date':
				$sDate = $this->_oGrouping->date;
				$oFormat = new Ext_Thebing_Gui2_Format_Date();
				$sValue = $oFormat->format($sDate);
				break;
			case 'payment_comment':
			case 'single_payment_note':

				if($sPlaceholder === 'payment_comment') {

					// »comment_single« gibt es nur bei Unterkunftsbezahlungen
					if($this->_oGrouping instanceof Ext_TS_Accounting_Provider_Grouping_Accommodation) {
						$sField = 'comment_single';
					} else {
						$sField = 'comment';
					}

				} elseif($sPlaceholder === 'single_payment_note') {
					$sField = 'payment_note';
				}

				$aPayments = (array)$this->_oGrouping->getJoinedObjectChilds('payments', true);
				foreach($aPayments as $oPayment) {
					// Der Zahlungskommentar ist bei allen gleich
					$sValue = $oPayment->$sField;
					break;
				}

				break;
			case 'provider_payment_overview':
				$sValue = '';
				if(!$this->bCommunication) {
					// Die Daten von diesem Platzhalter werden beim Speichern der Bezahlung in der jeweiligen GUI generiert
					$oHelper = new Ext_TS_Accounting_Provider_Grouping_Placeholder_PaymentOverviewTable($this, $this->sTemplateLanguage);
					$sValue = $oHelper->render();
				}
				break;
			default:
				$sValue = parent::_getReplaceValue($sPlaceholder, $aPlaceholder);
				break;
		}

		return $sValue;
	}

	public function getSchool() {
		if($this->_oItem instanceof Ext_Thebing_Accommodation) {
			$oSchool = Ext_Thebing_School::getSchoolFromSession();
		} else {
			$oSchool = $this->_oItem->getSchool();
		}
		return $oSchool;
	}

}