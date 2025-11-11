<?php

namespace TsCompany\Service\Import;

use Core\Helper\BitwiseOperator;
use Tc\Exception\Import\ImportRowException;
use Ts\Service\Import\AbstractImport;
use Tc\Service\Import\ErrorPointer;
use TsCompany\Entity\Industry;
use TsCompany\Service\NumberRange;

class Company extends AbstractImport {
	
	protected $sEntity = \TsCompany\Entity\Company::class;

	public function getFlexibleFields() {

		$aFlexFields = parent::getFlexibleFields();

		$oContact = new \TsCompany\Entity\Contact();
		$aFlexFields['Mitarbeiter'] = $oContact->getFlexibleFields();

		return $aFlexFields;
	}

	public function getFields() {

		/**
		 * Mapping
		 */
		$aFields = [];
		$aFields[0] = ['field'=> 'Eindeutige Firmen-ID'];
		$aFields[1] = ['field'=> 'Name', 'target' => 'ext_1'];
		$aFields[2] = ['field'=> 'Nummer', 'target' => 'number'];
		$aFields[3] = ['field'=> 'Adresse', 'target' => 'ext_3'];
		$aFields[4] = ['field'=> 'Adresszusatz', 'target' => 'ext_35'];
		$aFields[5] = ['field'=> 'PLZ', 'target' => 'ext_4'];
		$aFields[6] = ['field'=> 'Stadt', 'target' => 'ext_5'];
		$aFields[7] = ['field'=> 'Land', 'target' => 'ext_6', 'special' => 'country'];
		$aFields[8] = ['field'=> 'Web', 'target' => 'ext_10'];
		$aFields[9] = ['field'=> 'Gründungsjahr', 'target' => 'founding_year'];
		$aFields[10] = ['field'=> 'Beginn der Zusammenarbeit', 'target' => 'start_cooperation'];
		$aFields[11] = ['field'=> 'Anzahl der Mitarbeiter', 'target' => 'staffs'];
		$aFields[12] = ['field'=> 'Kommentar', 'target' => 'comment'];
		$aFields[13] = ['field'=> 'Branchen (kommasepariert)', 'target' => 'industries'];
		$aFields[14] = [
			'field'=> 'Korrespondenzsprache (z.B. English)',
			'target' => 'ext_33',
			'special' => 'language'
		];

		$aFields[15] = [
			'field'=> 'Abkürzung',
			'target' => 'ext_2'
		];

		$aFields[16] = [
			'field'=> 'Aktiv',
			'target' => 'status', 
			'special' => 'yes_no'
		];

		$aFields[17] = [
			'field'=> 'Bundesland',
			'target' => 'state'
		];

		return $aFields;
	}

	public function getAdditionalWorksheets(): ?array {

		$aWorksheets = [
			'Mitarbeiter' => [
				0 => ['field' => 'Eindeutige Firmen-ID'],
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
				0 => ['field' => 'Eindeutige Firmen-ID'],
				1 => ['field' => 'Titel', 'target'=>'title'],
				2 => ['field' => 'Betreff', 'target'=>'subject_id'],
				3 => ['field' => 'Kontaktart', 'target'=>'activity_id'],
				4 => ['field' => 'Text', 'target'=>'text'],
				5 => ['field' => 'Kontakt (Nachname, Vorname)', 'target'=>'company_contact_id'],
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
				'ts_companies_to_industries',
				// Multi select
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
			BitwiseOperator::add($aData['type'], \TsCompany\Entity\AbstractCompany::TYPE_COMPANY);;

			$aEntityProcess = [];
			foreach($aItem as $iKey => $sValue) {
				$aEntityProcess['field_'.$iKey] = $sValue;
			}

			\Ext_Thebing_Import::processItems($this->aFields, $aEntityProcess, $aData);

			// Nummer herausfiltern
			$sNumber = $aData['number'];
			unset($aData['number']);

			// Alle weitere Spalten als Kommentar eintragen
			$iCountFields = count($this->aFields);
			for($i=$iCountFields; $i < count($aItem); $i++) {
				$this->aFields[$i] = ['field'=>$this->aTitles[$i], 'target' => 'comments'];
			}

			// Leere Einträge überspringen
			if(!empty($aItem[0])) {

				$this->prepareItem($aItem, $aData);

				// Branchen herausfiltern
				$aIndustries = $aData['industries'];
				unset($aData['industries']);

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

				$oCompany = \TsCompany\Entity\Company::getInstance((int)$iEntityId);

				/*
				 * Nummer
				 */

				if(!empty($sNumber)) {
					$bNumberIsPossible = NumberRange::checkPossibility($oCompany, $sNumber);

					if (!$bNumberIsPossible) {
						throw new ImportRowException(sprintf('Duplicate number "%s"', $sNumber));
					}

					$oNumberrange = NumberRange::getObject($oCompany);
					$iNumberrangeId = ($oNumberrange) ? $oNumberrange->getId() : 0;

					\DB::updateJoinData('ts_companies_numbers', ['company_id' => $oCompany->getId()], [[
						'number' => $sNumber,
						'numberrange_id' => $iNumberrangeId
					]]);
				}

				/*
				 * Branchen
				 */

				if(!empty($aIndustries)) {
					$aIndustriesJoinData = [];
					foreach($aIndustries as $iIndustryId) {
						$aIndustriesJoinData[] = ['industry_id' => $iIndustryId];
					}
					\DB::updateJoinData('ts_companies_to_industries', ['company_id' => $oCompany->getId()], $aIndustriesJoinData);
				}

				/**
				 * Additional Worksheets
				 */

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

						$aCommentData['subject_id'] = \Ext_Thebing_Import::processSpecial(['array'], $aCommentData['subject_id'], array_flip($aSubjects));
						if($aCommentData['subject_id'] === null) {
							throw (new ImportRowException(\L10N::t('Betreff nicht gefunden!', \Ext_Thebing_Accommodation_Gui2::L10N_PATH).' ('.\L10N::t('Mögliche Werte', \Ext_Thebing_Accommodation_Gui2::L10N_PATH).': '.implode(', ', $aSubjects).')'))
								->pointer($this->getWorksheetTitle('Kommentare'), $iRowIndex);
						}

						$aCommentData['activity_id'] = \Ext_Thebing_Import::processSpecial(['array'], $aCommentData['activity_id'], array_flip($aActivities));
						if($aCommentData['activity_id'] === null) {
							throw (new ImportRowException(\L10N::t('Kontaktart nicht gefunden!', \Ext_Thebing_Accommodation_Gui2::L10N_PATH).' ('.\L10N::t('Mögliche Werte', \Ext_Thebing_Accommodation_Gui2::L10N_PATH).': '.implode(', ', $aActivities).')'))
								->pointer($this->getWorksheetTitle('Kommentare'), $iRowIndex);
						}

						if(!empty($aCommentData['company_contact_id'])) {

							$aContacts = $oCompany->getContacts(true);

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

		if(!empty($aItem[13])) {
			$aIndustries = Industry::getSelectOptions(true);

			$aPreparedData['industries'] = \Ext_Thebing_Import::processSpecial(['array_split'], $aItem[13], array_flip($aIndustries));

			if(
				$aPreparedData['industries'] === null ||
				in_array(null, $aPreparedData['industries'])
			) {
				throw new ImportRowException(\L10N::t('Branche nicht gefunden!', \Ext_Thebing_Accommodation_Gui2::L10N_PATH).' ('.\L10N::t('Mögliche Werte', \Ext_Thebing_Accommodation_Gui2::L10N_PATH).': '.implode(', ', $aIndustries).')');
			}
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
	
}
