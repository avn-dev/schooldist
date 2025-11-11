<?php

namespace TsCompany\Service\Import;

use Core\Helper\BitwiseOperator;
use Tc\Exception\Import\ImportRowException;
use Ts\Service\Import\AbstractImport;
use Tc\Service\Import\ErrorPointer;
use TsCompany\Entity\AbstractCompany;
use TsCompany\Service\NumberRange;

class Agency extends AbstractImport {
	
	protected $sEntity = 'Ext_Thebing_Agency';

	public $provideExport = true;

	public function getFlexibleFields() {

		$aFlexFields = parent::getFlexibleFields();

		$oContact = new \Ext_Thebing_Agency_Contact();
		$aFlexFields['Mitarbeiter'] = $oContact->getFlexibleFields();

		return $aFlexFields;
	}

	/**
	 * @param string $sName
	 *
	 * @return bool
	 */
	protected function existCommissionCategory($sName) {
		$oCommissionCategory = $this->loadCommissionCategory($sName);
		if(!$oCommissionCategory instanceof \Ext_Thebing_Provision_Group) {
			return false;
		}
		return true;
	}

	/**
	 * @param string $sName
	 *
	 * @return \Ext_Thebing_Provision_Group|null
	 */
	protected function loadCommissionCategory($sName) {
		return \Ext_Thebing_Provision_Group::getRepository()->findOneBy(['name' => $sName]);
	}

	/**
	 * Prüft ob eine Bezahlkategorie existiert.
	 *
	 * @param string $sName
	 *
	 * @return bool
	 */
	protected function existPaymentCategory($sName) {
		$oPaymentCategory = $this->loadPaymentCategory($sName);
		if(!$oPaymentCategory instanceof \Ext_TS_Payment_Condition) {
			return false;
		}
		return true;
	}

	/**
	 * @param string $sName
	 *
	 * @return \Ext_TS_Payment_Condition|null
	 */
	protected function loadPaymentCategory($sName) {
		return \Ext_TS_Payment_Condition::getRepository()->findOneBy(['name' => $sName]);
	}

	/**
	 * Prüft ob eine Stornogebühr mit dem Namen existiert.
	 *
	 * @param string $sName
	 *
	 * @return bool
	 */
	protected function existCancellationsGroup($sName) {
		$oCancellationFees = $this->loadCancellationsGroup($sName);
		if(!$oCancellationFees instanceof \Ext_Thebing_Cancellation_Group) {
			return false;
		}
		return true;
	}

	/**
	 * Lädt anhand des Namens eine Stornogebühr.
	 *
	 * @param string $sName
	 *
	 * @return \Ext_Thebing_Cancellation_Group|null
	 */
	protected function loadCancellationsGroup($sName) {
		return \Ext_Thebing_Cancellation_Group::getRepository()->findOneBy(['name' => $sName]);
	}

	public function getFields() {
		
		$sSql = "
				SELECT
					`iso4217`,
					`id`
				FROM
					kolumbus_currency
				";
		$aCurrencies = \DB::getQueryPairs($sSql);

		$aPaymentMethods = \Ext_Thebing_Agency::getAgencyPaymentMethods();
		$aPaymentMethods = array_flip($aPaymentMethods);
		
		/**
		 * Mapping
		 */
		$aFields = [];
		$aFields[0] = ['field'=> 'Eindeutige Agentur-ID', 'unique_id' => 'id'];
		$aFields[1] = ['field'=> 'Agency - Name', 'target' => 'ext_1'];
		$aFields[2] = ['field'=> 'Agency - Number', 'target' => 'number'];
		$aFields[3] = ['field'=> 'Agency - ZIP', 'target' => 'ext_4'];
		$aFields[4] = ['field'=> 'Agency - Address', 'target' => 'ext_3'];
		$aFields[5] = ['field'=> 'Agency - Address addon', 'target' => 'ext_35'];
		$aFields[6] = ['field'=> 'Agency - City', 'target' => 'ext_5'];
		$aFields[7] = ['field'=> 'Agency - Country', 'target' => 'ext_6', 'special' => 'country'];
		$aFields[8] = ['field'=> 'Internal field (DB)', 'target' => 'comments'];
		$aFields[9] = ['field'=> 'Agency - Website', 'target' => 'ext_10'];
		$aFields[10] = ['field'=> 'Staff (full time)', 'target' => 'staffs'];
		$aFields[11] = ['field'=> 'Agency - Year of foundation', 'target' => 'founding_year'];
		$aFields[12] = ['field'=> 'Agency - Customers per year (Total)', 'target' => 'customers'];
		$aFields[13] = ['field'=> 'Agency - Category', 'target' => 'category'];
		$aFields[14] = ['field'=> 'Agency - Note', 'target' => 'comment'];
		$aFields[15] = ['field'=> 'Agency - Group (separated with |)', 'target' => 'groups'];
		$aFields[16] = [
			'field'=> 'Agency - Correspondence language (only full language name support (example: English))',
			'target' => 'ext_33',
			'special' => 'language'
		];
		$aFields[17] = [
			'field' => 'Agency - Commission valid from (dd.mm.yyyy)',
			'target' => 'commission_valid_from',
			'special' => 'date_object',
			'additional' => $this->sExcelDateFormat,
		];
		$aFields[18] = [
			'field' => 'Agency - Commission category name',
			'target' => 'commission_name',
		];
		$aFields[19] = [
			'field' => 'Agency - Payment category valid from (dd.mm.yyyy)',
			'target' => 'payment_valid_from',
			'special' => 'date_object',
			'additional' => $this->sExcelDateFormat,
		];
		$aFields[20] = [
			'field' => 'Agency - Payment category name',
			'target' => 'payment_name',
		];
		$aFields[21] = [
			'field' => 'Agency - Cancellation name',
			'target' => 'cancellation_name',
		];
		$aFields[22] = [
			'field' => 'Agency - Cancellation valid from (dd.mm.yyyy)',
			'target' => 'cancellation_valid_from',
			'special' => 'date_object',
			'additional' => $this->sExcelDateFormat,
		];

		$aFields[23] = [
			'field'=> 'Agency - Nickname', 
			'target' => 'ext_2'
		];

		$aFields[24] = [
			'field'=> 'Active', 
			'target' => 'status', 
			'special' => 'yes_no'
		];

		$aFields[25] = [
			'field'=> 'Agency - State', 
			'target' => 'state'
		];

		$aFields[26] = [
			'field'=> 'Currency (ISO code)', 
			'target' => 'ext_23',
			'special' => 'array',
			'additional' => $aCurrencies
		];

		$aFields[27] = [
			'field'=> 'Payment method ('.implode(', ', array_keys($aPaymentMethods)).')', 
			'target' => 'ext_26', 
			'special' => 'array',
			'additional' => $aPaymentMethods
		];

		$aFields[28] = [
			'field'=> 'Invoice (yes, no, gross, net)', 
			'target' => 'invoice', 
			'special' => 'array_lower',
			'additional' => [
				'no' => 0,
				'yes' => 1,
				'gross' => 1,
				'net' => 2,
			]
		];

		$aFields[29] = [
			'field'=> 'LoA (yes, no)', 
			'target' => 'ext_29', 
			'special' => 'yes_no'
		];

		$aFields[30] = [
			'field'=> 'Comment payment type', 
			'target' => 'ext_38'
		];

		return $aFields;
	}

	public function getAdditionalWorksheets(): ?array {

		$aWorksheets = [
			'Mitarbeiter' => [
				0 => ['field' => 'Eindeutige Agentur-ID', 'unique_id' => 'company_id'],
				1 => ['field' => 'Anrede', 'target'=>'gender', 'special'=>'gender'],
				2 => ['field' => 'Vorname', 'target'=>'firstname'],
				3 => ['field' => 'Nachname', 'target'=>'lastname'],
				4 => ['field' => 'Abteilung', 'target'=>'group'],
				5 => ['field' => 'E-mail', 'target'=>'email'],
				6 => ['field' => 'Telefon', 'target'=>'phone'],
				7 => ['field' => 'Fax', 'target'=>'fax'],
				8 => ['field' => 'Skype', 'target'=>'skype'],
				9 => ['field' => 'Hauptkontaktperson (ja | nein)', 'target'=>'master_contact', 'special' => 'yes_no'],
				10 => ['field' => 'Kommentar', 'target'=>'comment'],
			],
			'Kommentare' => [
				0 => ['field' => 'Eindeutige Agentur-ID', 'unique_id' => 'company_id'],
				1 => ['field' => 'Titel', 'target'=>'title'],
				2 => ['field' => 'Betreff', 'target'=>'subject_id'],
				3 => ['field' => 'Aktivität', 'target'=>'activity_id'],
				4 => ['field' => 'Text', 'target'=>'text'],
				5 => ['field' => 'Agenturkontakt (Nachname, Vorname)', 'target'=>'company_contact_id'],
				6 => ['field' => 'Nachhaken', 'target'=>'follow_up', 'special'=>'date_object', 'additional' => $this->sExcelDateFormat]
			]
		];

		return $aWorksheets;
	}

	protected function getBackupTables() {
		
		$aTables = [
				'ts_companies',
				'ts_companies_contacts',
				'ts_companies_numbers',
				'ts_companies_comments',
				'ts_commission_categories',
				'kolumbus_agency_categories',
				'tc_cancellation_conditions_groups',
				// Multi select
				'kolumbus_agency_groups',
				// Guis im Dialog
				'ts_agencies_to_commission_categories',
				'ts_agencies_payment_conditions_validity',
				'kolumbus_validity',
				'tc_flex_sections_fields_values'
			];
	
		return $aTables;
	}

	protected function processItem(array &$aItem, int $iItem, array $aAdditionalWorksheetData=null) {

		try {

			$aData = [];
			$aData['active'] = 1;
			$aData['idClient'] = \Ext_Thebing_Client::getClientId();
			$aData['created'] = date('Y-m-d H:i:s');
			BitwiseOperator::add($aData['type'], AbstractCompany::TYPE_AGENCY);

			$aEntityProcess = [];
			foreach($aItem as $iKey => $sValue) {
				$aEntityProcess['field_'.$iKey] = $sValue;
			}

			\Ext_Thebing_Import::processItems($this->aFields, $aEntityProcess, $aData);

			// Wird weiter unten separat behandelt
			$sNumber = $aData['number'];
			
			foreach ($aData as $field => $value) {
				if (
					$field == 'category' ||
					$field == 'groups' ||
					$field == 'commission_valid_from' ||
					$field == 'commission_name' ||
					$field == 'payment_valid_from' ||
					$field == 'payment_name' ||
					$field == 'cancellation_name' ||
					$field == 'cancellation_valid_from' ||
					$field == 'number'
				) {
					// Die Fälle werden seperat unten behandelt (insertData() geht mit den Feldern nicht.)
					// number wird automatisch beim Dialogspeichern gesetzt.
					unset($aData[$field]);
				}
			}

			// Alle weitere Spalten als Kommentar eintragen
			$iCountFields = count($this->aFields);
			for($i=$iCountFields; $i < count($aItem); $i++) {
				$this->aFields[$i] = ['field'=>$this->aTitles[$i], 'target' => 'comments'];
			}

			// Leere Einträge überspringen
			if(!empty($aItem[0])) {

				$this->prepareItem($aItem, $aData);

				$aCheckFields = $this->getCheckItemFields($aData);

				$aCheck = \Ext_Thebing_Import::checkEntry($this->sTable, $aCheckFields);

				if(empty($aCheck)) {

					$iEntityId = \DB::insertData($this->sTable, $aData);

					$this->aReport['insert']++;

				} else {

					if($this->aSettings['update_existing']) {

						$aIntersect = array_intersect_key($aCheck, $aData);

						foreach($aData as $sKey=>&$sValue) {
							if(
								empty($sValue) &&
								!empty($aIntersect[$sKey])
							) {
								$sValue = $aIntersect[$sKey];
							}
						}

						$aData['created'] = $aIntersect['created'];

						\DB::updateData($this->sTable, $aData, "`id` = ".(int)$aCheck['id']);

						$this->aReport['update']++;

					}

					$iEntityId = $aCheck['id'];
				}

				$oAgency = \Ext_Thebing_Agency::getInstance($iEntityId);

				/*
				 * Nummer
				 */

				if(!empty($sNumber)) {
					$bNumberIsPossible = NumberRange::checkPossibility($oAgency, $sNumber);

					if (!$bNumberIsPossible) {
						throw new ImportRowException(sprintf('Duplicate number "%s"', $sNumber));
					}

					$oNumberrange = NumberRange::getObject($oAgency);
					$iNumberrangeId = ($oNumberrange) ? $oNumberrange->getId() : 0;

					\DB::updateJoinData('ts_companies_numbers', ['company_id' => $oAgency->getId()], [[
						'number' => $sNumber,
						'numberrange_id' => $iNumberrangeId
					]]);

				}

				// Agency group
				if(!empty($aItem[15])) {

					$aAgencyGroups = explode('|', $aItem[15]);

					$aAgencyGroupIds = array();

					foreach($aAgencyGroups as $sAgencyGroup) {

						$sSql = "
								SELECT
									*
								FROM
									`kolumbus_agency_groups`
								WHERE
									`name` LIKE :name AND
									`active` = 1
								";

						$aGroup = \DB::getQueryRow($sSql, [
							'name' => trim($sAgencyGroup)
						]);

						if(empty($aGroup)) {
							$oCategory = new \Ext_Thebing_Agency_Group();
							$oCategory->name = trim($sAgencyGroup);
							$oCategory->active = 1;
							$oCategory->idClient = \Ext_Thebing_Client::getClientId();
							$oCategory->save();
							$aGroup['id'] = $oCategory->id;
						}

						$aAgencyGroupIds[] = $aGroup['id'];

					}

					if(!empty($aAgencyGroupIds)) {

						$aAgencyGroupAssignments = array();

						foreach($aAgencyGroupIds as $iAgencyGroupId) {
							$aAgencyGroupAssignments[] = array(
								'agency_id' => (int)$iEntityId,
								'group_id' => (int)$iAgencyGroupId
							);
						}

						\DB::insertMany('kolumbus_agency_groups_assignments', $aAgencyGroupAssignments, true);

					}

				}

				// Ablauf der Provisionskategorie und der Zuweisung
				if(
					!empty($aItem[17]) &&
					!empty($aItem[18])
				) {

					$sCommissionCategoryName = $aItem[18];
					$sCommissionCategoryValidFrom = $aItem[17];

					$timeStamp = \Ext_Thebing_Import::processSpecial('date_object', $sCommissionCategoryValidFrom, $this->sExcelDateFormat);

					$dValidFrom = \DateTime::createFromFormat('Y-m-d', $timeStamp);

					if(!$dValidFrom) {
						throw new ImportRowException(sprintf(\L10N::t("Das Datumsformat in Spalte \"%s\" ist falsch", \Ext_Thebing_Accommodation_Gui2::L10N_PATH), $this->aFields[17]['field']));
					}

					if($this->existCommissionCategory($sCommissionCategoryName)) {
						$oCommissionCategory = $this->loadCommissionCategory($sCommissionCategoryName);
					} else {
						$oCommissionCategory = new \Ext_Thebing_Provision_Group();
					}

					$oCommissionCategory->name = $sCommissionCategoryName;
					$oCommissionCategory->save();

					$oValidity = new \Ext_Thebing_Agency_Provision_Group();
					$oValidity->description = 'import';
					$oValidity->agency_id = $iEntityId;
					$oValidity->group_id = $oCommissionCategory->id;
					$oValidity->valid_from = $timeStamp;

					$bValidate = $oValidity->validate();

					$iLatestEntry = (int)$oValidity->getLatestEntry();
					if($iLatestEntry > 0) {
						$oLatestEntry = \Ext_Thebing_Agency_Provision_Group::getInstance($iLatestEntry);
						if(
							$oLatestEntry->valid_from == $oValidity->valid_from &&
							$oLatestEntry->group_id == $oValidity->group_id
						) {
							$bValidate = false;
						}
					}

					if($bValidate === true) {
						$oValidity->save();
					}

				}

				// Ablauf der Bezahlkategorie und deren Zuweisung
				if(
					!empty($aItem[19]) &&
					!empty($aItem[20])
				) {

					$sPaymentCategoryName = $aItem[20];
					$sPaymentCategoryValidFrom = $aItem[19];

					$timeStamp = \Ext_Thebing_Import::processSpecial('date_object', $sPaymentCategoryValidFrom, $this->sExcelDateFormat);

					$dValidFrom = \DateTime::createFromFormat('Y-m-d', $timeStamp);

					if(!$dValidFrom) {
						throw new ImportRowException(sprintf(\L10N::t("Das Datumsformat in Spalte \"%s\" ist falsch", \Ext_Thebing_Accommodation_Gui2::L10N_PATH), $this->aFields[19]['field']));
					}

					if($this->existPaymentCategory($sPaymentCategoryName)) {
						$oPaymentCategory = $this->loadPaymentCategory($sPaymentCategoryName);
					} else {
						$oPaymentCategory = new \Ext_TS_Payment_Condition();
					}

					$oPaymentCategory->name = $sPaymentCategoryName;
					$oPaymentCategory->save();

					// Gültigkeit prüfen
					$oValidity = \Ext_TS_Agency_PaymentConditionValidity::getValidEntry($oAgency, $dValidFrom);

					// Nur wenn gültige Kategorie abweichend ist
					if(
						empty($oValidity) ||
						$oValidity->payment_condition_id != $oPaymentCategory->id
					)  {
						$oNewValidity = new \Ext_TS_Agency_PaymentConditionValidity;
						$oNewValidity->agency_id = $oAgency->id;
						$oNewValidity->payment_condition_id = $oPaymentCategory->id;
						$oNewValidity->school_id = 0;
						$oNewValidity->valid_from = $timeStamp;
						$oNewValidity->comment = 'Import';

						$bValidate = $oNewValidity->validate();

						if($bValidate === true) {
							$oNewValidity->save();
						}
					}

				}

				// Ablauf der Stornokategorien und deren Zuweisung
				if(
					!empty($aItem[21]) &&
					!empty($aItem[22])
				) {

					$sCancellationName = $aItem[21];
					$sCancellationValidFrom = $aItem[22];

					$timeStamp = \Ext_Thebing_Import::processSpecial('date_object', $sCancellationValidFrom, $this->sExcelDateFormat);

					$dValidFrom = \DateTime::createFromFormat('Y-m-d', $timeStamp);

					if(!$dValidFrom) {
						throw new ImportRowException(sprintf(\L10N::t("Das Datumsformat in Spalte \"%s\" ist falsch", \Ext_Thebing_Accommodation_Gui2::L10N_PATH), $this->aFields[22]['field']));
					}

					if($this->existCancellationsGroup($sCancellationName)) {
						$oCancellationFees = $this->loadCancellationsGroup($sCancellationName);
					} else {
						$oCancellationFees = new \Ext_Thebing_Cancellation_Group();
					}

					$oCancellationFees->name = $sCancellationName;
					$oCancellationFees->active = 1;
					$oCancellationFees->save();

					$oValidity = new \Ext_Thebing_Validity_WDBasic();
					$oValidity->active = 1;
					$oValidity->parent_id = $iEntityId;
					$oValidity->parent_type = 'agency';
					$oValidity->item_id = $oCancellationFees->id;
					$oValidity->item_type = 'cancellation_group';
					$oValidity->description = 'Import cancellationfees';
					$oValidity->valid_from = $dValidFrom->format('Y-m-d');

					$bValidate = $oValidity->validate();

					$iLatestEntry = $oValidity->getLatestEntry();
					if($iLatestEntry > 0) {
						$oLatestEntry = \Ext_Thebing_Validity_WDBasic::getInstance($iLatestEntry);
						if(
							$oLatestEntry->valid_from == $oValidity->valid_from &&
							$oLatestEntry->item_id == $oValidity->item_id
						) {
							$bValidate = false;
						}
					}

					if($bValidate) {
						$oValidity->save();
					}
				}

				$aWorksheets = $this->getAdditionalWorksheets();

				if(!empty($aAdditionalWorksheetData['Mitarbeiter'])) {

					// Alte Kontakte löschen
					if($this->aSettings['delete_existing']) {
						\DB::updateData('ts_companies_contacts', ['active' => 0], ['company_id' => $iEntityId]);
					}

					foreach($aAdditionalWorksheetData['Mitarbeiter'] as $aContact) {

						$aContactData = [];
						\Ext_Thebing_Import::processItems($aWorksheets['Mitarbeiter'], $aContact, $aContactData);

						// Mitarbeiter anlegen
						$aInsert = array();
						$aInsert['created']			= date('Y-m-d H:i:s');
						$aInsert['company_id']		= (int)$iEntityId;
						$aInsert['transfer'] 	 	= 1;
						$aInsert['accommodation']	= 1;
						$aInsert['reminder']		= 1;

						foreach($aContactData as $sField => $mValue) {
							$aInsert[$sField] = $mValue;
						}

						/*
						 * @todo Erkennung von doppelten mit E-Mail und/oder Name
						 */
						$iContactId = \DB::insertData('ts_companies_contacts', $aInsert);

						if(
							!empty($iContactId) &&
							!empty($this->aFlexFields['Mitarbeiter'])
						) {
							$this->oImport->saveFlexValues($this->aFlexFields['Mitarbeiter'], $aContact, $iContactId);
						}

					}

				}

				// Kommentare
				if(!empty($aAdditionalWorksheetData['Kommentare'])) {

					// Alte Kommentare löschen
					if($this->aSettings['delete_existing']) {
						\DB::updateData('ts_companies_comments', ['active' => 0], ['company_id' => $iEntityId]);
					}

					$oSubject = \Ext_Thebing_Marketing_Subject::getInstance();
					$aSubjects = $oSubject->getList(0, true);
					unset($aSubjects[""]);

					$oActivity = \Ext_Thebing_Marketing_Activity::getInstance();
					$aActivities = $oActivity->getList(0,true);
					unset($aActivities[""]);

					foreach($aAdditionalWorksheetData['Kommentare'] as $iRowIndex => $aComment) {

						$aCommentData = [];
						\Ext_Thebing_Import::processItems($aWorksheets['Kommentare'], $aComment, $aCommentData);

						if(!empty($aCommentData['subject_id'])) {

							$aCommentData['subject_id'] = \Ext_Thebing_Import::processSpecial(['array'], $aCommentData['subject_id'], array_flip($aSubjects));
							if($aCommentData['subject_id'] === null) {
								throw (new ImportRowException(\L10N::t('Betreff nicht gefunden!', \Ext_Thebing_Accommodation_Gui2::L10N_PATH).' ('.\L10N::t('Mögliche Werte', \Ext_Thebing_Accommodation_Gui2::L10N_PATH).': '.implode(', ', $aSubjects).')'))
									->pointer($this->getWorksheetTitle('Kommentare'), $iRowIndex);
							}
						}

						if(!empty($aCommentData['activity_id'])) {

							$aCommentData['activity_id'] = \Ext_Thebing_Import::processSpecial(['array'], $aCommentData['activity_id'], array_flip($aActivities));
							if ($aCommentData['activity_id'] === null) {
								throw (new ImportRowException(\L10N::t('Kontaktart nicht gefunden!', \Ext_Thebing_Accommodation_Gui2::L10N_PATH) . ' (' . \L10N::t('Mögliche Werte', \Ext_Thebing_Accommodation_Gui2::L10N_PATH) . ': ' . implode(', ', $aActivities) . ')'))
									->pointer($this->getWorksheetTitle('Kommentare'), $iRowIndex);
							}
						}

						if(!empty($aCommentData['company_contact_id'])) {

							$aContacts = $oAgency->getContacts(true);

							$aCommentData['company_contact_id'] = \Ext_Thebing_Import::processSpecial(['array'], $aCommentData['company_contact_id'], array_flip($aContacts));
							if($aCommentData['company_contact_id'] === null) {
								throw (new ImportRowException(\L10N::t('Kontakt nicht gefunden!', \Ext_Thebing_Accommodation_Gui2::L10N_PATH).' ('.\L10N::t('Mögliche Werte', \Ext_Thebing_Accommodation_Gui2::L10N_PATH).': '.implode(' | ', $aContacts).')'))
									->pointer($this->getWorksheetTitle('Kommentare'), $iRowIndex);
							}
						}

						$aInsert = array();
						$aInsert['created']			= date('Y-m-d H:i:s');
						$aInsert['company_id']		= (int)$iEntityId;

						foreach($aCommentData as $sField => $mValue) {
							$aInsert[$sField] = $mValue;
						}

						$aInsert['follow_up'] = ($aInsert['follow_up'] == '') ? '0000-00-0' : $aInsert['follow_up'];

						/*
						 * @todo Erkennung von doppelten mit E-Mail und/oder Name
						 */
						\DB::insertData('ts_companies_comments', $aInsert);

					}
				}

				// Zusätzliche Kommentare eintragen
				$sComment = "";
				foreach((array)$this->aFields as $iKey=>$aField) {

					if($aField['target'] == 'comments') {

						if(empty($aItem[$iKey])) {
							continue;
						}

						$sValue = $aField['field'].": ".$aItem[$iKey];

						$sComment .= $sValue."\n";

					}

				}

				$this->saveComment($iEntityId, $sComment);

			}

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

		return $iEntityId;
	}

	protected function prepareItem(array &$aItem, array &$aPreparedData) {

		if(!empty($aItem[7])) {
			$aCountries = \Ext_Thebing_Data::getCountryList(true, true);

			$aPreparedData['ext_6'] = \Ext_Thebing_Import::processSpecial(['array'], $aItem[7], array_flip($aCountries));
			if($aPreparedData['ext_6'] === null) {
				throw new ImportRowException(\L10N::t('Land nicht gefunden!', \Ext_Thebing_Accommodation_Gui2::L10N_PATH).' ('.\L10N::t('Mögliche Werte', \Ext_Thebing_Accommodation_Gui2::L10N_PATH).': '.implode(', ', $aCountries).')');
			}
		}

		// Agency category
		if(!empty($aItem[13])) {

			$sSQL = "
					SELECT
						*
					FROM
						`kolumbus_agency_categories`
					WHERE
						`name` LIKE :name AND
						`active` = 1
					";
			$aSQL = array(
				'name' => $aItem[13]
			);
			$aCategory = \DB::getQueryRow($sSQL, $aSQL);

			if(empty($aCategory)) {
				$oCategory = new \Ext_Thebing_Agency_Category();
				$oCategory->name = $aItem[13];
				$oCategory->active = 1;
				$oCategory->client_id = \Ext_Thebing_Client::getClientId();
				$oCategory->save();

				$aCategory['id'] = $oCategory->id;
			}

			$aPreparedData['ext_39'] = $aCategory['id'];

		}

	}

	protected function getCheckItemFields(array $aPreparedData) {
		return [
			'type' => $aPreparedData['type'],
			'ext_1' => $aPreparedData['ext_1']
		];
	}
	
	protected function saveComment(int $iEntityId, string $sComment) {

		if(!empty($sComment)) {

			// Alte Kommentare löschen
			/*if($this->aSettings['delete_existing']) {
				$sSql = "
					DELETE FROM
						`ts_companies_comments`
					WHERE
						`agency_id` = :agency_id
					";
				$aSql = array('agency_id'=>(int)$iEntityId);
				\DB::executePreparedQuery($sSql, $aSql);
			}*/

			$aData = array(
				'created'=>date('YmdHis'),
				'active'=>1,
				'company_id'=>$iEntityId,
				'title'=>'Import information',
				'text'=>$sComment
			);
			\DB::insertData('ts_companies_comments', $aData);
			
		}
		
	}

	public function getExportRowFieldValue(\WDBasic $entity, array $field, $additionalWorksheet=null) {

		switch ($field['target']) {
			case 'category':
				return $entity->getCategory()->name;
			case 'ext_6':
				if (empty($entity->ext_6)) {
					return '';
				} else {
					return \DB::table('data_countries')
						->where('cn_iso_2', $entity->ext_6)
						->pluck('cn_short_' . \System::getInterfaceLanguage())
						->first();
				}
			case 'comments':
				// Skippen
				return '';
			case 'groups':
				$groupIds = $entity->groups;

				$groupNames = [];
				foreach ($groupIds as $groupId) {
					$groupNames[] = \Ext_Thebing_Agency_Group::query()
						->where('id', $groupId)
						->pluck('name')
						->first();
				}

				return implode(' | ', $groupNames);
			case 'commission_valid_from':
			case 'commission_name':
			case 'payment_valid_from':
			case 'payment_name':
			case 'cancellation_valid_from':
			case 'cancellation_name':

				list($type, $fieldName) = explode('_', $field['target'], 2);
				
				$getCurrentCategoryMethodName = 'getCurrent'.ucfirst($type).'Validity';
				$currentValidity = $entity->$getCurrentCategoryMethodName();

				if (!empty($currentValidity) && $currentValidity->exist()) {
					if ($fieldName == 'valid_from') {
						return $currentValidity->valid_from;
					} else {
						return $currentValidity->getItem()?->getName();
					}
				}

				return '';
			case 'salary':
				$salaryData = $entity->getSalary(date('Y-m-d'));

				if(!empty($salaryData)) {

					$salary = \Ext_Thebing_Accommodation_Cost_Category::getInstance($salaryData['costcategory_id']);

					return $salary->name;
				}
				return '';
			case 'subject_id':
			case 'activity_id':
			case 'company_contact_id':
				$type = substr($field['target'], 0, strpos($field['target'], '_id'));

				$joinedObject = $entity->getJoinedObject($type);
				if ($joinedObject->id != 0) {
					if ($field['target'] == 'company_contact_id') {
						return $joinedObject->lastname.', '.$joinedObject->firstname;
					} else {
						return $joinedObject->title;
					}
				} else {
					return '';
				}
			case 'ext_33':
				return \DB::table('data_languages')
					->where('iso_639_1', $entity->ext_33)
					->pluck('name_'.\System::getInterfaceLanguage())
					->first();
			case 'follow_up':
				if ($entity->follow_up == '0000-00-00') {
					return '';
				} else {
					return $entity->follow_up;
				}
		}

		return parent::getExportRowFieldValue($entity, $field, $additionalWorksheet);
	}

	public function getExportEntities($additionalWorksheet=null): \Illuminate\Support\Collection {
		return match ($additionalWorksheet) {
			'Mitarbeiter' => \Ext_Thebing_Agency_Contact::query()->get(),
			'Kommentare' => \TsCompany\Entity\Comment::query()->get(),
			default => $this->sEntity::query()->get(),
		};
	}

	public function prepareExportRowField(&$value, \WDBasic $entity, array $field, $additionalWorksheet = null)
	{
		$target = $field['target'];
		if (
			$field['special'] != null &&
			str_contains($field['special'], 'date_object') &&
			$value == ''
		) {
			// Nicht als DateTime formatieren in parent
			return;
		} else if (
			$target == 'ext_23' ||
			$target == 'ext_26' ||
			$target == 'invoice'
		) {
			$field['additional'] = array_flip($field['additional']);
			if ($target == 'invoice') {
				$field['special'] = 'array';
			}
		}

		parent::prepareExportRowField($value, $entity, $field, $additionalWorksheet);
	}

}
