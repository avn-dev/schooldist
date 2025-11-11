<?php

namespace Ts\Service\Import;

use Tc\Exception\Import\ImportRowException;
use Tc\Service\Import\ErrorPointer;
use TsSponsoring\Service\SponsorNumberrange;

class Sponsor extends AbstractImport {
	
	protected $sEntity = \TsSponsoring\Entity\Sponsor::class;

	public function getAddressFields() {
		$aFields = [];
		$aFields[] = ['field' => 'Adresse', 'target' => 'address'];
		$aFields[] = ['field' => 'Adresszusatz', 'target' => 'address_addon'];
		$aFields[] = ['field' => 'PLZ', 'target' => 'zip'];
		$aFields[] = ['field' => 'Stadt', 'target' => 'city'];
		$aFields[] = ['field' => 'Land', 'target' => 'country_iso'];
		return $aFields;
	}

	public function getPaymentConditionFields() {
		$aFields = [];
		$aFields[] = ['field' => 'Zahlungsmodalität - Gültig für', 'target' => 'payment_condition_id'];
		$aFields[] = ['field' => 'Zahlungsmodalität - Gültig ab', 'target' => 'valid_from', 'special'=>'date_object', 'additional' => $this->sExcelDateFormat];
		return $aFields;
	}

	public function getFields() {
		
		$aSchools = \Ext_Thebing_Client::getSchoolList(true);

		/**
		 * Mapping
		 */
		$aFields = [];
		$aFields[] = ['field'=> 'Eindeutige Sponsor-ID'];
		$aFields[] = ['field'=> 'Name', 'target' => 'name'];
		$aFields[] = ['field'=> 'Abkürzung', 'target' => 'abbreviation'];
		$aFields[] = ['field'=> 'Nummer', 'target' => 'number'];
		$aFields[] = ['field'=> 'Schulen (kommagetrennt)', 'target' => 'schools', 'special'=>'array_split', 'additional'=> array_flip($aSchools)];
		$aFields[] = ['field' => 'Sponsoring', 'target' => 'sponsoring'];

		$aFields = array_merge($aFields, $this->getAddressFields());

		$aFields[] = ['field' => 'Korrespondenzsprache', 'target' => 'language_iso'];
		$aFields[] = ['field' => 'Kommentar', 'target' => 'comment', 'special'=>'nl2br'];

		$aFields = array_merge($aFields, $this->getPaymentConditionFields());

		$aFields = array_values($aFields);
		
		return $aFields;
	}
	
	public function getAdditionalWorksheets(): ?array {
		
		$aWorksheets = [
			'Mitarbeiter' => [
				0 => ['field' => 'Eindeutige Sponsor-ID'],
				1 => ['field' => 'Vorname', 'target'=>'firstname'],
				2 => ['field' => 'Nachname', 'target'=>'lastname'],
				3 => ['field' => 'Anrede', 'target'=>'gender', 'special'=>'gender'],
				4 => ['field' => 'Geburtstag', 'target'=>'birthday', 'special'=>'date_object', 'additional' => $this->sExcelDateFormat],
				5 => ['field' => 'E-mail', 'target'=>'email'],
				6 => ['field' => 'Telefon', 'target'=>'phone'],
				7 => ['field' => 'Fax', 'target'=>'fax'],
				8 => ['field' => 'Kommentar', 'target' => 'comment', 'special'=>'nl2br']
			]
		];
	
		return $aWorksheets;
	}
	
	protected function getBackupTables() {
		
		$aTables = [
			'ts_sponsors_payment_conditions_validity',
			'tc_flex_sections_fields_values'
		];
	
		return $aTables;
	}
		
	protected function processItem(array &$aItem, int $iItem, array $aAdditionalWorksheetData=null) {

		try {

			$sReport = 'update';
			
			$aData = [];
			\Ext_Thebing_Import::processItems($this->aFields, $aItem, $aData);

			if(in_array(null, $aData['schools'])) {
				throw new ImportRowException(\L10N::t('Schule nicht gefunden!', \Ext_Thebing_Accommodation_Gui2::L10N_PATH));
			}

			$aWorksheets = $this->getAdditionalWorksheets();
			
			// Gibt es die Unterkunft schon?
			$oSponsor = \TsSponsoring\Entity\Sponsor::getRepository()->findOneBy(['name'=>$aData['name']]);

			// Wenn Unterkunft schon vorhanden und nicht aktualisiert werden soll
			if(
				$oSponsor !== null &&
				!$this->aSettings['update_existing']
			) {
				return;
			}
			
			if($oSponsor === null) {
				$sReport = 'insert';
				$oSponsor = new \TsSponsoring\Entity\Sponsor();
			}

			/*
			 * Nummer
			 */

			if(!empty($aData['number'])) {
				$bNumberIsPossible = SponsorNumberrange::checkPossibility($oSponsor, $aData['number']);

				if (!$bNumberIsPossible) {
					throw new ImportRowException(sprintf('Duplicate number "%s"', $aData['number']));
				}

				$oNumberrange = \Ext_TS_Numberrange_Accommodation::getObject($oSponsor);
				$iNumberrangeId = ($oNumberrange) ? $oNumberrange->getId() : 0;

				$oSponsor->number = $aData['number'];
				$oSponsor->numberrange_id = $iNumberrangeId;
			}

			unset($aData['number']);

			$aSponsoringTypes = \TsSponsoring\Entity\Sponsor::getSponsoringTypes();

			$aData['sponsoring'] = \Ext_Thebing_Import::processSpecial(['array'], $aData['sponsoring'], array_flip($aSponsoringTypes));
			if($aData['sponsoring'] === null) {
				throw new ImportRowException(\L10N::t('Sponsoring nicht gefunden!', \Ext_Thebing_Accommodation_Gui2::L10N_PATH).' ('.\L10N::t('Mögliche Werte', \Ext_Thebing_Accommodation_Gui2::L10N_PATH).': '.implode(', ', $aSponsoringTypes).')');
			}

			$sInterfaceLanguage	= \Ext_TC_System::getInterfaceLanguage();
			$aCorrespondenceLanguages = \Ext_Thebing_Data::getCorrespondenceLanguages(true, $sInterfaceLanguage);

			$aData['language_iso'] = \Ext_Thebing_Import::processSpecial(['array'], $aData['language_iso'], array_flip($aCorrespondenceLanguages));
			if($aData['language_iso'] === null) {
				throw new ImportRowException(\L10N::t('Korrespondenzsprache nicht gefunden!', \Ext_Thebing_Accommodation_Gui2::L10N_PATH).' ('.\L10N::t('Mögliche Werte', \Ext_Thebing_Accommodation_Gui2::L10N_PATH).': '.implode(', ', $aCorrespondenceLanguages).')');
			}

			// Adresse

			$aAddressFields = $this->getAddressFields();
			$aAddress = [];

			foreach($aAddressFields as $aField) {
				$aAddress[] = $aData[$aField['target']];
				unset($aData[$aField['target']]);
			}

			$aAddressData = [];
			\Ext_Thebing_Import::processItems($aAddressFields, $aAddress, $aAddressData);

			$aCountries = \Ext_Thebing_Data::getCountryList(true, true);

			$aAddressData['country_iso'] = \Ext_Thebing_Import::processSpecial(['array'], $aAddressData['country_iso'], array_flip($aCountries));
			if($aAddressData['country_iso'] === null) {
				throw new ImportRowException(\L10N::t('Land nicht gefunden!', \Ext_Thebing_Accommodation_Gui2::L10N_PATH).' ('.\L10N::t('Mögliche Werte', \Ext_Thebing_Accommodation_Gui2::L10N_PATH).': '.implode(', ', $aCountries).')');
			}

			$oAddress = $oSponsor->getAddress();
			foreach ($aAddressData as $sField => $mValue) {
				$oAddress->$sField = $mValue;
			}

			// Zahlungsmodalität

			$aPaymentConditionFields = $this->getPaymentConditionFields();
			$aPaymentCondition = [];

			foreach($aPaymentConditionFields as $aField) {
				$aPaymentCondition[] = $aData[$aField['target']];
				unset($aData[$aField['target']]);
			}

			$aPaymentConditionData = [];
			\Ext_Thebing_Import::processItems($aPaymentConditionFields, $aPaymentCondition, $aPaymentConditionData);

			if(
				!empty($aPaymentConditionData['payment_condition_id']) &&
				!empty($aPaymentConditionData['valid_from'])
			) {

				$aPaymentConditions = \Ext_TS_Payment_Condition::getSelectOptions();

				$aPaymentConditionData['payment_condition_id'] = \Ext_Thebing_Import::processSpecial(['array'], $aPaymentConditionData['payment_condition_id'], array_flip($aPaymentConditions));
				if($aPaymentConditionData['payment_condition_id'] === null) {
					throw new ImportRowException(\L10N::t('Zahlungsbedingung nicht gefunden!', \Ext_Thebing_Accommodation_Gui2::L10N_PATH).' ('.\L10N::t('Mögliche Werte', \Ext_Thebing_Accommodation_Gui2::L10N_PATH).': '.implode(', ', $aPaymentConditions).')');
				}

				$oPaymentCondition = $oSponsor->getJoinedObjectChild('payment_conditions');
				$oPaymentCondition->payment_condition_id = $aPaymentConditionData['payment_condition_id'];
				$oPaymentCondition->valid_from = $aPaymentConditionData['valid_from'];
			}

			foreach($aData as $sField=>$mValue) {
				$oSponsor->$sField = $mValue;
			}

			// Mitglieder
			if(!empty($aAdditionalWorksheetData['Mitarbeiter'])) {

				// Alte Kontakte löschen
				if($oSponsor->exist() && $this->aSettings['delete_existing']) {
					$aContacts = $oSponsor->getContacts();
					foreach($aContacts as $oContact) {
						$oContact->delete();
					}
				}

				foreach($aAdditionalWorksheetData['Mitarbeiter'] as $iRowIndex => $aContact) {

					$aContactData = [];
					\Ext_Thebing_Import::processItems($aWorksheets['Mitarbeiter'], $aContact, $aContactData);

					$sName = $aContactData['lastname'].', '.$aContactData['firstname'];

					// Gibt es den Mitarbeiter schon?
					$oContact = $oSponsor->getJoinedObjectChildByValue('contacts', 'name', $sName);
					/* @var \TsSponsoring\Entity\Sponsor\Contact $oContact */
					if($oContact === null) {
						$oContact = $oSponsor->getJoinedObjectChild('contacts');
					}

					try {

						$oEmail = $oContact->getFirstEmailAddress();
						$oEmail->email = $aContactData['email'];

						$oContact->setDetail('phone_private', $aContactData['phone']);
						$oContact->setDetail('fax', $aContactData['fax']);
						$oContact->setDetail('comment', $aContactData['comment']);

						unset($aContactData['phone']);
						unset($aContactData['fax']);
						unset($aContactData['comment']);
						unset($aContactData['email']);

						foreach($aContactData as $sField=>$mValue) {
							$oContact->$sField = $mValue;
						}

						$oEmail->validate(true);

						$oContact->validate(true);

					} catch(\Exception $e) {
						throw (new ImportRowException($e->getMessage()))
							->pointer($this->getWorksheetTitle('Mitarbeiter'), $iRowIndex);
					}
				}
			}

			$oSponsor->saveParents();
			$oSponsor->save();
			
			$this->aReport[$sReport]++;
			
			return $oSponsor->getId();
			
		} catch(\Exception $e) {

			$pointer = ($e instanceof ImportRowException && $e->hasPointer())
				? $e->getPointer()
				: new ErrorPointer(null, $iItem);

			$this->aErrors[$iItem] = [['message'=>$e->getMessage(), 'pointer' => $pointer]];

			$this->aReport['error']++;
			
			if(empty($this->aSettings['skip_errors'])) {
				throw new \Exception('Terminate import');
			}
	
		}
		
	}
	
	protected function getCheckItemFields(array $aPreparedData) {
		
	}
		
}
