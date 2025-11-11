<?php

namespace Ts\Service\Import;

use Tc\Exception\Import\ImportRowException;
use Tc\Service\Import\ErrorPointer;
use TsSponsoring\Service\SponsorNumberrange;

class Teacher extends AbstractImport {
	
	protected $sEntity = \Ext_Thebing_Teacher::class;

	public function getFields() {
		
		$aSchools = \Ext_Thebing_Client::getSchoolList(true);
		$aCountries = \Ext_Thebing_Data::getCountryList(true, true);
		
		$sInterfaceLanguage	= \Ext_Thebing_School::fetchInterfaceLanguage();
		
		/**
		 * Mapping
		 */
		$aFields = [];
		$aFields[] = ['field'=> 'Eindeutige Lehrer-ID'];
		$aFields[] = ['field'=> 'Vorname', 'target' => 'firstname'];
		$aFields[] = ['field'=> 'Nachname', 'target' => 'lastname'];
		$aFields[] = ['field'=> 'E-Mail (einmalig)', 'target' => 'email'];
		$aFields[] = ['field'=> 'Schulen (kommagetrennt)', 'target' => 'schools', 'special'=>'array_split', 'additional'=> array_flip($aSchools), 'mandatory'=>true];
		
		$oCourseCategory = new \Ext_Thebing_Tuition_Course_Category;
		$aCourseCategories = $oCourseCategory->getArrayList(true, 'name_'.$sInterfaceLanguage);//$oSchool->getCourseCategoriesList('select');
		$aLevels = \Ext_Thebing_Tuition_Level::getList('internal', $sInterfaceLanguage);
		$aCourseLanguages = \Ext_Thebing_Tuition_LevelGroup::getSelectOptions();
		
		$aFields[] = ['field'=> 'Qualifikation - Kurskategorien (kommagetrennt)', 'target' => 'course_categories', 'special'=>'array_split', 'additional'=> array_flip($aCourseCategories)];
		$aFields[] = ['field'=> 'Qualifikation - Niveaus (kommagetrennt)', 'target' => 'levels', 'special'=>'array_split', 'additional'=> array_flip($aLevels)];
		$aFields[] = ['field'=> 'Qualifikation - Kurssprachen (kommagetrennt)', 'target' => 'course_languages', 'special'=>'array_split', 'additional'=> array_flip($aCourseLanguages)];
		
		$aFields[] = ['field' => 'Adresse', 'target' => 'street'];
		$aFields[] = ['field' => 'Adresszusatz', 'target' => 'additional_address'];
		$aFields[] = ['field' => 'PLZ', 'target' => 'zip'];
		$aFields[] = ['field' => 'Stadt', 'target' => 'city'];
		$aFields[] = ['field' => 'Bundesland', 'target' => 'state'];
		$aFields[] = ['field' => 'Land', 'target' => 'country_id', 'special'=>'array', 'additional'=> array_flip($aCountries)];
				
		$aFields[] = ['field' => 'Telefon', 'target' => 'phone'];
		$aFields[] = ['field' => 'Telefon (mobil)', 'target' => 'mobile_phone'];
		$aFields[] = ['field' => 'Telefon (geschäftlich)', 'target' => 'phone_business'];
		$aFields[] = ['field' => 'Skype', 'target' => 'skype'];
		$aFields[] = ['field' => 'Sozialversicherungsnummer', 'target' => 'socialsecuritynumber'];
		$aFields[] = ['field' => 'Geschlecht', 'target'=>'gender', 'special'=>'gender'];

		$aFields[] = ['field'=> 'Nationalität (ISO 3166-1 alpha-2, uppercase)', 'target' => 'nationality'];
		$aFields[] = ['field'=> 'Muttersprache (ISO 639-1, lowercase)', 'target' => 'mother_tongue'];
		
		$aFields[] = ['field' => 'Geburtstag', 'target'=>'birthday', 'special'=>'date_object', 'additional' => $this->sExcelDateFormat];
		
		$aFields[] = ['field'=> 'Kontoinhaber', 'target' => 'account_holder'];
		$aFields[] = ['field'=> 'Kontonummer', 'target' => 'account_number'];
		$aFields[] = ['field'=> 'Bankleitzahl', 'target' => 'adress_of_bank'];
		$aFields[] = ['field'=> 'Name der Bank', 'target' => 'name_of_bank'];
		$aFields[] = ['field'=> 'Bankadresse', 'target' => 'bank_address'];
		$aFields[] = ['field'=> 'IBAN', 'target' => 'iban'];
		
		$aFields[] = ['field' => 'Kommentar', 'target' => 'comment', 'special'=>'nl2br'];

		$aFields = array_values($aFields);
		
		return $aFields;
	}
	
	public function getAdditionalWorksheets(): ?array {
		
		$aSchools = \Ext_Thebing_Client::getSchoolList(true);
		
		$aCostcategories = \Ext_Thebing_Marketing_Costcategories::getTeacherCategories(true);
		$aCostcategories['-1'] = \L10N::t('Festgehalt');
			
		$aPeriods = \Ext_Thebing_Teacher_Salary::getPeriods();
		
		$aWorksheets = [
			'Vertragsparameter' => [
				0 => ['field' => 'Eindeutige Lehrer-ID'],
				1 => ['field'=> 'Schule', 'target' => 'school_id', 'special'=>'array', 'additional'=> array_flip($aSchools)],
				2 => ['field' => 'Kostenkategorie', 'target'=>'costcategory_id', 'special'=>'array', 'additional'=> array_flip($aCostcategories)],
				3 => ['field' => 'Lektionen', 'target'=>'lessons'],
				4 => ['field' => 'Lektionen pro', 'target'=>'lessons_period', 'special'=>'array', 'additional' => array_flip($aPeriods)],
				5 => ['field' => 'Gültig ab', 'target'=>'valid_from', 'special'=>'date_object', 'additional' => $this->sExcelDateFormat],
				6 => ['field' => 'Kommentar', 'target'=>'comment']
			]
		];
	
		return $aWorksheets;
	}
	
	protected function getBackupTables() {
		
		$aTables = [
			'ts_teachers',
			'ts_teachers_courselanguages',
			'ts_teachers_to_schools',
			'kolumbus_teacher_levels',
			'kolumbus_teacher_courses',
			'kolumbus_teacher_salary',
			'tc_flex_sections_fields_values'
		];
	
		return $aTables;
	}
		
	protected function processItem(array &$aItem, int $iItem, array $aAdditionalWorksheetData=null) {

		try {

			$sReport = 'update';
			
			$aData = [];
			\Ext_Thebing_Import::processItems($this->aFields, $aItem, $aData);
			
			$this->checkArraySplitFields($aItem, $aData);
			
			$aWorksheets = $this->getAdditionalWorksheets();
			
			// Gibt es den Lehrer schon?
			$oTeacher = \Ext_Thebing_Teacher::getRepository()->findOneBy(['email'=>$aData['email']]);

			// Wenn Unterkunft schon vorhanden und nicht aktualisiert werden soll
			if(
				$oTeacher !== null &&
				!$this->aSettings['update_existing']
			) {
				return;
			}
			
			if($oTeacher === null) {
				$sReport = 'insert';
				$oTeacher = new \Ext_Thebing_Teacher();
			}

			foreach($aData as $sField=>$mValue) {
				$oTeacher->$sField = $mValue;
			}

			// Mitglieder
			if(!empty($aAdditionalWorksheetData['Vertragsparameter'])) {

				// Alte Vertragsparameter löschen
				if($oTeacher->exist() && $this->aSettings['delete_existing']) {
					$aSalaries = $oTeacher->getJoinedObjectChilds('salary');
					foreach($aSalaries as $oSalary) {
						$oTeacher->deleteJoinedObjectChild('salary', $oSalary);
					}
				}

				foreach($aAdditionalWorksheetData['Vertragsparameter'] as $iRowIndex => $aSalary) {

					$aSalaryData = [];
					\Ext_Thebing_Import::processItems($aWorksheets['Vertragsparameter'], $aSalary, $aSalaryData);
					
					$this->checkArraySplitFields($aSalary, $aSalaryData, $aWorksheets['Vertragsparameter']);

					$oSalary = $oTeacher->getJoinedObjectChild('salary');

					try {

						foreach($aSalaryData as $sField=>$mValue) {
							$oSalary->$sField = $mValue;
						}

						$oSalary->validate(true);

					} catch(\Exception $e) {
						throw (new ImportRowException($e->getMessage()))
							->pointer($this->getWorksheetTitle('Vertragsparameter'), $iRowIndex);
					}
				}
			}

			$oTeacher->save();
			
			$this->aReport[$sReport]++;
			
			return $oTeacher->getId();
			
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
