<?php

use \Core\Exception\Entity\ValidationException;

class Ext_TC_Flexible_Gui2 extends Ext_TC_Gui2_Data {

	public static function getSectionFilterOptions() {
		$aFlexSections		= Ext_TC_Flexibility::getFlexSections(true);
		asort($aFlexSections);
		return Ext_TC_Util::addEmptyItem($aFlexSections, Ext_TC_L10N::getEmptySelectLabel('please_choose'), -1);
	}

	public static function getWhere(bool $innerGui, \Ext_Gui2 $gui) {
		if(!$innerGui) {
			return ['parent_id' => 0];
		}

		return [];
	}

	public static function getDialog(bool $isInnerGui, \Ext_Gui2 $gui) {

		$dialog = $gui->createDialog($gui->t('Feld "{title}" editieren'), $gui->t('Neues Feld anlegen'));
		$dialog->setDataObject(\Ext_TC_Flexible_Gui2_Dialog_Data::class);
		$dialog->save_as_new_button = true;

		$tabData = $dialog->createTab($gui->t('Daten'));

		$fieldTypes = \Ext_TC_Flexibility::getFlexFieldTypes();
		if($isInnerGui) {
			unset($fieldTypes[\Ext_TC_Flexibility::TYPE_REPEATABLE]);
		}

		$tabData->setElement($dialog->createRow($gui->t('Feldtyp'), 'select', array(
			'db_alias'			=> '',
			'db_column'			=> 'type',
			'required'			=> 0,
			'select_options'	=> $fieldTypes,
			'dependency'		=> array(array('db_alias'=>'', 'db_column' => 'section_id')),
			'selection'			=> \Ext_TC_Factory::getObject('Ext_TC_Gui2_Selection_Flexibility_FieldType'),
		)));

		$tabData->setElement($dialog->createRow($gui->t('Feldname'), 'input', array(
			'db_alias'			=> '',
			'db_column'			=> 'title',
			'required'			=> 1
		)));

		if(!$isInnerGui) {
			$tabData->setElement($dialog->createRow($gui->t('In Liste anzeigen'), 'checkbox', [
				'db_column' => 'visible'
			]));
		}

		$tabData->setElement($dialog->createRow($gui->t('Mehrsprachigkeit'), 'checkbox', array(
			'db_alias'			=> '',
			'db_column'			=> 'i18n',
			'dependency_visibility' => array(
				'db_column' => 'type',
				'db_alias' => '',
				'on_values' => array(
					0, 1, 6
				)
			)
		)));

		$tabData->setElement($dialog->createRow($gui->t('nach übersetzten Werten sortieren'), 'checkbox', array(
			'db_alias'			=> '',
			'db_column'			=> 'i18n_sort',
			'dependency_visibility' => array(
				'db_column' => 'type',
				'db_alias' => '',
				'on_values' => array(
					5,8
				)
			)
		)));

		$tabData->setElement($dialog->createRow($gui->t('Pflichtfeld'), 'checkbox', array(
			'db_alias'			=> '',
			'db_column'			=> 'required'
		)));

		$validateOptions	= \Ext_TC_Util::getValidationOptions();
		asort($validateOptions);
		$validateOptions	= \Ext_TC_Util::addEmptyItem($validateOptions, $gui->t('Keine Überprüfung') );

		$tabData->setElement($dialog->createRow($gui->t('Überprüfen mit'), 'select', array(
			'db_alias'			=> '',
			'db_column'			=> 'validate_by',
			'required'			=> 0,
			'select_options'	=> $validateOptions,
			'dependency'		=> array(array('db_alias'=>'', 'db_column' => 'section_id')),
			'selection'			=> \Ext_TC_Factory::getObject('Ext_TC_Gui2_Selection_Flexibility_Validation'),
		)));

		$tabData->setElement($dialog->createRow($gui->t('Regulärer Ausdruck'), 'input', array(
			'db_alias'			=> '',
			'db_column'			=> 'regex'
		)));

		$tabData->setElement($dialog->createRow($gui->t('max. Zeichenlänge'), 'input', array(
			'db_alias'			=> 'kfsf',
			'db_column'			=> 'max_length',
			'style'				=> 'width: 50px;',
			'dependency_visibility' => [
				'db_column' => 'type',
				'db_alias' => '',
				'on_values' => [
					0, 1
				]
			]
		)));

		$tabData->setElement($dialog->createRow($gui->t('Fehlermeldung'), 'textarea', array(
			'db_alias'			=> '',
			'db_column'			=> 'error',
			'required'			=> 0
		)));

		$div = $dialog->create('div');
		$div->class = 'div_placeholder';

		$div->setElement($dialog->create('h4')->setElement($gui->t('Platzhalter')));

		$div->setElement($dialog->createRow($gui->t('Feldplatzhalter'), 'input', array(
			'db_alias'			=> 'kfsf',
			'db_column'			=> 'placeholder'
		)));

		$div->setElement($dialog->createRow($gui->t('Feldbeschreibung'), 'textarea', array(
			'db_alias'			=> '',
			'db_column'			=> 'description'
		)));

		$tabData->setElement($div);

		if(!$isInnerGui) {
			$div = $dialog->create('div');
			$div->id = 'gui_designer';

			$h3 = $dialog->create('h4');
			$h3->setElement($gui->t('Weitere Einstellungen'));
			$div->setElement($h3);

			$div->setElement($dialog->createRow($gui->t('Verwendung'), 'select', array(
				'db_alias' => '',
				'db_column' => 'usage',
				'required' => 0,
				'dependency' => array(array('db_alias' => '', 'db_column' => 'section_id')),
				'selection' => \Ext_TC_Factory::getObject('Ext_TC_Gui2_Selection_Flexibility_Usage'),
			)));

			$tabData->setElement($div);
		}

		$dialog->setElement($tabData);

		$tabOtions = $dialog->createTab($gui->t('Optionen'));
		$tabOtions->setElement($gui->getDataObject()->getOptionGui($gui, $isInnerGui));
		$dialog->setElement($tabOtions);

		if(!$isInnerGui) {
			$tabChildFields = $dialog->createTab($gui->t('Felder'));
			$tabChildFields->setElement($gui->getDataObject()->getChildFieldsGui($gui));
			$dialog->setElement($tabChildFields);
		}

		return $dialog;
	}

	public function getOptionGui(Ext_Gui2 $parentGui, bool $isInnerGui = false){
		
		$sInterfaceLanguage = Ext_TC_System::getInterfaceLanguage();

		// Korrespondenzsprachen
		$aAllLanguages = Ext_TC_Factory::executeStatic('Ext_TC_Util', 'getTranslationLanguages');

		$sHashValue = 'core_admin_flexibility_options';
		if($isInnerGui) {
			$sHashValue .= '_inner';
		}

		$oInnerGui = $parentGui->createChildGui(md5($sHashValue), 'Ext_TC_Flexible_Option_Gui2_Data');
		$oInnerGui->query_id_column		= 'id';
		$oInnerGui->query_id_alias		= 'kfsfo';
		$oInnerGui->foreign_key			= 'field_id';
		$oInnerGui->foreign_key_alias	= 'kfsfo';
		$oInnerGui->parent_primary_key	= 'id';
		$oInnerGui->load_admin_header	= false;
		$oInnerGui->multiple_selection  = false;
		$oInnerGui->column_sortable		= 0;
		$oInnerGui->row_sortable		= 1;

		if($oInnerGui instanceof Ext_TC_Gui2) {
			$oInnerDefaultColumn = $oInnerGui->getDefaultColumn();
			$oInnerDefaultColumn->setConfig('editor_id', ['db_column' => 'user_id']);
			$oInnerGui->setDefaultColumn($oInnerDefaultColumn); // wird bei getDefaultColumn() on the fly erstellt aber keine Referenz behalten
		}

		$oInnerGui->setWDBasic('Ext_TC_Flexible_Option');
		//$oInnerGui->setTableData('limit', 30);
		//$oInnerGui->setTableData('where', array('kfsfov.lang_id'=>$sInterfaceLanguage));

		// Dialog
		$oDialog					= $oInnerGui->createDialog($oInnerGui->t('Option "{'.$sInterfaceLanguage.'_title}" editieren'), $oInnerGui->t('Neue Option anlegen'));
		$oDialog->height			= 650;

		$oDialog->save_as_new_button	= true;
		$oDialog->save_bar_options		= true;
		$oDialog->save_bar_default_option = 'close';
 
		foreach((array)$aAllLanguages as $sLang => $aLang){
			$oDialog->setElement($oDialog->createRow($oInnerGui->t('Titel') . ' (' . $aLang['iso'] . ')', 'input', array(
					'db_alias'			=> '',
					'db_column'			=> $aLang['iso'].'_title',
					'required'			=> 1
			)));
		}

		$oDialog->setElement(
			$oDialog->createRow(
				$oInnerGui->t('Schlüssel'),
				'input', 
				array(
					'db_alias'			=> '',
					'db_column'			=> 'key'
				)
			)
		);

		// Buttons
		$oBar			= $oInnerGui->createBar();
		$oBar->width	= '100%';
		$oIcon			= $oBar->createNewIcon($oInnerGui->t('Neuer Eintrag'), $oDialog, $oInnerGui->t('Neuer Eintrag'));
		$oBar->setElement($oIcon);
		$oIcon			= $oBar->createEditIcon($oInnerGui->t('Editieren'), $oDialog, $oInnerGui->t('Editieren'));
		$oBar->setElement($oIcon);
		$oIcon			= $oBar->createDeleteIcon($oInnerGui->t('Löschen'), $oInnerGui->t('Löschen'));
		$oBar->setElement($oIcon);
		$oInnerGui->setBar($oBar);

		# START - Leiste 3 #
			$oBar = $oInnerGui->createBar();
			$oBar->width = '100%';
			$oBar->position = 'top';

			$oPagination = $oBar->createPagination(true);
			$oBar ->setElement($oPagination);

			// TODO Recht einbauen oder brauchbar umsetzen, nicht nur irgendwie für Frontend-BOA #11664
			if(Ext_TC_Util::getSystem() === 'agency') {
				$oIcon = $oBar->createIcon(Factory::executeStatic('Util', 'getIcon', ['table_row_insert']), 'confirm');
				$oIcon->active = 1;
				$oIcon->action = 'add_separator';
				$oIcon->confirm_message = $oInnerGui->t('Wollen Sie wirklich einen Trennung einbauen?');
				$oIcon->label = $oInnerGui->t('Trennung einbauen');
				$oBar->setElement($oIcon);
			}

			$oLoading = $oBar->createLoadingIndicator();
			$oBar->setElement($oLoading);

			$oInnerGui->setBar($oBar);
		# ENDE - Leiste 2 #

		$oColumn = $oInnerGui->createColumn();
		$oColumn->db_column = 'id';
		$oColumn->db_alias = 'kfsfo';
		$oColumn->title = $this->t('ID');
		$oColumn->width = Ext_TC_Util::getTableColumnWidth('id');
		//$oColumn->default = false;
		$oInnerGui->setColumn($oColumn);

		$oColumn				= $oInnerGui->createColumn();
		$oColumn->db_column		= 'title';
		$oColumn->db_alias		= 'kfsfov';
		$oColumn->title			= $this->t('Titel');
		$oColumn->width			= Ext_TC_Util::getTableColumnWidth('name');
		$oColumn->width_resize	= true;
		$oInnerGui->setColumn($oColumn);

		$oInnerGui->addDefaultColumns();
		
		return $oInnerGui;
	}

	public function getChildFieldsGui(Ext_Gui2 $parentGui){
		$factory = new \Ext_Gui2_Factory('Tc_flexible_fields');
		// Hash der ChildFields-Gui vor createGui setzen damit überall der korrekte Hash vorhanden ist (z.b. getDialog()).
		// Alternative wäre eine eigene Config-Datei
		$factory->getConfig()->set(['hash'], md5('Tc_flexible_fields_childs'));
		$gui = $factory->createGui('child_fields', $parentGui);
		$gui->parent_primary_key = 'id';
		$gui->foreign_key = 'parent_id';
		$gui->setTableData('where', self::getWhere(true, $gui));
		return $gui;
	}

	/**
	 * @param Ext_Gui2_Dialog $oDialogData
	 * @param array $aSelectedIds
	 * @param string $sAdditional
	 * @return array 
	 */
	public function getEditDialogHTML(&$oDialogData, $aSelectedIds, $sAdditional = false)
	{
		$oTab				= $oDialogData->aElements[0];
		$aTabElementsOrigin	= (array)$oTab->aElements;

		####### Je nach Fall das Verfügbarkeitselement darstellen (Hidden/Select) #######
		$iSectionId			= $this->getSectionId();

		$aNewElements = array();
		
		// Ansonsten Selektfeld setzen
		$aFlexSections		= Ext_TC_Flexibility::getFlexSections(true);
		asort($aFlexSections);

		$aOptions = array(
			'db_alias'			=> '',
			'db_column'			=> 'section_id',
			'required'			=> 1,
			'select_options'	=> \Ext_TC_Util::addEmptyItem($aFlexSections)
		);

		$categoryUsage = Ext_TC_Factory::executeStatic('Ext_TC_Flexibility', 'getCategoryUsage', [$this->_oGui]);
		
		$query = \Ext_TC_Flexible_Section::query();
		$sectionsWithUsage = $query->select('tc_fs.*')->whereIn('tc_fs.category', array_keys($categoryUsage))->pluck('tc_fs.id');

		$aChildVisibility = array(
			array(
				'id' => 'gui_designer',
				'on_values' => $sectionsWithUsage->all()
			)
		);
		
		$aOptions['child_visibility'] = $aChildVisibility;

		if($iSectionId > 0) {
			
			$section = Ext_TC_Flexible_Section::getInstance($iSectionId);

			$query = \Ext_TC_Flexible_Section::query();
			$categorySections = $query->where('category', $section->category)->pluck('title', 'id');

			$categorySections->transform(function($title) {
				return L10N::t($title, Ext_TC_Flexibility::$sL10NDescription);
			});

			if(count($categorySections) > 1) {
				
				$aOptions['select_options'] = $categorySections->toArray();
				
				$oRow = $oDialogData->createRow($this->t('Verfügbarkeit'), 'select', $aOptions);
				
			} else {

				// Wenn Sektion vorhanden, Hidden Feld setzen
				$oRow					= $oDialogData->createRow($this->t('Verfügbarkeit'), 'hidden', array(
					'db_alias'			=> '',
					'db_column'			=> 'section_id',
					'required'			=> 1,
					'child_visibility' => $aChildVisibility
				));

				$oRow->style			= 'display:none;';
				
			}
			
			$aNewElements[]			= $oRow;

		} else {
			
			$oRow = $oDialogData->createRow($this->t('Verfügbarkeit'), 'select', $aOptions);

			$aNewElements[]			= $oRow;

		}

		$aTabElements			= array_merge($aNewElements, $aTabElementsOrigin);

		// GUI-Designer Feld
		//gui_dialog_designer
		
		$oTab->aElements		= $aTabElements;

		$aData = parent::getEditDialogHTML($oDialogData, $aSelectedIds, $sAdditional);

		// Nachdem das Html generiert wurde, die Elemente der Tabs wieder zurücksetzen, da sonst bei jedem öffnen 
		// das zusätzliche HTML-Zeug reingepackt wird, da über den Dialog das Element erstellt wird und in aSaveData reinkommt,
		// funktioniert das ganze so richtig...
		$oTab->aElements		= $aTabElementsOrigin;

		$aData['sections_without_list'] = Factory::executeStatic('Ext_TC_Flexibility', 'getFieldSectionIdsWithoutListView');
		$aData['sections_with_placeholders'] = Factory::executeStatic('Ext_TC_Flexibility', 'getFieldSectionsWithPlaceholders');

		return $aData;
	}

	/**
	 * @inheritdoc
	 */
	protected function saveEditDialogData(array $aSelectedIds, $aSaveData, $bSave = true, $sAction = 'edit', $bPrepareOpenDialog = true) {
		global $_VARS;

		// Warnung gilt primär Elasticsearch-GUIs, da hier die Felder nicht mehr generiert werden
		$oField = $this->_getWDBasicObject($aSelectedIds);
		if(
			$oField->id > 0 &&
			$aSaveData['visible'] &&
			$aSaveData['visible'] != $oField->visible &&
			$_VARS['ignore_errors'] != 1
		) {

			$aTransfer['action'] = 'saveDialogCallback';
			$aTransfer['data']['show_skip_errors_checkbox'] = 1;
			$aTransfer['data']['id'] = $_VARS['dialog_id'];
			$aTransfer['error'] = [[
				'message' => $this->t('Werte von Feldern, die künftig in der Liste angezeigt werden sollen, werden möglicherweise erst nach dem Speichern des Eintrags korrekt angezeigt.'),
				'type' => 'hint'
			]];

			return $aTransfer;

		} else {
			return parent::saveEditDialogData($aSelectedIds, $aSaveData, $bSave, $sAction, $bPrepareOpenDialog);
		}

	}

	/**
	 * Die jetzige Sektion anhand des Filters oder Dialoges ermitteln
	 * 
	 * @return int 
	 */
	public function getSectionId() {
		global $_VARS;
		
		$aSelectedIds		= (array)$_VARS['id'];
		
		$iSectionId			= 0;

		if(!$this->oWDBasic) {
			$this->_getWDBasicObject($aSelectedIds);
		}

		$iSectionIdBasic	= (int)$this->oWDBasic->section_id;; 

		if($iSectionIdBasic > 0) {
			// Wenn edit, dann wurde die Sektion bereits gewählt, Wert aus Entity übernehmen
			$iSectionId = $iSectionIdBasic;
		} elseif(
			isset($_VARS['filter']) &&
			isset($_VARS['filter']['section_filter'])
		) {
			// Wenn Filter gewählt wurde, dann diese als Sektion festlegen
			$iSectionId	= (int)$_VARS['filter']['section_filter'];
		}
		
		return $iSectionId;

	}

	/**
	 * @inheritdoc
	 */
	public function _getErrorMessage($sError, $sField, $sLabel = '', $sAction = null, $sAdditional = null) {

		if ($sError instanceof ValidationException && (string)$sError === 'FIELD_IN_USE') {
			return str_replace('{label}', $sError->getAdditional()['label'], $this->t('Das Feld wird noch im Eintrag "{label}" verwendet.'));
		} elseif($sError === 'TOO_MANY_FIELDS') {
			$sMessage = $this->t('Im ausgewählten Bereich wurden bereits zu viele individuelle Felder angelegt.');
		} elseif($sError === 'TOO_MANY_FIELDS_VISIBLE') {
			$sMessage = $this->t('Im ausgewählten Bereich wurden bereits zu viele Felder für die Anzeige in der Liste eingestellt.');
		} elseif($sError === 'INVALID_REGEX') {
			$sMessage = parent::_getErrorMessage($sError, $sField, $sLabel, $sAction, $sAdditional).'<br>';
			$sMessage .= $this->t('Der Platzhalter muss mit einem Buchstaben beginnen und mindestens 3 Zeichen haben. Es sind nur Kleinbuchstaben, 0-9 und Unterstrich erlaubt.');
		} else {
			$sMessage = parent::_getErrorMessage($sError, $sField, $sLabel, $sAction, $sAdditional);
		}

		return $sMessage;
	}

	static public function getFormatParamsUsageColumn() {

		$aOptions = array(
			'enquiry'	=> L10N::t('Anfrage'),
			'booking'	=> L10N::t('Buchung'), ## , self::TRANSLATION_PATH
			'enquiry_booking'	=> L10N::t('Anfrage und Buchung')
		);

		return $aOptions;
	}

}
