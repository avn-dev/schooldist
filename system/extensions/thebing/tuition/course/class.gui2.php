<?php

class Ext_Thebing_Tuition_Course_Gui2 extends Ext_Thebing_Gui2_Data {

	use \Tc\Traits\Gui2\Import;
	
	const L10N_PATH = 'Thebing » Tuition » Courses';
	
	// Übersetzungen
	public function getTranslations($sL10NDescription){

		$aData = parent::getTranslations($sL10NDescription);

		$aData['tuition_courses_combination_switch_off'] = L10N::t('Soll dieser Kombinationskurs wirklich aufgelöst werden?', $sL10NDescription);
		$aData['tuition_courses_combination_switch_on'] = L10N::t('Soll dieser Kurs wirklich ein Kombinationskurs sein?', $sL10NDescription);
	
		return $aData;
	}
	
	public function getCalculationOptions() {
		return [
			'week' => $this->t('Woche'),
			'month' => $this->t('Monat'),
			'fixed' => $this->t('Einmalig')
		];
	}
	
	public function getTypeOptions(): array {

		$types = [
			Ext_Thebing_Tuition_Course::TYPE_PER_WEEK => $this->t('Kurs mit festen wöchtenlichen/monatlichen Lektionen'),
			Ext_Thebing_Tuition_Course::TYPE_PER_UNIT => $this->t('Kurs mit flexiblen Lektionen'),
			Ext_Thebing_Tuition_Course::TYPE_EXAMINATION => $this->t('Prüfung / Probeunterricht'),
			Ext_Thebing_Tuition_Course::TYPE_COMBINATION => $this->t('Kombinationskurs'),
			Ext_Thebing_Tuition_Course::TYPE_EMPLOYMENT => $this->t('Anstellung'),
		];

		#if(Ext_Thebing_Access::hasRight('thebing_tuition_resources_courses_programs')) {
			$types[Ext_Thebing_Tuition_Course::TYPE_PROGRAM] = $this->t('Programm');
		#}

		return $types;
	}

	public static function getUkQuarterlyReportCourseTypes($bShort=false, $bWithEmpty=true) {

		$aOptions =  [
			'general_english' => 'General English',
			'business_professional' => 'Business & Professional English',
			'english_plus' => 'English Plus',
			'eap' => 'English for academic purposes',
			'esp' => 'English for specific purposes',
			'1to1' => 'One-to-One',
			'teacher_development' => 'Teacher Development (QUIC)',
			'summer_winter_camps' => 'Summer/Winter Camps (QUIC)'
		];

		if($bShort) {
			$aOptions['business_professional'] = 'Bus & Prof';
			$aOptions['eap'] = 'EAP';
			$aOptions['esp'] = 'ESP';
		}
		
		if($bWithEmpty) {
			$aOptions = Ext_Thebing_Util::addEmptyItem($aOptions, '', '');
		}

		return $aOptions;

	}

	public function getAvaibilityOptions($bWithEmpty = true) {

		$aOptions =  array(
			Ext_Thebing_Tuition_Course::AVAILABILITY_ALWAYS => $this->t('immer verfügbar (Starttag der Schule)'),
			Ext_Thebing_Tuition_Course::AVAILABILITY_ALWAYS_EACH_DAY => $this->t('immer verfügbar (jeder Wochentag)'),
			Ext_Thebing_Tuition_Course::AVAILABILITY_STARTDATES => $this->t('begrenzt verfügbar'),
			Ext_Thebing_Tuition_Course::AVAILABILITY_NEVER => $this->t('nicht verfügbar'),
		);

//		if($bWithEmpty) {
//			$aOptions = Ext_Thebing_Util::addEmptyItem($aOptions, '', Ext_Thebing_Tuition_Course::AVAILABILITY_UNDEFINED);
//		}

		return $aOptions;

	}

	public function getStartdatesGui() {

		$oSchool = Ext_Thebing_School::getSchoolFromSession();

		$oStartdatesGui = new Ext_Thebing_Gui2(md5('thebing_tuition_resources_courses_startdates'), \TsTuition\Gui2\Data\Course\Startdate::class);
		$oStartdatesGui->query_id_column		= 'id';
		$oStartdatesGui->query_id_alias		= '';
		$oStartdatesGui->foreign_key			= 'course_id';
		$oStartdatesGui->foreign_key_alias	= '';
		$oStartdatesGui->parent_hash			= $this->_oGui->hash;
		$oStartdatesGui->parent_primary_key	= 'id';
		$oStartdatesGui->load_admin_header	= false;
		$oStartdatesGui->multiple_selection	= false;
		$oStartdatesGui->class_js = 'Availability';

		$oStartdatesGui->setWDBasic(\Ext_Thebing_Tuition_Course_Startdate::class);
		//$oStartdatesGui->setTableData('limit', 1000000);
		$oStartdatesGui->setTableData('orderby', array('start_date'=>'ASC'));

		$oStartdatesGui->gui_description 	= $this->_oGui->gui_description;

		// Dialog
		$oDialog = $oStartdatesGui->createDialog($this->t('Kurstermin editieren'), $this->t('Kurstermin anlegen'));
		$oDialog->width = 900;
		$oDialog->height = 650;

		// Buttons
		$oBar			= $oStartdatesGui->createBar();
		$oBar->width	= '100%';
		/*$oLabelGroup	= $oBar->createLabelGroup($this->t('Aktionen'));
		$oBar->setElement($oLabelGroup);*/
		$oIcon			= $oBar->createNewIcon($this->t('Neuer Eintrag'), $oDialog, $this->t('Neuer Eintrag'));
		$oBar->setElement($oIcon);
		$oIcon			= $oBar->createEditIcon($this->t('Editieren'), $oDialog, $this->t('Editieren'));
		$oBar->setElement($oIcon);
		$oIcon			= $oBar->createDeleteIcon($this->t('Löschen'), $this->t('Löschen'));
		$oBar->setElement($oIcon);
		$oStartdatesGui->setBar($oBar);

		// Listen Optionen
		$oColumn				= $oStartdatesGui->createColumn();
		$oColumn->db_column		= 'type';
		$oColumn->db_alias		= '';
		$oColumn->title			= $oStartdatesGui->t('Typ');
		$oColumn->width			= Ext_Thebing_Util::getTableColumnWidth('name');
		$oColumn->format		= new Ext_Gui2_View_Format_Selection(
			[
				\Ext_Thebing_Tuition_Course_Startdate::TYPE_START_DATE => $this->t('Startdatum'),
				\Ext_Thebing_Tuition_Course_Startdate::TYPE_NOT_AVAILABLE => $this->t('Nicht verfügbar')
			]
		);
		$oStartdatesGui->setColumn($oColumn);

		$oColumn				= $oStartdatesGui->createColumn();
		$oColumn->db_column		= 'start_date';
		$oColumn->db_alias		= '';
		$oColumn->title			= $oStartdatesGui->t('Startdatum');
		$oColumn->width			= Ext_Thebing_Util::getTableColumnWidth('date');
		$oColumn->width_resize	= true;
		$oColumn->format		= new Ext_Thebing_Gui2_Format_Date_Withday();
		$oStartdatesGui->setColumn($oColumn);

		$oColumn				= $oStartdatesGui->createColumn();
		$oColumn->db_column		= 'period';
		$oColumn->db_alias		= '';
		$oColumn->title			= $oStartdatesGui->t('Wiederholung');
		$oColumn->width			= Ext_Thebing_Util::getTableColumnWidth('id');
		$oColumn->format		= new Ext_Thebing_Gui2_Format_Int();
		$oColumn->small = true;
		$oStartdatesGui->setColumn($oColumn);

		$oColumn				= $oStartdatesGui->createColumn();
		$oColumn->db_column		= 'last_start_date';
		$oColumn->db_alias		= '';
		$oColumn->title			= $oStartdatesGui->t('Letztes Startdatum');
		$oColumn->width			= Ext_Thebing_Util::getTableColumnWidth('date');
		$oColumn->format		= new Ext_Thebing_Gui2_Format_Date();
		$oColumn->small = true;
		$oStartdatesGui->setColumn($oColumn);

		$oColumn				= $oStartdatesGui->createColumn();
		$oColumn->db_column		= 'end_date';
		$oColumn->db_alias		= '';
		$oColumn->title			= $oStartdatesGui->t('Letztes Enddatum');
		$oColumn->width			= Ext_Thebing_Util::getTableColumnWidth('date');
		$oColumn->format		= new Ext_Thebing_Gui2_Format_Date();
		$oColumn->small = true;
		$oStartdatesGui->setColumn($oColumn);

		$oColumn				= $oStartdatesGui->createColumn();
		$oColumn->db_column		= 'minimum_duration';
		$oColumn->db_alias		= '';
		$oColumn->title			= $oStartdatesGui->t('Minimale Wochen');
		$oColumn->width			= Ext_Thebing_Util::getTableColumnWidth('id');
		$oColumn->format		= new Ext_Thebing_Gui2_Format_Int();
		$oColumn->small = true;
		$oStartdatesGui->setColumn($oColumn);

		$oColumn				= $oStartdatesGui->createColumn();
		$oColumn->db_column		= 'maximum_duration';
		$oColumn->db_alias		= '';
		$oColumn->title			= $oStartdatesGui->t('Maximale Wochen');
		$oColumn->width			= Ext_Thebing_Util::getTableColumnWidth('id');
		$oColumn->format		= new Ext_Thebing_Gui2_Format_Int();
		$oColumn->small = true;
		$oStartdatesGui->setColumn($oColumn);

		$oColumn				= $oStartdatesGui->createColumn();
		$oColumn->db_column		= 'fix_duration';
		$oColumn->db_alias		= '';
		$oColumn->title			= $oStartdatesGui->t('Fixe Dauer');
		$oColumn->width			= Ext_Thebing_Util::getTableColumnWidth('id');
		$oColumn->format		= new Ext_Thebing_Gui2_Format_Int();
		$oColumn->small = true;
		$oStartdatesGui->setColumn($oColumn);

		$oStartdatesGui->addDefaultColumns();

		return $oStartdatesGui;
	}

	public function getProgramGui() {
		$factory = new Ext_Gui2_Factory('TsTuition_course_programs');
		return $factory->createGui('', $this->_oGui);
	}

	protected function _saveNewSort($aIds){

		foreach((array)$aIds as $iId){
			$this->_getWDBasicObject($iId);
			$this->oWDBasic->bSaveBySort = true;
		}
		
		parent::_saveNewSort($aIds);
		
		foreach((array)$aIds as $iId){
			$this->_getWDBasicObject($iId);
			$this->oWDBasic->bSaveBySort = false;
		}
		
	}
	
	protected function _getErrorMessage($sError, $sField, $sLabel = '', $sAction = null, $sAdditional = null) {

		switch($sError) {
			case 'JOURNEY_COURSES_FOUND':
				return $this->t('Es sind noch Buchungen vorhanden. Bitte überprüfen Sie die Gültigkeit des Kurses.');
			case 'CLASSES_FOUND':
				return $this->t('Es sind noch Klassen zugewiesen. Bitte überprüfen Sie die Gültigkeit des Kurses.');
			case 'COMBINATION_COURSE_USED':
				return $this->t('Die Einstellungen des Kombinationskurses können nicht verändert werden, da der Kurs bereits verwendet wird.');
			case 'ALLOCATED_TO_PARENT_COURSE':
				return $this->t('Der Kurs ist einem Kombinationskurs oder Programm zugewiesen.');
			case 'TUITION_ALLOCATIONS_FOUND':
				return $this->t('Die Einstellungen für Lektionen können nicht verändert werden, da der Kurs bereits in der Klassenplanung zugewiesen wurde.');
			default:
				return parent::_getErrorMessage($sError, $sField, $sLabel, $sAction, $sAdditional);
		}

	}

	protected function getImportService(): \Ts\Service\Import\AbstractImport {
			
		$importService = new \Ts\Service\Import\Course();
		$importService->setGui2Data($this);
		
		return $importService;
	}

	protected function getImportDialogId() {
		return 'COURSE_IMPORT_';
	}

	protected function addSettingFields(\Ext_Gui2_Dialog $oDialog) {

		$oRow = $oDialog->createRow($this->t('Vorhandene Einträge aktualisieren (werden anhand des Namens und der Schule erkannt)'), 'checkbox', ['db_column'=>'settings', 'db_alias'=>'update_existing']);
		$oDialog->setElement($oRow);

		$oRow = $oDialog->createRow($this->t('Vorhandene Starttermine leeren'), 'checkbox', ['db_column'=>'settings', 'db_alias'=>'delete_existing']);
		$oDialog->setElement($oRow);

		$oRow = $oDialog->createRow($this->t('Fehler überspringen'), 'checkbox', ['db_column'=>'settings', 'db_alias'=>'skip_errors']);
		$oDialog->setElement($oRow);
		
	}
	
	static public function getWhere(\Ext_Gui2 $oGui){
		
		$oSchool			= Ext_Thebing_School::getSchoolFromSession();
		$iSchoolId			= (int)$oSchool->id;

		return ['ktc.school_id' => (int)$iSchoolId];
	}
	
	
	static public function getOrderby() {
		
		$oSchool			= Ext_Thebing_School::getSchoolFromSession();
		$sInterfaceLanguage = $oSchool->getInterfaceLanguage();
		
		return ['ktc.name_'.$sInterfaceLanguage => 'ASC'];
	}
	
	static public function manipulateSearchFilter(\Ext_Gui2 $oGui) {
		
		$oSchool			= Ext_Thebing_School::getSchoolFromSession();
		$sInterfaceLanguage = $oSchool->getInterfaceLanguage();
		
		return [
			'column' => [
				'id',
				'name_short',
				'name_'.$sInterfaceLanguage
			]
		];
		
	}
	
	static public function getDialog(\Ext_Gui2 $oGui) {
		
		$oSchool			= Ext_Thebing_School::getSchoolFromSession();
		$sInterfaceLanguage = $oSchool->getInterfaceLanguage();
		$aTranslationLanguages	= Ext_Thebing_Util::getTranslationLanguages();
		$aCourseCategories	= $oSchool->getCourseCategoriesList('select');
		$aLevelGroups = Ext_Thebing_Tuition_LevelGroup::getSelectOptions();
		$oData				= $oGui->getDataObject(); /** @var Ext_Thebing_Tuition_Course_Gui2 $oData */
		$aCourseTypes = $oData->getTypeOptions();
		$aCourseUnitList	= $oSchool->getCourseUnitList(true);
		$aLevels			= $oSchool->getLevelList(true, $sInterfaceLanguage);
		$aAvaibility		= $oData->getAvaibilityOptions();
		
		$sTitleEdit = L10N::t("Kurs \"{name}\" editieren", $oGui->gui_description);
		$sTitleEdit = str_replace('{name}', '{name_'.$sInterfaceLanguage.'}', $sTitleEdit);

		$oDialog = $oGui->createDialog($sTitleEdit, L10N::t('Neuen Kurs anlegen', $oGui->gui_description));
		$oDialog->save_as_new_button = true;
		$oDialog->save_bar_options = true;
		$oDialog->save_bar_default_option = 'open';

		$oTab = $oDialog->createTab(L10N::t('Daten', $oGui->gui_description));
		$oTab->aOptions['section'] = 'tuition_courses_general';
		$oTab->aOptions['task'] = 'data';

		if(\TcExternalApps\Service\AppService::hasApp(\TsCanvas\Handler\ExternalApp::APP_NAME)) {
			$oTab->setElement($oDialog->createRow(L10N::t("Canvas-ID", $oGui->gui_description), 'input', array(
				'db_alias'  => 'ktc',
				'db_column' => 'canvas_id'
			)));
		}

		$oTab->setElement($oDialog->createI18NRow($oGui->t('Bezeichnung'), ['required'=>true, 'db_alias'=>'ktc', 'db_column_prefix' => 'name_'], $aTranslationLanguages));

		$oTab->setElement($oDialog->createRow(L10N::t("Kürzel", $oGui->gui_description), 'input', array(
			'db_alias'  => 'ktc',
			'db_column' => 'name_short',
			'required'  => 1
		)));
		$oTab->setElement($oDialog->createRow(L10N::t('Kategorie', $oGui->gui_description), 'select', array(
			'db_alias' => 'ktc',
			'db_column'=>'category_id',
			'select_options' => $aCourseCategories,
			'required' => true,
		)));
		$oTab->setElement($oDialog->createRow(L10N::t('Kurssprache', $oGui->gui_description), 'select', array(
			'db_alias' => 'ktc',
			'db_column' => 'course_languages',
			'select_options' => $aLevelGroups,
			'required' => true,
			'multiple' => 5,
			'jquery_multiple' => true,
			'searchable' => true,
		)));

		$oTab->setElement($oDialog->createRow(L10N::t("Unterschiedliche Preise pro Sprache", $oGui->gui_description), 'checkbox', array(
			'db_alias' => 'ktc',
			'db_column'=>'different_price_per_language',
			'required' => 0,
		)));

		$oTab->setElement($oDialog->createRow(L10N::t('Kursart', $oGui->gui_description), 'select', array(
			'db_alias' => 'ktc',
			'db_column' => 'per_unit',
			'events' => [
				[
					'function' => 'togglePerWeekCourseUnits',
					'event' => 'change'
				]
			],
			'select_options' => $aCourseTypes,
			'child_visibility' => [
				[
					'class' => 'lessons_list_row',
					'on_values' => [
						\Ext_Thebing_Tuition_Course::TYPE_PER_UNIT,
						\Ext_Thebing_Tuition_Course::TYPE_PER_WEEK,
						\Ext_Thebing_Tuition_Course::TYPE_EXAMINATION
					]
				],
				[
					'id' => 'cancelled_lessons_container',
					'on_values' => [
						\Ext_Thebing_Tuition_Course::TYPE_PER_UNIT,
						\Ext_Thebing_Tuition_Course::TYPE_PER_WEEK,
						\Ext_Thebing_Tuition_Course::TYPE_EXAMINATION
					]
				],
				[
					'class' => 'schoolholiday-settings',
					'on_values' => [
						Ext_Thebing_Tuition_Course::TYPE_PER_WEEK,
						Ext_Thebing_Tuition_Course::TYPE_PER_UNIT,
						Ext_Thebing_Tuition_Course::TYPE_EXAMINATION,
					]
				]
			]
		)));

		$oTab->setElement($oDialog->createRow(L10N::t('Vorbereitungskurse', $oGui->gui_description), 'select', [
			'db_alias' => '',
			'db_column' => 'preparation_courses',
			'selection' => new TsTuition\Gui2\Selection\Courses(),
			'dependency_visibility' => [
				'db_alias' => 'ktc',
				'db_column' => 'per_unit',
				'on_values' => [Ext_Thebing_Tuition_Course::TYPE_EXAMINATION]
			],
			'multiple' => 5,
			'jquery_multiple' => true,
			'searchable' => true,
		]));

		/*$oTab->setElement($oDialog->createRow(L10N::t("Kombinationskurs", $oGui->gui_description), 'checkbox', array(
			'db_alias' => 'ktc',
			'db_column'=>'combination',
			'required' => 0,
			'class' => 'txt week_fields week_fields2',
			'events' => array(
				array(
					'function'=>'confirmSetCombination',
					'event'=>'change'
				)
			)
		)));*/

		$oTab->setElement($oDialog->createRow(L10N::t("Kombinierte Kurse", $oGui->gui_description), 'select', array(
			'db_alias' => '',
			'db_column' => 'combined_courses',
			'selection' => new TsTuition\Gui2\Selection\Courses(true),
			'multiple' => 5,
			'jquery_multiple' => 1,
			'searchable' => true,
			'style' => 'height: 105px;',
			'required' => 0,
			'dependency_visibility' => [
				'db_alias' => 'ktc',
				'db_column' => 'per_unit',
				'on_values' => [Ext_Thebing_Tuition_Course::TYPE_COMBINATION]
			],
		)));

		$oTab->setElement($oDialog->createRow(L10N::t("Nicht separat buchbar", $oGui->gui_description), 'checkbox', array(
			'db_alias' => 'ktc',
			'db_column'=>'only_for_combination_courses',
			'required' => 0,
			'dependency_visibility' => [
				'db_alias' => 'ktc',
				'db_column' => 'per_unit',
				'on_values' => [
					Ext_Thebing_Tuition_Course::TYPE_PER_WEEK,
					Ext_Thebing_Tuition_Course::TYPE_PER_UNIT,
					Ext_Thebing_Tuition_Course::TYPE_EXAMINATION,
					Ext_Thebing_Tuition_Course::TYPE_EMPLOYMENT,
				]
			]
		)));

		$oTab->setElement($oDialog->createRow(L10N::t('Zeitgleiches Zuweisen erlauben', $oGui->gui_description), 'checkbox', array(
			'db_alias' => 'ktc',
			'db_column'=>'allow_parallel_tuition_allocations',
			'required' => 0,
			'dependency_visibility' => [
				'db_alias' => 'ktc',
				'db_column' => 'per_unit',
				'on_values' => [
					Ext_Thebing_Tuition_Course::TYPE_EXAMINATION
				]
			]
		)));

		$oTab->setElement($oDialog->createMultiRow($oGui->t('Lektionen'), [
			'db_alias' => 'ktc',
			'row_class' => 'lessons_list_row',
			'items' => [
				[
					'input' => 'select',
					'db_column' => 'lessons_list',
					'multiple' => true,
					'required' => true,
					'class' => 'tagsinput',
					'style' => 'width: 80%',
					'format' => new Ext_Thebing_Gui2_Format_Float(),
				],
				[
					'db_column' => 'lessons_unit',
					'input' => 'select',
					'class' => 'lessons_unit txt auto_width',
					'select_options' => collect(\TsTuition\Enums\LessonsUnit::cases())
						->mapWithKeys(fn ($case) => [$case->value => $case->getLabelText($oGui->getLanguageObject())]),
				],
			]
		]));

		$oTab->setElement($oDialog->createRow($oGui->t('Lektionen nicht individuell anpassbar'), 'checkbox', [
			'db_alias' => 'ktc',
			'db_column' => 'lessons_fix',
			'class' => 'unit_fields',
		]));

		$oTab->setElement($oDialog->createRow(L10N::t("Unterrichtsdauer", $oGui->gui_description), 'input', array(
			'db_alias' => 'ktc',
			'db_column'=>'lesson_duration',
			'required' => 1,
			'format'=>new Ext_Thebing_Gui2_Format_Float(),
		)));

		$oTab->setElement($oDialog->createRow(L10N::t('Preisberechnung', $oGui->gui_description), 'select', array(
			'db_column' => 'price_calculation',
			'select_options' => $oData->getCalculationOptions(),
		//	'dependency_visibility' => [
		//		'db_alias' => 'ktc',
		//		'db_column' => 'per_unit',
		//		'on_values' => ['0']
		//	],
		)));

		$aPaymentConditions = \Ext_TS_Payment_Condition::getSelectOptions();

		$oTab->setElement($oDialog->createRow(L10N::t('Abweichende Preise pro Zahlungsbedingung aktivieren',
			$oGui->gui_description),
			'checkbox',
			[
				'db_column' => 'show_prices_per_payment_conditon_select',
			]
		));

		$oTab->setElement($oDialog->createRow(L10N::t('Abweichende Preise pro Zahlungsbedingung', $oGui->gui_description), 'select', array(
			'db_column' => 'prices_per_payment_condition',
			'select_options' => $aPaymentConditions,
			'multiple'=>5,
			'jquery_multiple'=>1,
			'dependency_visibility' => [
				'db_column' => 'show_prices_per_payment_conditon_select',
				'on_values' => ['1']
			],
		)));
		$oTab->setElement($oDialog->createRow(L10N::t("Wochen", $oGui->gui_description), 'select', array(
			'db_alias' => 'ktc',
			'db_column'=>'weeks',
			'select_options' => $oSchool->getWeekList(true),
			'multiple'=>5,
			'jquery_multiple'=>1,
			'style'=>'height: 105px;',
			'class' => 'txt week_fields week_fields2',
			'required'	=> 1,
		)));
		$oTab->setElement($oDialog->createRow(L10N::t("Einheiten", $oGui->gui_description), 'select', array(
			'db_alias' => 'ktc',
			'db_column'=>'units',
			'select_options' => $aCourseUnitList,
			'multiple'=>5,
			'jquery_multiple'=>1,
			'style'=>'height: 105px;',
			'class' => 'txt unit_fields',
			'required'	=> 1,
		)));

		$oTab->setElement($oDialog->createRow(L10N::t('Unterrichtsform', $oGui->gui_description), 'select', [
			'db_alias' => 'ktc',
			'db_column' => 'online',
			'select_options' => self::getOnlineOptions(),
			'dependency_visibility' => [
				'db_alias' => 'ktc',
				'db_column' => 'per_unit',
				'on_values' => [
					Ext_Thebing_Tuition_Course::TYPE_PER_WEEK,
					Ext_Thebing_Tuition_Course::TYPE_PER_UNIT,
					Ext_Thebing_Tuition_Course::TYPE_EXAMINATION,
				]
			]
		]));

		$oH3 = $oDialog->create('h4');
		$oH3->setElement($oGui->t('Verfügbarkeit'));
		$oTab->setElement($oH3);
		$oTab->setElement($oDialog->createRow(L10N::t("Startlevel", $oGui->gui_description), 'select', array(
			'db_alias'	=> 'ktc',
			'db_column'	=> 'start_level_id',
			'select_options' => $aLevels
		)));

		$oAvailabilityContainer = $oDialog->create('div');
		$oAvailabilityContainer->class = 'availability-container';

		$oAvailabilityContainer->setElement($oDialog->createRow(L10N::t("Verfügbarkeit", $oGui->gui_description), 'select', array(
			'db_alias'	=> 'ktc',
			'db_column'	=> 'avaibility',
			'select_options' => $aAvaibility,
			'required' => true,
			'default_value' => Ext_Thebing_Tuition_Course::AVAILABILITY_ALWAYS,
			'events' => array(
				array(
						'function'=>'toggleAvailabilityTab',
						'event'=>'change'
				)
			),
		)));

		$aCourseDurationFlags = [Ext_Thebing_Tuition_Course::AVAILABILITY_ALWAYS, Ext_Thebing_Tuition_Course::AVAILABILITY_ALWAYS_EACH_DAY];

		$oAvailabilityContainer->setElement($oDialog->createRow(L10N::t("Minimale Kursdauer", $oGui->gui_description), 'input', array(
			'db_alias'	=> 'ktc',
			'db_column'	=> 'minimum_duration',
			'format'	=> new Ext_Gui2_View_Format_Null(),
			'dependency_visibility' => [
				'db_alias' => 'ktc',
				'db_column' => 'avaibility',
				'on_values' => $aCourseDurationFlags
			]
		)));
		$oAvailabilityContainer->setElement($oDialog->createRow(L10N::t("Maximale Kursdauer", $oGui->gui_description), 'input', array(
			'db_alias'	=> 'ktc',
			'db_column'	=> 'maximum_duration',
			'format'	=> new Ext_Gui2_View_Format_Null(),
			'dependency_visibility' => [
				'db_alias' => 'ktc',
				'db_column' => 'avaibility',
				'on_values' => $aCourseDurationFlags
			]
		)));

		$oAvailabilityContainer->setElement($oDialog->createRow(L10N::t("Fixe Kursdauer", $oGui->gui_description), 'input', array(
			'db_alias'	=> 'ktc',
			'db_column'	=> 'fix_duration',
			'format'	=> new Ext_Gui2_View_Format_Null(),
			'dependency_visibility' => [
				'db_alias' => 'ktc',
				'db_column' => 'avaibility',
				'on_values' => $aCourseDurationFlags
			]
		)));

		$oTab->setElement($oAvailabilityContainer);

		$oTab->setElement($oDialog->createRow(L10N::t("Kann an Feiertagen stattfinden", $oGui->gui_description), 'checkbox', array(
			'db_alias' => 'ktc',
			'db_column'=>'publicholiday',
			'required' => 0,
			'dependency_visibility' => [
				'db_alias' => 'ktc',
				'db_column' => 'per_unit',
				'on_values' => [
					Ext_Thebing_Tuition_Course::TYPE_PER_WEEK,
					Ext_Thebing_Tuition_Course::TYPE_PER_UNIT,
					Ext_Thebing_Tuition_Course::TYPE_EXAMINATION,
					Ext_Thebing_Tuition_Course::TYPE_COMBINATION,
				]
			]
		)));

		$schoolHolidayContainer = $oDialog->create('div');
		$schoolHolidayContainer->class = 'schoolholiday-settings';

		$oTab->setElement($schoolHolidayContainer);

		$oH3 = $oDialog->create('h3');
		$oH3->setElement($oGui->t('Schulferien'));
		$schoolHolidayContainer->setElement($oH3);

		$schoolHolidayContainer->setElement($oDialog->createRow($oGui->t("Kursbuchung"), 'select', array(
			'db_alias' => 'ktc',
			'db_column'=>'schoolholiday',
			'required' => 0,
			'select_options' => [
				1 => $oGui->t('Findet während der Ferien statt'),
				0 => $oGui->t('Findet nicht während der Ferien statt (Kursteile splitten)'),
				2 => $oGui->t('Findet nicht während der Ferien statt (Kursteile nicht splitten)')
			],
//			'dependency_visibility' => [
//				'db_alias' => 'ktc',
//				'db_column' => 'per_unit',
//				'on_values' => [
//					Ext_Thebing_Tuition_Course::TYPE_PER_WEEK,
//					Ext_Thebing_Tuition_Course::TYPE_PER_UNIT,
//					Ext_Thebing_Tuition_Course::TYPE_EXAMINATION,
//					Ext_Thebing_Tuition_Course::TYPE_COMBINATION,
//				]
//			]
		)));

		$schoolHolidayContainer->setElement($oDialog->createRow($oGui->t("Klassenplanung"), 'select', array(
			'db_alias' => 'ktc',
			'db_column'=>'schoolholiday_scheduling',
			'required' => 0,
			'select_options' => [
				0 => $oGui->t('Keine Unterbrechung während Schulferien'),
				1 => $oGui->t('Unterbrechung während Schulferien')
			],
			'dependency_visibility' => [
				'db_alias' => 'ktc',
				'db_column' => 'schoolholiday',
				'on_values' => [
					0, 2
				]
			]
		)));

		$oAutomaticExtensionDiv = $oDialog->create('div');
		$oAutomaticExtensionDiv->id = 'cancelled_lessons_container';

		$oAutomaticExtensionDiv->setElement($oDialog->create('h4')->setElement($oGui->t('Klassenausfall')));

		$oAutomaticExtensionDiv->setElement($oDialog->createRow($oGui->t('Lektionen nachholen'), 'checkbox', [
			'db_alias' => 'ktc',
			'db_column'	=> 'catch_up_on_cancelled_lessons',
		]));
		$oTab->setElement($oAutomaticExtensionDiv);

		// Automatische Kursverlängerung / Kursverträge
		if (\TcExternalApps\Service\AppService::hasApp(\TsTuition\Handler\CourseRenewalApp::APP_NAME)) {

			$oTab->setElement($oDialog->create('h4')->setElement($oGui->t('Automatische Kursverlängerung')));

			$oTab->setElement($oDialog->createRow($oGui->t('Automatische Kursverlängerung'), 'checkbox', [
				'db_column'	=> 'automatic_renewal',
				'child_visibility' => [
					[
						'class' => 'automatic_renewal_fields',
						'on_values' => [1]
					]
				]
			]));

			$oTab->setElement($oDialog->createRow($oGui->t('Verlängerung X Wochen vor Kursende'), 'input', [
				'db_column'	=> 'automatic_renewal_weeks_before',
				'row_class' => 'automatic_renewal_fields'
			]));

			$oTab->setElement($oDialog->createRow($oGui->t('Verlängerungszeitraum'), 'select', [
				'db_column'	=> 'automatic_renewal_duration_type',
				'row_class' => 'automatic_renewal_fields',
				'select_options' => Util::addEmptyItem([
					'original' => $oGui->t('Originallaufzeit'),
					'adjusted' => $oGui->t('Angepasste Laufzeit')
				])
			]));

			$oTab->setElement($oDialog->createRow($oGui->t('Verlängerung um X Wochen'), 'input', [
				'db_column'	=> 'automatic_renewal_duration_weeks',
				'row_class' => 'automatic_renewal_fields',
				'dependency_visibility' => [
					'db_column' => 'automatic_renewal_duration_type',
					'on_values' => ['adjusted']
				]
			]));

//			$oTab->setElement($oDialog->createRow($oGui->t('Preisberechnung'), 'select', [
//				'db_column'	=> 'automatic_renewal_price_calculation',
//				'row_class' => 'automatic_renewal_fields',
//				'select_options' => Util::addEmptyItem([
//					'original_course' => $oGui->t('Basierend auf dem Preis des Originalkurses'),
//					'school' => $oGui->t('Basierend auf den Schuleinstellungen')
//				])
//			]));

		}

		// UK quarterly report Felder nur bei Recht anzeigen
		if(Ext_Thebing_Access::hasRight('thebing_management_reports_standard2')) {

			$aUkQuarterlyCourseTypes = $oGui->getDataObject()->getUkQuarterlyReportCourseTypes(false, true);

			$oTab->setElement($oDialog->create('h4')->setElement($oGui->t('English UK Report / QUIC')));

			$oTab->setElement($oDialog->createRow(L10N::t("Kurstyp", $oGui->gui_description), 'select', array(
				'db_alias'	=> 'ktc',
				'db_column'	=> 'uk_quarterly_course_type',
				'select_options' => $aUkQuarterlyCourseTypes
			)));

			$oTab->setElement($oDialog->createRow($oGui->t('Juniorkurs (QUIC)'), 'checkbox', array(
				'db_alias'	=> 'ktc',
				'db_column'	=> 'uk_quarterly_junior_course',
				'dependency_visibility' => [
					'db_alias' => 'ktc',
					'db_column' => 'uk_quarterly_course_type',
					'on_values' => array_keys(\TsStatistic\Generator\Statistic\Quic::getQuicCourseTypes('both'))
				]
			)));

		}

		$oH3 = $oDialog->create('h4');
		$oH3->setElement($oGui->t('Sonstiges'));
		$oTab->setElement($oH3);

		$oTab->setElement($oDialog->createRow(L10N::t("Maximale Anzahl von Schülern", $oGui->gui_description), 'input', array(
			'db_alias'	=> 'ktc',
			'db_column'	=> 'maximum_students',
			'format'	=> new Ext_Thebing_Gui2_Format_Int()
		)));
		$oTab->setElement($oDialog->createRow(L10N::t("Durchschnittliche Anzahl von Schülern", $oGui->gui_description), 'input', array(
			'db_alias'	=> 'ktc',
			'db_column'	=> 'average_students',
			'format'	=> new Ext_Thebing_Gui2_Format_Int()
		)));
		$oTab->setElement($oDialog->createRow(L10N::t("Minimale Anzahl von Schülern", $oGui->gui_description), 'input', array(
			'db_alias'	=> 'ktc',
			'db_column'	=> 'minimum_students',
			'format'	=> new Ext_Thebing_Gui2_Format_Int()
		)));
		$oTab->setElement($oDialog->createRow(L10N::t("Minimales Alter", $oGui->gui_description), 'input', array(
			'db_alias'	=> 'ktc',
			'db_column'	=> 'minimum_age',
			'format'	=> new Ext_Thebing_Gui2_Format_Int()
		)));
		$oTab->setElement($oDialog->createRow(L10N::t("Durchschnittliches Alter", $oGui->gui_description), 'input', array(
			'db_alias'	=> 'ktc',
			'db_column'	=> 'average_age',
			'format'	=> new Ext_Thebing_Gui2_Format_Int()
		)));
		$oTab->setElement($oDialog->createRow(L10N::t("Maximales Alter", $oGui->gui_description), 'input', array(
			'db_alias'	=> 'ktc',
			'db_column'	=> 'maximum_age',
			'format'	=> new Ext_Thebing_Gui2_Format_Int()
		)));

		$oTab->setElement($oDialog->createRow($oGui->t('Nummernkreis'), 'select', array(
			'db_alias' => 'ktc',
			'db_column' => 'numberrange_id',
			'selection' => new Ext_TC_Numberrange_Gui2_Selection_Numberranges('global'),
			'dependency_visibility' => [
				'db_alias' => 'ktc',
				'db_column' => 'per_unit',
				'on_values' => [2] // Prüfungen
			]
		)));

		if($oSchool->price_calculation == 1) {
			$oTab->setElement($oDialog->createRow(L10N::t("Kurs aus fortlaufender Preisberechnung ausschliessen", $oGui->gui_description), 'checkbox', array(
				'db_alias' => 'ktc',
				'db_column'=>'skip_ongoing_price_calculation'
			)));
		}

		$oDialog->setElement($oTab);

		$oTab = $oDialog->createTab(L10N::t('Verfügbarkeit', $oGui->gui_description));
		$oTab->class = 'availability';
		$oTab->setElement($oData->getStartdatesGui());
		$oDialog->setElement($oTab);

		$oTab = $oDialog->createTab(L10N::t('Programm', $oGui->gui_description));
		$oTab->class = 'program';
		$oTab->setElement($oData->getProgramGui());
		$oDialog->setElement($oTab);

		if(Ext_Thebing_Access::hasRight('thebing_tuition_resources_courses_documents')) {
			$oTab = $oDialog->createTab($oGui->t('Dokumente'));

			$oTab->setElement($oTab->getDialog()->createRow($oGui->t('Vorlagen'), 'select', [
				'db_column' => 'pdf_templates',
				'selection' => new Ext_TS_Gui2_Selection_Service_PdfTemplate('course'),
				'multiple' => 5,
				'jquery_multiple' => true,
				'searchable' => true,
				'style' => 'height: 105px;'
			]));

			$oDialog->setElement($oTab);
		}

		$aAccommodationCombinations = $oSchool->getAccommodationCombinations();

		$oTab = $oDialog->createTab(L10N::t('Frontend', $oGui->gui_description));

		$oTab->setElement($oDialog->createRow(L10N::t("Unterkünfte", $oGui->gui_description), 'select', array(
			'db_alias' => 'ktc',
			'db_column' => 'accommodation_combinations_joined',
			'select_options' => $aAccommodationCombinations,
			'multiple' => 5,
			'jquery_multiple' => 1,
		)));

		$superordinateCourse = new \TsTuition\Entity\SuperordinateCourse;

		$oTab->setElement($oDialog->createRow(L10N::t('Übergeordneter Kurs', $oGui->gui_description), 'select', array(
			'db_alias' => 'ktc',
			'db_column'=>'superordinate_course_id',
			'select_options' => Ext_Thebing_Util::addEmptyItem($superordinateCourse->getArrayListI18N(['name'], true))
		)));

		$oTab->setElement($oDialog->createI18NRow($oGui->t('Name'), ['db_alias' => 'ktc', 'db_column_prefix' => 'frontend_name_'], $aTranslationLanguages));

		$oTab->setElement($oDialog->createI18NRow($oGui->t('Beschreibung'), ['db_alias' => 'ktc', 'db_column_prefix' => 'description_'], $aTranslationLanguages));

		$oTab->setElement($oTab->getDialog()->createRow($oGui->t('Ansprechpartner'), 'select', [
			'db_column' => 'contact_persons',
			'select_options' => Ext_Thebing_User::getArrayByFunction('course_contact'),
			'multiple' => 5,
			'jquery_multiple' => true,
			'searchable' => true
		]));

		$oTab->setElement($oDialog->createRow($oGui->t('Mindestanzahl von Tagen vor Leistungsbeginn'), 'input', [
			'db_alias' => 'ktc',
			'db_column' => 'frontend_min_bookable_days_ahead',
			'format' => new Ext_Gui2_View_Format_Null()
		]));

		$oDialog->setElement($oTab);
		
			return $oDialog;
			}
	
	static public function getFormatPerUnitColumn() {
		
		$oGui = new Ext_Thebing_Gui2(md5('thebing_tuition_resources_courses'), 'Ext_Thebing_Tuition_Course_Gui2');
		$oGui->access = ['thebing_tuition_resources_courses', ''];
		$oData				= $oGui->getDataObject();
		$aCourseTypes = $oData->getTypeOptions();
		
		return $aCourseTypes;
	}
	
	static public function getFormatCourseLanguagesColumn1() {
		
		$aLevelGroups = Ext_Thebing_Tuition_LevelGroup::getSelectOptions();
		
		return $aLevelGroups;
	}
	
	static public function getFormatCourseLanguagesColumn2() {
				
		return ', ';
	}
	
	static public function 	getFormatSuperordinateCourseIdColumn() {
				
		return \TsTuition\Entity\SuperordinateCourse::getInstance()->getArrayListI18N(['name'], true);
	}

	static public function getOnlineOptions() {
		
		$options = [
			0 => \L10N::t('Präsenz', self::L10N_PATH),
			1 => \L10N::t('Online', self::L10N_PATH),
			2 => \L10N::t('Hybrid', self::L10N_PATH)
		];
		
		return $options;
	}
	
}
