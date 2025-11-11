<?php

namespace Ts\Service\Import;

use Tc\Exception\Import\ImportRowException;
use Tc\Service\Import\ErrorPointer;
use TsSponsoring\Service\SponsorNumberrange;
use TsTuition\Enums\LessonsUnit;

class Course extends AbstractImport {
	
	protected $sEntity = \Ext_Thebing_Tuition_Course::class;
	
	/**
	 * @var \Ext_Thebing_Tuition_Course_Gui2
	 */
	protected $gui2Data;

	protected $schoolOptions = [];
	
	protected $schools = [];

	protected $superordinateCourseMapping = [];

	public function setGui2Data(\Ext_Thebing_Tuition_Course_Gui2 $data) {
		
		$this->gui2Data = $data;
		
		$this->aFields = $this->getFields();
		
	}
	
	public function getFields() {
			
		if($this->gui2Data === null) {
			return [];
		}
		
		$interfaceLanguage	= \Ext_Thebing_School::fetchInterfaceLanguage();
		
		$this->schools = \Ext_Thebing_Client::getSchoolList(true);
		$aCountries = \Ext_Thebing_Data::getCountryList(true, true);
		
		foreach($this->schools as $schoolId=>$schoolName) {

			$school = \Ext_Thebing_School::getInstance($schoolId);
			$this->schoolOptions[$schoolId]['weeks'] = array_flip((array)$school->getWeekList(true));
			$this->schoolOptions[$schoolId]['units'] = array_flip((array)$school->getCourseUnitList(true));
			$this->schoolOptions[$schoolId]['categories'] = array_flip((array)$school->getCourseCategoriesList('select'));
			$this->schoolOptions[$schoolId]['levels'] = array_flip((array)$school->getLevelList(true, $interfaceLanguage));
			
		}
		
		$aTranslationLanguages	= \Ext_Thebing_Util::getTranslationLanguages();

		$courseLanguages = \Ext_Thebing_Tuition_LevelGroup::getSelectOptions();
		
		$superordinateCourse = new \TsTuition\Entity\SuperordinateCourse;
		$this->superordinateCourseMapping = array_flip((array)$superordinateCourse->getArrayListI18N(['name'], true));

		/**
		 * Mapping
		 */
		$aFields = [];
		$aFields[] = ['field' => 'Eindeutige Kurs-ID'];
		// Position nicht verändern!
		$aFields[] = ['field'=> 'Schule', 'target' => 'school_id', 'special'=>'array', 'additional'=>array_flip((array)$this->schools), 'mandatory'=>true];
		foreach($aTranslationLanguages as $aTranslationLanguage) {
			$aFields[] = ['field'=> 'Bezeichnung ('.$aTranslationLanguage['name'].')', 'target' => 'name_'.$aTranslationLanguage['iso'], 'mandatory'=>true];
		}
		foreach($aTranslationLanguages as $aTranslationLanguage) {
			$aFields[] = ['field'=> 'Frontend - Name ('.$aTranslationLanguage['name'].')', 'target' => 'frontend_name_'.$aTranslationLanguage['iso'], 'mandatory'=>true];
		}
		foreach($aTranslationLanguages as $aTranslationLanguage) {
			$aFields[] = ['field'=> 'Frontend - Beschreibung ('.$aTranslationLanguage['name'].')', 'target' => 'description_'.$aTranslationLanguage['iso'], 'mandatory'=>true];
		}
		
		$aFields[] = ['field'=> 'Kürzel', 'target' => 'name_short', 'mandatory'=>true];
		$aFields[] = ['field'=> 'Kategorie', 'target' => 'category_id', 'mandatory'=>true];
		$aFields[] = ['field'=> 'Kurssprachen', 'target' => 'course_languages', 'special'=>'array_split', 'additional'=>array_flip((array)$courseLanguages), 'mandatory'=>true];
		$aFields[] = ['field'=> 'Kursart', 'target' => 'per_unit', 'special'=>'array', 'additional'=>array_flip((array)$this->gui2Data->getTypeOptions()), 'mandatory'=>true];
		$aFields[] = ['field'=> 'Nur für Kombinationskurs nutzbar', 'target' => 'only_for_combination_courses', 'special'=>'yes_no'];
		$aFields[] = ['field'=> 'Anzahl an Lektionen', 'target' => 'lessons_list', 'special' => 'split_trim', 'additional' => ','];
		$aFields[] = ['field'=> 'Wöchentlich / Absolut (0 / 1)', 'target' => 'lessons_unit', 'special'=>'array', 'additional'=>array_map(fn ($case) => $case->value, LessonsUnit::cases())];
		$aFields[] = ['field'=> 'Unterrichtsdauer', 'target' => 'lesson_duration', 'mandatory' => true];
		$aFields[] = ['field'=> 'Preisberechnung', 'target' => 'price_calculation', 'special'=>'array', 'additional'=>array_flip((array)$this->gui2Data->getCalculationOptions())];
		$aFields[] = ['field'=> 'Wochen/Lektionen', 'target' => 'weeks_units'];
		$aFields[] = ['field'=> 'Unterrichtsform', 'target' => 'online', 'special'=>'array', 'additional'=>array_flip((array)\Ext_Thebing_Tuition_Course_Gui2::getOnlineOptions())];
		$aFields[] = ['field'=> 'Startlevel', 'target' => 'start_level_id'];
		$aFields[] = ['field'=> 'Verfügbarkeit', 'target' => 'avaibility', 'special'=>'array', 'additional'=>array_flip((array)$this->gui2Data->getAvaibilityOptions()), 'mandatory' => true];
		$aFields[] = ['field'=> 'Minimale Kursdauer', 'target' => 'minimum_duration'];
		$aFields[] = ['field'=> 'Maximale Kursdauer', 'target' => 'maximum_duration'];
		$aFields[] = ['field'=> 'Fixe Kursdauer', 'target' => 'fix_duration'];
		$aFields[] = ['field'=> 'Kann an Feiertagen stattfinden', 'target' => 'publicholiday', 'special'=>'yes_no'];
		$aFields[] = ['field'=> 'Kann in Schulferien stattfinden', 'target' => 'schoolholiday', 'special'=>'yes_no'];
		$aFields[] = ['field'=> 'UK quarterly report - Kurstyp', 'target' => 'uk_quarterly_course_type', 'special'=>'array', 'additional'=>array_flip((array)$this->gui2Data->getUkQuarterlyReportCourseTypes(false, true))];
		$aFields[] = ['field'=> 'Maximale Anzahl von Schülern', 'target' => 'maximum_students'];
		$aFields[] = ['field'=> 'Durchschnittliche Anzahl von Schülern', 'target' => 'average_students'];
		$aFields[] = ['field'=> 'Minimale Anzahl von Schülern', 'target' => 'minimum_students'];
		$aFields[] = ['field'=> 'Minimales Alter', 'target' => 'minimum_age'];
		$aFields[] = ['field'=> 'Durchschnittliches Alter', 'target' => 'average_age'];
		$aFields[] = ['field'=> 'Maximales Alter', 'target' => 'maximum_age'];
		$aFields[] = ['field'=> 'Übergeordneter Kurs', 'target' => 'superordinate_course_id'];

		$aFields = array_values($aFields);
		
		return $aFields;
	}
	
	public function getAdditionalWorksheets(): ?array {
		
		$aWorksheets = [
			'Starttermine' => [
				0 => ['field' => 'Eindeutige Kurs-ID', 'hint'=>'Nutzt man hier die Fidelo-Kurs-ID, reicht es, im ersten Arbeitsblatt nur die erste Spalte zu füllen.'],
				1 => ['field'=> 'Startdatum', 'target' => 'start_date', 'special'=>'date_object', 'additional' => $this->sExcelDateFormat],
				2 => ['field' => 'Einzelnes Startdatum', 'target'=>'single_date', 'special'=>'yes_no'],
				3 => ['field' => 'Widerholungen', 'target'=>'period'],
				4 => ['field' => 'Letztes Startdatum', 'target'=>'last_start_date', 'special'=>'date_object', 'additional' => $this->sExcelDateFormat],
				5 => ['field' => 'Minimale Kursdauer', 'target'=>'minimum_duration'],
				6 => ['field' => 'Maximale Kursdauer', 'target'=>'maximum_duration'],
				7 => ['field' => 'Fixe Kursdauer', 'target'=>'fix_duration']
			]
		];
	
		return $aWorksheets;
	}
		
	protected function getBackupTables() {
		
		$aTables = [
			'kolumbus_tuition_courses',
			'kolumbus_tuition_courses_accommodation_combinations',
			'kolumbus_tuition_courses_to_units',
			'kolumbus_tuition_courses_to_weeks',
			'kolumbus_course_startdates'
		];
	
		return $aTables;
	}
		
	protected function processItem(array &$aItem, int $iItem, array $aAdditionalWorksheetData=null) {

		try {

			$sReport = 'update';

			$itemFields = $this->aFields;
			
			// Vorab-Durchlauf der Daten, um für den eigentlichen Durchlauf Werte zu ermitteln
			$aCheckData = [];
			\Ext_Thebing_Import::processItems($itemFields, $aItem, $aCheckData);

			// Nur ID übermittelt, andere Spalten leer
			if(empty(array_filter($aCheckData))) {

				$course = \Ext_Thebing_Tuition_Course::getInstance($aItem[0]);

				if(!$course->exist()) {
					$this->aReport['error']++;
					return;
				}
				
			} else {

				// Schul-ID ermitteln, um Kategorien und Wochen zu ermitteln
				$schoolId = (int)$aCheckData['school_id'];
				// Kurstyp ermitteln, um Wochen oder Lektionen einzulesen
				$perUnit = (int)$aCheckData['per_unit'];

				$interfaceLanguage	= \Ext_Thebing_School::fetchInterfaceLanguage();

				foreach($itemFields as $fieldIndex=>&$field) {

					if($field['target'] == 'category_id') {
						$field['special'] = 'array';
						$field['additional'] = $this->schoolOptions[$schoolId]['categories'];
					} elseif($field['target'] == 'weeks_units') {

						$field['special'] = 'array_split';

						if($perUnit === 1) {
							$field['target'] = 'units';
							$field['additional'] = $this->schoolOptions[$schoolId]['units'];	
						} else {
							$field['target'] = 'weeks';					
							$field['additional'] = $this->schoolOptions[$schoolId]['weeks'];	
						}

					} elseif($field['target'] == 'start_level_id') {
						$field['special'] = 'array';
						$field['additional'] = $this->schoolOptions[$schoolId]['levels'];
					} elseif($field['target'] == 'superordinate_course_id') {

						if(!empty($aItem[$fieldIndex])) {
							if(!empty($this->superordinateCourseMapping[$aItem[$fieldIndex]])) {

								$aItem[$fieldIndex] = $this->superordinateCourseMapping[$aItem[$fieldIndex]];

								$superordinateCourse = \TsTuition\Entity\SuperordinateCourse::getInstance($aItem[$fieldIndex]);

							} else {

								$superordinateCourse = new \TsTuition\Entity\SuperordinateCourse;
								$superordinateCourse->setI18NName($aItem[$fieldIndex], $interfaceLanguage, 'name', 'ts_sc_i18n');
								$superordinateCourse->save();

								$aItem[$fieldIndex] = $superordinateCourse->id;

								$this->superordinateCourseMapping[$aItem[$fieldIndex]] = $superordinateCourse->id;

							}
						}

					}

				}

				$aData = [];
				\Ext_Thebing_Import::processItems($itemFields, $aItem, $aData);

				$nullFields = [
					'minimum_duration',
					'maximum_duration',
					'fix_duration',
					// nur 0, 1 oder null für lessons_unit erlaubt
					'lessons_unit'
				];
				foreach($nullFields as $nullField) {
					if($aData[$nullField] === "") {
						$aData[$nullField] = null;
					}
				}

				$this->checkArraySplitFields($aItem, $aData, $itemFields);

				// Gibt es den Kurs schon?
				$course = \Ext_Thebing_Tuition_Course::getRepository()->findOneBy(['school_id' => $aData['school_id'], 'name_short'=>$aData['name_short']]);

				// Wenn Kurs schon vorhanden und nicht aktualisiert werden soll
				if(
					$course !== null &&
					!$this->aSettings['update_existing']
				) {
					return;
				}

				if($course === null) {
					$sReport = 'insert';
					$course = new \Ext_Thebing_Tuition_Course();
				}

				$course->active = 1;

				if(empty($aData['superordinate_course_id'])) {
					unset($aData['superordinate_course_id']);
				}

				foreach($aData as $sField=>$mValue) {
					$course->$sField = $mValue;
				}

			}
			
			$aWorksheets = $this->getAdditionalWorksheets();

			// Starttermine
			if(!empty($aAdditionalWorksheetData['Starttermine'])) {

				// Alte Startdaten löschen
				if($course->exist() && $this->aSettings['delete_existing']) {
					$startDates = $course->getJoinedObjectChilds('start_dates');
					foreach($startDates as $startDate) {
						$course->deleteJoinedObjectChild('start_dates', $startDate);
					}
				}

				foreach($aAdditionalWorksheetData['Starttermine'] as $iRowIndex => $startDateItem) {

					$startDateData = [];
					\Ext_Thebing_Import::processItems($aWorksheets['Starttermine'], $startDateItem, $startDateData);
					
					$this->checkArraySplitFields($startDateItem, $startDateData, $aWorksheets['Starttermine']);

					$startDate = $course->getJoinedObjectChildByValueOrNew('start_dates', 'start_date', $startDateData['start_date']);

					try {

						$startDate->active = 1;
						$startDate->type = 'start_date';

						foreach($startDateData as $sField=>$mValue) {
							$startDate->$sField = $mValue;
						}

						$startDate->validate(true);

					} catch(\Exception $e) {
						throw (new ImportRowException($e->getMessage()))
							->pointer($this->getWorksheetTitle('Starttermine'), $iRowIndex);
					}
				}
			}

			$course->save();

			$this->aReport[$sReport]++;
			
			return $course->getId();
			
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
