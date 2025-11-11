<?php

namespace Ts\Service\Import;

use Tc\Exception\Import\ImportRowException;
use Tc\Service\Import\ErrorPointer;

class Accommodation extends AbstractImport {
	
	protected $sEntity = 'Ext_Thebing_Accommodation';
	
	public $provideExport = true;
	
	public function getFields() {
		
		$aSchools = \Ext_Thebing_Client::getSchoolList(true);

		/**
		 * Mapping
		 */
		$aFields = [];
		$aFields[] = ['field'=> 'Eindeutige Unterkunft-ID', 'unique_id' => 'id'];
		$aFields[] = ['field'=> 'Name', 'target' => 'ext_33'];
		$aFields[] = ['field'=> 'Nummer', 'target' => 'number'];
		$aFields[] = ['field'=> 'Schulen (kommagetrennt)', 'target' => 'schools', 'special'=>'array_split', 'additional'=> array_flip($aSchools)];
		$aFields[] = ['field'=> 'Kategorien (kommagetrennt)', 'target' => 'accommodation_categories'];
		$aFields[] = ['field'=> 'Standardkategorie', 'target' => 'default_category_id'];
		
		$aFields[] = ['field'=> 'Verpflegungen', 'target' => 'meals'];
		$aFields[] = ['field'=> 'Abrechnungskategorie', 'target' => 'billing_terms'];
		$aFields[] = ['field'=> 'Kostenkategorie', 'target' => 'salary'];
		
		$aFields[] = ['field'=> 'Adresse', 'target' => 'ext_63'];
		$aFields[] = ['field'=> 'Adresszusatz', 'target' => 'address_addon'];
		$aFields[] = ['field'=> 'PLZ', 'target' => 'ext_64'];
		$aFields[] = ['field'=> 'Stadt', 'target' => 'ext_65'];
		$aFields[] = ['field'=> 'Bundesland', 'target' => 'ext_99'];
		$aFields[] = ['field'=> 'Land', 'target' => 'ext_66'];
		$aFields[] = ['field'=> 'Anrede', 'target' => 'ext_105', 'special'=>'gender'];
		$aFields[] = ['field'=> 'Vorname', 'target' => 'ext_103'];
		$aFields[] = ['field'=> 'Nachname', 'target' => 'ext_104'];
		$aFields[] = ['field'=> 'Telefon', 'target' => 'ext_67'];
		$aFields[] = ['field'=> 'Telefon 2', 'target' => 'ext_76'];
		$aFields[] = ['field'=> 'Fax', 'target' => 'ext_101'];
		$aFields[] = ['field'=> 'Handy', 'target' => 'ext_77'];
		$aFields[] = ['field'=> 'Skype', 'target' => 'ext_78'];
		$aFields[] = ['field'=> 'E-Mail', 'target' => 'email'];
		$aFields[] = ['field'=> 'Kommentar', 'target' => 'ext_34'];
		$aFields[] = ['field'=> 'Biete Anreise an', 'target' => 'transfer_arrival', 'special'=>'yes_no'];
		$aFields[] = ['field'=> 'Biete Abreise an', 'target' => 'transfer_departure', 'special'=>'yes_no'];
		
		$aLanguages = \Ext_Thebing_Data::getSystemLanguages();

		foreach($aLanguages as $sLang => $sLanguage) {
			$aFields[] = ['field'=> 'Familienbeschreibung', 'language'=>$sLang, 'target' => 'family_description_'.$sLang, 'special'=>'nl2br'];
		}

		foreach($aLanguages as $sLang => $sLanguage) {
			$aFields[] = ['field'=> 'Wegbeschreibung', 'language'=>$sLang, 'target' => 'way_description_'.$sLang, 'special'=>'nl2br'];
		}
		
		$aFields[] = ['field'=> 'Kontoinhaber', 'target' => 'ext_68'];
		$aFields[] = ['field'=> 'Kontonummer', 'target' => 'ext_70'];
		$aFields[] = ['field'=> 'Bankleitzahl', 'target' => 'ext_71'];
		$aFields[] = ['field'=> 'Bank', 'target' => 'ext_69'];
		$aFields[] = ['field'=> 'Adresse der Bank', 'target' => 'ext_72'];
		$aFields[] = ['field'=> 'IBAN', 'target' => 'bank_account_iban'];
		$aFields[] = ['field'=> 'BIC', 'target' => 'bank_account_bic'];
		
		$oMatching = new \Ext_Thebing_Matching();
		$aHostfamilyCriteria = $oMatching->getCriteria('hostfamily', false);
		$aOtherCriteria = $oMatching->getCriteria('other', false);
		
		$aMergedCriteria = array_merge_recursive($aHostfamilyCriteria, $aOtherCriteria);
		
		foreach($aMergedCriteria as $sType=>$aCriteria) {
			foreach($aCriteria as $oCriteria) {
				
				$aFields[$oCriteria->getAccommodationField()] = ['field'=> $oCriteria->getLabel(), 'target' => $oCriteria->getAccommodationField()];
				
				if(
					$oCriteria->getAccommodationType() === 'select' &&
					!empty($oCriteria->getOptions())
				) {
					$aFields[$oCriteria->getAccommodationField()]['special'] = 'array_trim';
					$aFields[$oCriteria->getAccommodationField()]['additional'] = array_flip($oCriteria->getOptions());
				} elseif($oCriteria->getAccommodationType() === 'input') {
					// Nix weiter, einfach nur Text
				} else {
					$aFields[$oCriteria->getAccommodationField()]['special'] = 'yes_no';
				}
				
			}
		}

		$aFields = array_values($aFields);
		
		return $aFields;
	}
	
	public function getAdditionalWorksheets(): ?array {
		
		$aWorksheets = [
			'Räume' => [
				0 => ['field' => 'Eindeutige Unterkunft-ID', 'unique_id' => 'accommodation_id'],
				1 => ['field' => 'Name', 'target'=>'name'],
				2 => ['field' => 'Raumart', 'target' => 'type_id'],
				3 => ['field' => 'Einzelbett', 'target' => 'single_beds', 'special' => 'int'],
				4 => ['field' => 'Doppelbett', 'target' => 'double_beds', 'special' => 'int'],
				5 => ['field' => 'Frauen', 'target' => 'female', 'special' => 'yes_no'],
				6 => ['field' => 'Männer', 'target' => 'male', 'special' => 'yes_no'],
				7 => ['field' => 'Paare', 'target' => 'couples', 'special' => 'yes_no'],
				8 => ['field' => 'Kommentar', 'target' => 'comment']
			],
			'Mitglieder' => [
				0 => ['field' => 'Eindeutige Unterkunft-ID', 'unique_id' => 'accommodation_id'],
				1 => ['field' => 'Vorname', 'target'=>'firstname'],
				2 => ['field' => 'Nachname', 'target'=>'lastname'],
				3 => ['field' => 'Geschlecht', 'target'=>'gender', 'special'=>'gender'],
				4 => ['field' => 'Geburtstag', 'target'=>'birthday', 'special'=>'date_object', 'additional' => $this->sExcelDateFormat]
			],
			'Kommentare' => [
				0 => ['field' => 'Eindeutige Unterkunft-ID', 'unique_id' => 'acc_id'],
				1 => ['field' => 'Besucher', 'target'=>'visitor'],
				2 => ['field' => 'Datum', 'target'=>'date', 'special'=>'date_object', 'additional' => $this->sExcelDateFormat],
				3 => ['field' => 'Titel', 'target'=>'title'],
				4 => ['field' => 'Betreff', 'target'=>'subject_id'],
				5 => ['field' => 'Aktivität', 'target'=>'activity_id'],
				6 => ['field' => 'Beschreibung', 'target'=>'text']
			]
		];
	
		return $aWorksheets;
	}
	
	protected function getBackupTables() {
		
		$aTables = [
			'tc_flex_sections_fields_values',
			'ts_accommodations_numbers'
		];
	
		return $aTables;
	}
		
	protected function processItem(array &$aItem, int $iItem, array $aAdditionalWorksheetData=null) {

		try {

			$sReport = 'update';
			
			$aData = [];
			\Ext_Thebing_Import::processItems($this->aFields, $aItem, $aData);

			if(in_array(null, $aData['schools'])) {
				throw new ImportRowException(\L10N::t('Schule nicht gefunden!', \Ext_Thebing_Accommodation_Gui2::L10N_PATH).' ('.\L10N::t('Mögliche Werte', \Ext_Thebing_Accommodation_Gui2::L10N_PATH).': '.implode(', ', array_flip($this->aFields[3]['additional'])).')');
			}
			
			$aCategories = \Ext_Thebing_Accommodation_Category::getListForSchools($aData['schools']);

			$aData['accommodation_categories'] = \Ext_Thebing_Import::processSpecial(['array_split'], $aData['accommodation_categories'], array_flip($aCategories));
			if(in_array(null, $aData['accommodation_categories'])) {
				throw new \Exception(\L10N::t('Kategorie nicht gefunden!', \Ext_Thebing_Accommodation_Gui2::L10N_PATH).' ('.\L10N::t('Mögliche Werte', \Ext_Thebing_Accommodation_Gui2::L10N_PATH).': '.implode(', ', $aCategories).')');
			}

			$aData['default_category_id'] = \Ext_Thebing_Import::processSpecial(['array_trim'], $aData['default_category_id'], array_flip($aCategories));
			if($aData['default_category_id'] === null) {
				throw new \Exception(\L10N::t('Standardkategorie nicht gefunden!', \Ext_Thebing_Accommodation_Gui2::L10N_PATH).' ('.\L10N::t('Mögliche Werte', \Ext_Thebing_Accommodation_Gui2::L10N_PATH).': '.implode(', ', $aCategories).')');
			}
			
			if(!in_array($aData['default_category_id'], $aData['accommodation_categories'])) {
				throw new \Exception(\L10N::t('Standardkategorie muss eine der Kategorien sein!', \Ext_Thebing_Accommodation_Gui2::L10N_PATH));
			}
			
			$aMeals = \Ext_Thebing_Accommodation_Meal::getListForSchools($aData['schools']);
			
			$aData['meals'] = \Ext_Thebing_Import::processSpecial(['array_split'], $aData['meals'], array_flip($aMeals));
			if(in_array(null, $aData['meals'])) {
				throw new \Exception(\L10N::t('Verpflegung nicht gefunden!', \Ext_Thebing_Accommodation_Gui2::L10N_PATH).' ('.\L10N::t('Mögliche Werte', \Ext_Thebing_Accommodation_Gui2::L10N_PATH).': '.implode(', ', $aMeals).')');
			}
			
			$aWorksheets = $this->getAdditionalWorksheets();
			
			// Gibt es die Unterkunft schon?
			$oAccommodation = \Ext_Thebing_Accommodation::getRepository()->findOneBy(['ext_33'=>$aData['ext_33']]);
			
			// Wenn Unterkunft schon vorhanden und nicht aktualisiert werden soll
			if(
				$oAccommodation !== null &&
				!$this->aSettings['update_existing']
			) {
				return;
			}
			
			if($oAccommodation === null) {
				$sReport = 'insert';
				$oAccommodation = new \Ext_Thebing_Accommodation;
			}

			/*
			 * Nummer
			 */

			if(!empty($aData['number'])) {
				$bNumberIsPossible = \Ext_TS_Numberrange_Accommodation::checkPossibility($oAccommodation, $aData['number']);

				if (!$bNumberIsPossible) {
					throw new \Exception(sprintf('Duplicate number "%s"', $aData['number']));
				}

				$oNumberrange = \Ext_TS_Numberrange_Accommodation::getObject($oAccommodation);
				$iNumberrangeId = ($oNumberrange) ? $oNumberrange->getId() : 0;

				$oAccommodation->numbers = [[
					'number' => $aData['number'],
					'numberrange_id' => $iNumberrangeId
				]];
			}

			/*
			 * Abrechnungskategorie und Kostenkategorie
			 * Schule wird hier erstmal nicht berücksichtigt
			 */

			if(!empty($aData['billing_terms'])) {
				$oPaymentCategory = \Ts\Entity\AccommodationProvider\Payment\Category::getRepository()->findOneBy(['name'=>$aData['billing_terms']]);
				if($oPaymentCategory === null) {
					throw new \Exception(\L10N::t('Abrechnungskategorie nicht gefunden!', \Ext_Thebing_Accommodation_Gui2::L10N_PATH));
				}
				$oPaymentCategoryValidity = $oAccommodation->getJoinedObjectChildByValueOrNew('billing_terms', 'valid_from', date('Y-m-d'));
				$oPaymentCategoryValidity->category_id = $oPaymentCategory->id;
			}
			
			if(!empty($aData['salary'])) {
				$aCostCategories = \Ext_Thebing_Marketing_Costcategories::getAccommodationCategories(true, $aData['accommodation_categories'], $aData['schools']);
				$iCostCategoryId = array_search($aData['salary'], $aCostCategories);
				if(empty($iCostCategoryId)) {
					throw new \Exception(\L10N::t('Kostenkategorie nicht gefunden!', \Ext_Thebing_Accommodation_Gui2::L10N_PATH).' ('.\L10N::t('Mögliche Werte', \Ext_Thebing_Accommodation_Gui2::L10N_PATH).': '.implode(', ', $aCostCategories).')');
				}
				$oCostCategoryValidity = $oAccommodation->getJoinedObjectChildByValueOrNew('salary', 'valid_from', date('Y-m-d'));
				$oCostCategoryValidity->costcategory_id = $iCostCategoryId;
			}

			unset($aData['billing_terms']);
			unset($aData['salary']);
			unset($aData['number']);

			foreach($aData as $sField=>$mValue) {
				$oAccommodation->$sField = $mValue;
			}
			
			// Räume			
			if(!empty($aAdditionalWorksheetData['Räume'])) {

				$roomNameIndex = [];
				foreach($aAdditionalWorksheetData['Räume'] as $iRowIndex => $aRoom) {
					
					$aRoomData = [];
					\Ext_Thebing_Import::processItems($aWorksheets['Räume'], $aRoom, $aRoomData);

					$aRoomtypes = \Ext_Thebing_Accommodation_Roomtype::getListForSchools($aData['schools']);

					$aRoomData['type_id'] = \Ext_Thebing_Import::processSpecial(['array_trim'], $aRoomData['type_id'], array_flip($aRoomtypes));
					if($aRoomData['type_id'] === null) {
						throw (new ImportRowException(\L10N::t('Raumart nicht gefunden!', \Ext_Thebing_Accommodation_Gui2::L10N_PATH).' ('.\L10N::t('Mögliche Werte', \Ext_Thebing_Accommodation_Gui2::L10N_PATH).': '.implode(', ', $aRoomtypes).')'))
							->pointer($this->getWorksheetTitle('Räume'), $iRowIndex);
					}

					// Wenn Name schon vorkommt
					if(isset($roomNameIndex[$aRoomData['name']])) {
						
						$roomNameIndex[$aRoomData['name']]++;
						
						$aRoomData['name'] .= ' ('.$roomNameIndex[$aRoomData['name']].')';
						
					} else {	
						$roomNameIndex[$aRoomData['name']] = 0;
					}
					
					// Gibt es den Raum schon?
					$oRoom = $oAccommodation->getJoinedObjectChildByValueOrNew('rooms', 'name', $aRoomData['name']);

					foreach($aRoomData as $sField=>$mValue) {
						$oRoom->$sField = $mValue;
					}
										
				}
				
			}
			
			// Mitglieder
			if(!empty($aAdditionalWorksheetData['Mitglieder'])) {

				foreach($aAdditionalWorksheetData['Mitglieder'] as $iRowIndex => $aMember) {

					$aMemberData = [];
					\Ext_Thebing_Import::processItems($aWorksheets['Mitglieder'], $aMember, $aMemberData);

					$sName = $aMemberData['lastname'].', '.$aMemberData['firstname'];

					// Gibt es den Raum schon?
					$oMember = $oAccommodation->getJoinTableObjectsByValue('members', 'name', $sName);

					if($oMember === null) {
						$oMember = $oAccommodation->getJoinTableObject('members');
					}

					foreach($aMemberData as $sField=>$mValue) {
						$oMember->$sField = $mValue;
					}
					
				}
			}

			// Kommentare
			if(!empty($aAdditionalWorksheetData['Kommentare'])) {

				$oSubject = \Ext_Thebing_Marketing_Subject::getInstance();
				$aSubjects = $oSubject->getList(0, true);

				$oActivity = \Ext_Thebing_Marketing_Activity::getInstance();
				$aActivities = $oActivity->getList(0,true);

				foreach($aAdditionalWorksheetData['Kommentare'] as $iRowIndex => $aComment) {

					$aVisitData = [];
					\Ext_Thebing_Import::processItems($aWorksheets['Kommentare'], $aComment, $aVisitData);

					$aVisitData['subject_id'] = \Ext_Thebing_Import::processSpecial(['array_trim'], $aVisitData['subject_id'], array_flip($aSubjects));
					if($aVisitData['subject_id'] === null) {
						throw (new ImportRowException(\L10N::t('Betreff nicht gefunden!', \Ext_Thebing_Accommodation_Gui2::L10N_PATH).' ('.\L10N::t('Mögliche Werte', \Ext_Thebing_Accommodation_Gui2::L10N_PATH).': '.implode(', ', $aSubjects).')'))
							->pointer($this->getWorksheetTitle('Kommentare'), $iRowIndex);
					}

					$aVisitData['activity_id'] = \Ext_Thebing_Import::processSpecial(['array_trim'], $aVisitData['activity_id'], array_flip($aActivities));
					if($aVisitData['activity_id'] === null) {
						throw (new ImportRowException(\L10N::t('Kontaktart nicht gefunden!', \Ext_Thebing_Accommodation_Gui2::L10N_PATH).' ('.\L10N::t('Mögliche Werte', \Ext_Thebing_Accommodation_Gui2::L10N_PATH).': '.implode(', ', $aActivities).')'))
							->pointer($this->getWorksheetTitle('Kommentare'), $iRowIndex);
					}

					if(empty($aVisitData['text'])) {
						throw (new ImportRowException(\L10N::t('Beschreibung darf nicht leer sein!', \Ext_Thebing_Accommodation_Gui2::L10N_PATH)))
							->pointer($this->getWorksheetTitle('Kommentare'), $iRowIndex);
					}
					
					$aCheckValues = [
						'date' => $aVisitData['date'],
						'title' => $aVisitData['title'],
						'text' => $aVisitData['text'],
						'visitor' => $aVisitData['visitor'],
					];
					$oVisit = $oAccommodation->getJoinedObjectChildByValueOrNew('visits', $aCheckValues);

					foreach($aVisitData as $sField=>$mValue) {
						$oVisit->$sField = $mValue;
					}

				}

			}

			$oAccommodation->save();
			
			$this->aReport[$sReport]++;
			
			return $oAccommodation->id;
			
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
		
	public function getExportRowFieldValue(\WDBasic $entity, array $field, $additionalWorksheet=null) {
				
		if($field['target'] == 'billing_terms') {
			
			$oCategoryRepo = \Ts\Entity\AccommodationProvider\Payment\Category::getRepository();

			$oPaymentCategory = $oCategoryRepo->findByProvider($entity);
			
			if($oPaymentCategory) {
				return $oPaymentCategory->name;
			}
			return '';
		}
		
		if($field['target'] == 'salary') {
			
			$salaryData = $entity->getSalary(date('Y-m-d'));

			if(!empty($salaryData)) {
				
				$salary = \Ext_Thebing_Accommodation_Cost_Category::getInstance($salaryData['costcategory_id']);

				return $salary->name;
			}
			return '';
		}

		return parent::getExportRowFieldValue($entity, $field, $additionalWorksheet);
	}
	
	public function prepareExportRowField(&$value, \WDBasic $entity, array $field, $additionalWorksheet=null) {
		
		if($field['target'] == 'accommodation_categories') {

			$aCategories = \Ext_Thebing_Accommodation_Category::getListForSchools($entity->schools);
			$value = array_intersect_key($aCategories, array_flip($value??[]));
			$value = implode(', ', $value);
			
		} elseif($field['target'] == 'default_category_id') {

			$aCategories = \Ext_Thebing_Accommodation_Category::getListForSchools($entity->schools);
			
			$value = $aCategories[$value] ?? '';
						
		} elseif($field['target'] == 'meals') {

			$aMeals = \Ext_Thebing_Accommodation_Meal::getListForSchools($entity->schools);
			$value = array_intersect_key($aMeals, array_flip($value??[]));
			$value = implode(', ', $value);

		} elseif($field['target'] == 'subject_id') {
			
			$oSubject = \Ext_Thebing_Marketing_Subject::getInstance();
			$aSubjects = $oSubject->getList(0, true);
			
			$value = $aSubjects[$value] ?? '';
			
		} elseif($field['target'] == 'activity_id') {

			$oActivity = \Ext_Thebing_Marketing_Activity::getInstance();
			$aActivities = $oActivity->getList(0,true);
			
			$value = $aActivities[$value] ?? '';
			
		} else {		
			parent::prepareExportRowField($value, $entity, $field, $additionalWorksheet);
		}

	}
	
	public function getExportEntities($additionalWorksheet=null) {
	
		switch($additionalWorksheet) {
			case 'Räume':
				$entities = \Ext_Thebing_Accommodation_Room::getRepository()->getAllActive();
				break;
			case 'Mitglieder':
				$entities = \TsAccommodation\Entity\Member::getRepository()->getAllActive();
				break;
			case 'Kommentare':
				$entities = \Ext_Thebing_Accommodation_Visit::getRepository()->getAllActive();
				break;
			default:

				$entities = $this->sEntity::query()
					->where(function($query) {
					$query->whereRaw('valid_until >= CURDATE()')
						->orWhere('valid_until', '=', '0000-00-00');
					})
					->get();
				break;
		}	
		
		return $entities;
	}
	
}
