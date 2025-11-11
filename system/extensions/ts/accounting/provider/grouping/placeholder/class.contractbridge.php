<?php

/**
 * Platzhalterklasse, die für Lehrerzahlungen und Unterkunftszahlungen die gemeinsamen Vertragsplatzhalter manipuliert
 */
abstract class Ext_TS_Accounting_Provider_Grouping_Placeholder_ContractBridge extends Ext_Thebing_Contract_Placeholder
{
	/**
	 * Wird in Ableitung überschrieben, definiert Übersetzung (s.u,)
	 * @var string
	 */
	protected $_sPlaceholderAreaTranslation = null;

	/** @var Ext_TS_Accounting_Provider_Grouping_Abstract */
	protected $_oGrouping;

	public function  __construct($oItem = null, $oGrouping = null) {
		$this->_oItem = $oItem;
		$this->_oGrouping = $oGrouping;
	}

	public function getPlaceholders($sType = '') {
		$aPlaceholders = parent::getPlaceholders($sType);

		foreach($aPlaceholders as $iKey => &$aPlaceholderData) {

			if($aPlaceholderData['section_key'] === 'contracts') {
				$aPlaceholderData['section'] = L10N::t($this->_sPlaceholderAreaTranslation.' bezahlen', Ext_TS_Accounting_Provider_Grouping_Abstract::sL10N);

				// Spezielle Vertagsplatzhalter rauslöschen
				unset($aPlaceholderData['placeholders']['item_contract_start']);
				unset($aPlaceholderData['placeholders']['item_contract_end']);
				unset($aPlaceholderData['placeholders']['item_contract_date']);
				unset($aPlaceholderData['placeholders']['item_contract_number']);
				unset($aPlaceholderData['placeholders']['item_salary']);
				unset($aPlaceholderData['placeholders']['item_master_contract_number']);

				$aPlaceholderData['placeholders']['selected_date'] = L10N::t('Datum der Bezahlung', Ext_TS_Accounting_Provider_Grouping_Abstract::sL10N);
				$aPlaceholderData['placeholders']['total_amount'] = L10N::t('Bezahlter Gesamtbetrag', Ext_TS_Accounting_Provider_Grouping_Abstract::sL10N);
				$aPlaceholderData['placeholders']['total_amount_word'] = L10N::t('Bezahlter Gesamtbetrag in Worten', Ext_TS_Accounting_Provider_Grouping_Abstract::sL10N);
				$aPlaceholderData['placeholders']['payment_comment'] = L10N::t('Zahlungskommentar', Ext_TS_Accounting_Provider_Grouping_Abstract::sL10N);
				$aPlaceholderData['placeholders']['single_payment_note'] = L10N::t('Kommentar der Position', Ext_TS_Accounting_Provider_Grouping_Abstract::sL10N);
				$aPlaceholderData['placeholders']['payment_method'] = L10N::t('Bezahlmethode', Ext_TS_Accounting_Provider_Grouping_Abstract::sL10N);

				// Momentan so gelöst – sollten eigentlich irgendwann dann bei allen Anbietern zur Verfügung stehen
				if($this instanceof Ext_TS_Accounting_Provider_Grouping_Accommodation_Placeholder) {
					$aPlaceholderData['placeholders']['customernumber'] = L10N::t('Kundennummer', Ext_TS_Accounting_Provider_Grouping_Abstract::sL10N);
					$aPlaceholderData['placeholders']['firstname'] = L10N::t('Vorname des Kunden', Ext_TS_Accounting_Provider_Grouping_Abstract::sL10N);
					$aPlaceholderData['placeholders']['surname'] = L10N::t('Nachname des Kunden', Ext_TS_Accounting_Provider_Grouping_Abstract::sL10N);
					$aPlaceholderData['placeholders']['name'] = L10N::t('Vorname und Nachname des Kunden', Ext_TS_Accounting_Provider_Grouping_Abstract::sL10N);
					$aPlaceholderData['placeholders']['first_start_date'] = L10N::t('Leistungszeitraum: Start', Ext_TS_Accounting_Provider_Grouping_Abstract::sL10N);
					$aPlaceholderData['placeholders']['last_end_date'] = L10N::t('Leistungszeitraum: Ende', Ext_TS_Accounting_Provider_Grouping_Abstract::sL10N);
					$aPlaceholderData['placeholders']['payment_number'] = L10N::t('Zahlungsnummer', Ext_TS_Accounting_Provider_Grouping_Abstract::sL10N);
					$aPlaceholderData['placeholders']['provider_number'] = L10N::t('Anbieternummer', Ext_TS_Accounting_Provider_Grouping_Abstract::sL10N);
				}

				// Platzhalter in der Kommunikation nicht einbauen, da dafür die Daten fehlen
				if(!$this->bCommunication) {
					$aPlaceholderData['placeholders']['provider_payment_overview'] = L10N::t('Zahlübersicht', Ext_TS_Accounting_Provider_Grouping_Abstract::sL10N);
				}

			} elseif($aPlaceholderData['section_key'] === 'individual') {
				// Individuelle Platzhalter entfernen
				unset($aPlaceholders[$iKey]);
			}

		}

		return $aPlaceholders;
	}

	protected function _getReplaceValue($sPlaceholder, array $aPlaceholder) {
		$sValue = '';

		switch($sPlaceholder) {
			case 'selected_date':
				$sDate = $this->_oGrouping->date;
				$oFormat = new Ext_Thebing_Gui2_Format_Date();
				$sValue = $oFormat->format($sDate);
				break;
			case 'total_amount':
				$oFormat = new Ext_Thebing_Gui2_Format_Amount();
				$sValue = $oFormat->format($this->_oGrouping->amount);
				break;
			case 'total_amount_word':

				$fAmount = (float)$this->_oGrouping->amount;
				$sLanguage = $this->sTemplateLanguage;

				$oNumbersWords = new \Ts\Helper\NumbersWords($sLanguage);

				$sValue = $oNumbersWords->toWords($fAmount);

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
			case 'payment_method':
				$oPaymentMethod = $this->_oGrouping->getPaymentMethod();
				$sValue = $oPaymentMethod->getName();
				break;
			case 'provider_payment_overview':
				// Die Daten von diesem Platzhalter werden beim Speichern der Bezahlung in der jeweiligen GUI generiert
				if(!$this->bCommunication) {
					$oHelper = new Ext_TS_Accounting_Provider_Grouping_Placeholder_PaymentOverviewTable($this, $this->sTemplateLanguage);
					$sValue = $oHelper->render();
				}
				break;
			default:
				$sValue = parent::_getReplaceValue($sPlaceholder, $aPlaceholder);
		}

		return $sValue;
	}
	
	public function getSchool() {
		
		$oSchool = \Ext_Thebing_School::getSchoolFromSessionOrFirstSchool();
		
		return $oSchool;
	}

}