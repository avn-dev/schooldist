<?php

class Ext_TC_Frontend_Template_Gui2_Data extends Ext_TC_Gui2_Data
{

	const TRANSLATION_PATH = 'Thebing Core » Templates » Frontend';

	/**
	 * Gibt die Anwendungsfälle für Frontend zurück
	 * 
	 * @param bool $bWithEmptyItem
	 * @return array 
	 */
	public static function getUsageOptions($bWithEmptyItem=false)
    {

		$aOptions = array(
            'feedback_form'	=> L10N::t('Feedbackformular', self::TRANSLATION_PATH)
		);

		if($bWithEmptyItem) {
			$aOptions = Ext_TC_Util::addEmptyItem($aOptions);
		}

		return $aOptions;

	}

	/**
	 * Anwendungsfälle, bei welchen die ganzen anderen/weiteren Tabs angezeigt werden
	 *
	 * @return array
	 */
	public static function getUsagesWithTabOptions()
    {
		return [];
	}

	/**
	 * Anwendungsfälle mit Default-Templates
	 *
	 * @return array
	 */
	public static function getUsagesWithDefaultTemplates()
    {

		$aDefaultTemplates = [
			'feedback_form' => Util::getDocumentRoot().'storage/tc/templates/frontend/feedback.tpl'
		];

		return $aDefaultTemplates;

	}

	/**
	 * Liefert die GUI im Dialog für den Tab »Felder«
	 * @static
	 * @return Ext_TC_Gui2
	 */
	public function getDialogFieldGui()
    {

		$oGui = $this->_oGui->createChildGui(md5('core_admin_frontend_templates_fields'), 'Ext_TC_Frontend_Template_Field_Gui2_Data');

		$oGui->access = array('core_frontend_templates', '');
		$oGui->class_js	= 'FrontendTemplatesFieldGui';
		$oGui->setWDBasic('Ext_TC_Frontend_Template_Field');
		$oGui->setTableData('where', array('active'=> 1));
		$oGui->setTableData('limit', 100000);
		$oGui->setTableData('orderby', array('tc_ftf.placeholder' => 'ASC'));

		// @ TODO Sortierung einfügen
		#$oGui->setTableData('orderby', array('field' => 'DESC'));
		$oGui->parent_primary_key = 'id';
		$oGui->foreign_key = 'template_id';

		$aAreas = Ext_TC_Frontend_Template_Field::getFieldAreas(true);

		// --- Dialog ---

		$oDialog = $oGui->createDialog($oGui->t('Feld bearbeiten'), $oGui->t('Neues Feld anlegen'));
		$oDialog->save_as_new_button	= true;
		$oDialog->save_bar_options		 = true;
		$oDialog->save_bar_default_option = 'open';
		
		$oDialog->width = 1100;
		
		// Tab: Einstellungen

		$oTab = $oDialog->createTab($oGui->t('Einstellungen'));

		$oTab->setElement($oDialog->createRow($oGui->t('Bereich'), 'select', array(
			'db_column' => 'area',
			'db_alias' => 'tc_ftf',
			'select_options' => $aAreas,
			'required' => true
		)));

		$oTab->setElement($oDialog->createRow($oGui->t('Feld'), 'select', array(
			'db_column' => 'field',
			'db_alias' => 'tc_ftf',
			'selection' => Ext_TC_Factory::getObject('Ext_TC_Frontend_Template_Field_Gui2_Selection_Field'),
			'required' => true,
			'dependency' => array(
				array(
					'db_alias' => 'tc_ftf',
					'db_column' => 'area'
				)
			)
		)));

		$oTab->setElement($oDialog->createRow($oGui->t('Darstellung'), 'select', array(
			'db_column' => 'display',
			'db_alias' => 'tc_ftf',
			'selection' => Ext_TC_Factory::getObject('Ext_TC_Frontend_Template_Field_Gui2_Selection_Display'),
			'required' => true,
			'dependency' => array(
				array(
					'db_alias' => 'tc_ftf',
					'db_column' => 'field'
				)
			),
			'events' => array(
				array(
					'event' => 'change',
					'function' => 'reloadDialogTab',
					'parameter' => 'aDialogData.id, new Array(-1, 1)'
				)
			)
		)));


		$oTab->setElement($oDialog->createRow($oGui->t('Platzhalter'), 'input', array(
			'db_column' => 'placeholder',
			'db_alias' => 'tc_ftf'
		)));

		$oTab->setElement($oDialog->createRow($oGui->t('Label'), 'input', array(
			'db_column' => 'label',
			'db_alias' => 'tc_ftf'
		)));

		$oTab->setElement($oDialog->createRow($oGui->t('Editierbar'), 'checkbox', array(
			'db_column' => 'editable',
			'db_alias' => 'tc_ftf'
		)));

//		$oTab->setElement($oDialog->createRow($oGui->t('Hinweistext'), 'html', array(
//			'db_column' => 'description',
//			'db_alias' => 'tc_ftf',
//			'row_id' => 'description'
//		)));

        $aLanguages = Ext_TC_Factory::executeStatic('Ext_TC_Util', 'getTranslationLanguages');
        
        $oTab->setElement($oDialog->createI18NRow($oGui->t('Hinweistext'), array(
            'db_alias' => 'fields_i18n',
            'db_column'=> 'description',
            'i18n_parent_column' => 'field_id',
            'type' => 'html'
        ), $aLanguages));
        
		$oTab->setElement($oDialog->createRow($oGui->t('Zusätzliche CSS-Klassen'), 'input', array(
			'db_column' => 'field_css_classes',
			'db_alias' => 'tc_ftf',
			'row_id' => 'field_css_classes'
		)));

		$oH3 = $oDialog->create('h4');
		$oH3->setElement($oGui->t('Validierung'));
		
		$oTab->setElement($oH3);
		
		$oTab->setElement($oDialog->createRow($oGui->t('Pflichtfeld'), 'checkbox', array(
			'db_column' => 'mandatory_field',
			'db_alias' => 'tc_ftf',
			'child_visibility' => array(
				array(
					'id' => 'mandatory_field_settings',
					'on_values' => array(
						'1'
					)
				)
			)
		)));
		
		$oRequiredSettings = $oDialog->create('div');
		$oRequiredSettings->id = 'mandatory_field_settings';
		
		$oRequiredSettings->setElement($oDialog->createRow($oGui->t('Pflichtfeld Fehlermeldung'), 'input', array(
			'db_column' => 'mandatory_field_error',
			'db_alias' => 'tc_ftf',
			'row_id' => 'mandatory_field_error'
		)));
		
		$oRequiredJoinedObjectContainer = $oDialog->createJoinedObjectContainer('parent_fields_dependencies', array('min' => 1, 'max' => 5));
		
		$oRequiredJoinedObjectContainer->setElement($oRequiredJoinedObjectContainer->createRow($oGui->t('Elternelement'), 'select', array(
			'db_column' => 'dependency_field_id',
			'db_alias' => 'tc_ftfd',
			'selection' => new Ext_TC_Frontend_Template_Field_Dependency_Gui2_Selection_Field()	
		)));
		
		$oRequiredJoinedObjectContainer->setElement($oRequiredJoinedObjectContainer->createRow($oGui->t('Wert'), 'select', array(
			'db_column' => 'field_values',
			'db_alias' => 'tc_ftfd',
			'selection' => new Ext_TC_Frontend_Template_Field_Dependency_Gui2_Selection_Value(),
			'multiple' => 5, 
			'jquery_multiple' => 1,
			'style' => 'height: 50px;',
			'searchable' => 1,
			'dependency' => array(
				array(
					'db_alias' => 'tc_ftfd',
					'db_column' => 'dependency_field_id'
				)
			),
		)));
		
		$oRequiredSettings->setElement($oRequiredJoinedObjectContainer);
		
		

		
		/*$oRequiredSettings->setElement($oDialog->createRow($oGui->t('Abhängigkeit'), 'input', array(
			'db_column' => 'mandatory_field_parent',
			'db_alias' => 'tc_ftf',
			'row_id' => 'mandatory_field_parent'
		)));*/
		
		$oTab->setElement($oRequiredSettings);
		
		$oDialog->setElement($oTab);

		// Tab: Vorlage

		$oTab = $oDialog->createTab($oGui->t('Vorlage'));

		$oTab->setElement($oDialog->createRow($oGui->t('Vorlage überschreiben'), 'checkbox', array(
			'db_column' => 'overwrite_template',
			'db_alias' => 'tc_ftf'
		)));

		$oTab->setElement($oDialog->createRow($oGui->t('Quelltext'), 'textarea', array(
			'db_column' => 'template',
			'db_alias' => 'tc_ftf',
			'style'	=> 'height: 300px;'
		)));

		$oDialog->setElement($oTab);


		// --- Bars ---

		$oBar = $oGui->createBar();

		$oFilter = $oBar->createFilter();
		$oFilter->db_column = array('label', 'placeholder');
		$oFilter->db_alias = array('tc_ftf');
		$oFilter->db_operator  = 'LIKE';
		$oFilter->id = 'search';
		$oFilter->placeholder = $oGui->t('Suche').'…';
		$oBar->setElement($oFilter);
		
		$aYesNoArray = Ext_TC_Util::getYesNoArray(false);
		
		$oFilter = $oBar->createFilter('select');
		$oFilter->select_options = Ext_TC_Util::addEmptyItem($aYesNoArray, '--'.$oGui->t('Wird benutzt').'--', 'xNullx');
		$oFilter->value	= 'xNullx';
		$oFilter->filter_query = array(
			'no' => "
				`tc_ftf`.`used` = 0
			",
			'yes' => "
				`tc_ftf`.`used` = 1
			",
		);
		$oBar->setElement($oFilter);
		
		$oGui->setBar($oBar);

		$oBar = $oGui->createBar();

		$oIcon = $oBar->createNewIcon($oGui->t('Neuer Eintrag'), $oDialog, $oGui->t('Neuer Eintrag'));
		$oBar->setElement($oIcon);
		$oIcon = $oBar->createEditIcon($oGui->t('Editieren'), $oDialog, $oGui->t('Editieren'));
		$oBar->setElement($oIcon);
		$oIcon = $oBar->createDeleteIcon($oGui->t('Löschen'), $oGui->t('Löschen'));
		$oBar->setElement($oIcon);

		$oSeparator = $oBar->createSeperator();
		$oBar->setElement($oSeparator);

		$oIcon = $oBar->createIcon('fa-plus-square', 'request', $oGui->t('Fehlende Felder anlegen'));
		$oIcon->label = $oGui->t('Fehlende Felder anlegen');
		$oIcon->task = 'request';
		$oIcon->action = 'addMissingFields';
		$oIcon->active = 1;
		$oBar->setElement($oIcon);

		$oLoading = $oBar->createLoadingIndicator();
		$oBar->setElement($oLoading);
		
		$oGui->setBar($oBar);

		// --- Columns ---

		$oColumn = $oGui->createColumn();
		$oColumn->db_column	= 'label';
		$oColumn->title	= $oGui->t('Feld');
		$oColumn->width	= Ext_TC_Util::getTableColumnWidth('name');
		$oColumn->width_resize = true;
		$oColumn->format = Ext_TC_Factory::getObject('Ext_TC_Frontend_Template_Field_Format_Field');
		$oGui->setColumn($oColumn);

		$oColumn = $oGui->createColumn();
		$oColumn->db_column	= 'placeholder';
		$oColumn->db_alias = 'tc_ftf';
		$oColumn->title	= $oGui->t('Platzhalter');
		$oColumn->width	= Ext_TC_Util::getTableColumnWidth('name');
		$oGui->setColumn($oColumn);

		$aTypes = Ext_TC_Frontend_Template_Field_Gui2_Selection_Display::getInputTypes();
		
		$oColumn = $oGui->createColumn();
		$oColumn->db_column	= 'display';
		$oColumn->db_alias = 'tc_ftf';
		$oColumn->title	= $oGui->t('Feldtyp');
		$oColumn->width	= Ext_TC_Util::getTableColumnWidth('name');
		$oColumn->format = new Ext_Gui2_View_Format_Selection($aTypes);
		$oGui->setColumn($oColumn);

		$oColumn = $oGui->createColumn();
		$oColumn->db_column	= 'mandatory_field';
		$oColumn->db_alias = 'tc_ftf';
		$oColumn->title	= $oGui->t('Pflichtfeld');
		$oColumn->width	= Ext_TC_Util::getTableColumnWidth('short_name');
		$oColumn->format = new Ext_TC_Gui2_Format_YesNo();
		$oGui->setColumn($oColumn);

		$oColumn = $oGui->createColumn();
		$oColumn->db_column	= 'editable';
		$oColumn->db_alias = 'tc_ftf';
		$oColumn->title	= $oGui->t('Editierbar');
		$oColumn->width	= Ext_TC_Util::getTableColumnWidth('short_name');
		$oColumn->format = new Ext_TC_Gui2_Format_YesNo();
		$oGui->setColumn($oColumn);

		$oColumn = $oGui->createColumn();
		$oColumn->db_column	= 'used';
		$oColumn->db_alias = 'tc_ftf';
		$oColumn->title	= $oGui->t('Wird benutzt');
		$oColumn->width	= Ext_TC_Util::getTableColumnWidth('short_name');
		$oColumn->style = new Ext_TC_Frontend_Template_Gui2_Style_Used();
		$oColumn->format = new Ext_TC_Gui2_Format_YesNo();
		$oGui->setColumn($oColumn);
		
		$oGui->addDefaultColumns();

		return $oGui;

	}

	/**
	 * @inheritdoc
	 */
	public function prepareOpenDialog($sIconAction, $aSelectedIds, $iTab = false, $sAdditional = false, $bSaveSuccess = true)
    {

		$aData = parent::prepareOpenDialog($sIconAction, $aSelectedIds, $iTab, $sAdditional, $bSaveSuccess);

		$aData['usages_with_tabs'] = Ext_TC_Factory::executeStatic(__CLASS__, 'getUsagesWithTabOptions');
		$aData['default_templates'] = array_keys(Ext_TC_Factory::executeStatic(__CLASS__, 'getUsagesWithDefaultTemplates'));

		return $aData;

	}
	
	
	protected function saveEditDialogData(array $aSelectedIds, $aSaveData, $bSave = true, $sAction = 'edit', $bPrepareOpenDialog = true)
    {
				
		$oFrontendTemplate = $this->getWDBasicObject($aSelectedIds);
		$sCodeBackup = $oFrontendTemplate->code;
		
		$aTransfer = parent::saveEditDialogData($aSelectedIds, $aSaveData, $bSave, $sAction, $bPrepareOpenDialog);
		
		if(
			empty($aTransfer['error'])
		) {
			$oLogger = \Log::getLogger('frontend_templates');
			$oLogger->addInfo(
				'Backup',
				[
					'id' => $oFrontendTemplate->id,
					'content' => $sCodeBackup
				]
			);
		}
		
		return $aTransfer;
	}

	public static function getOrderby()
    {

		return ['name' => 'DESC'];
	}

	public static function getDialog(\Ext_Gui2 $oGui)
    {

		$aUsageOptions = Ext_TC_Factory::executeStatic('Ext_TC_Frontend_Template_Gui2_Data', 'getUsageOptions', array(true));
		$oDialog = $oGui->createDialog($oGui->t('Frontend "{name}" bearbeiten'), $oGui->t('Neues Frontend anlegen'));
		$oDialog->bSmallLabels = true;
		$oDialog->save_as_new_button = true;
		$oData = $oGui->getDataObject();
		/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */

		$oTab = $oDialog->createTab($oGui->t('Vorlage'));

		$oTab->setElement($oDialog->createRow($oGui->t('Name'), 'input', array(
			'db_column'			=> 'name',
			'db_alias'			=> '',
			'required'			=> true
		)));

		$oTab->setElement($oDialog->createRow($oGui->t('Verwendung'), 'select', array(
			'db_column'			=> 'usage',
			'db_alias'			=> '',
			'required'			=> true,
			'select_options'	=> $aUsageOptions
		)));

		$oTab->setElement($oDialog->createRow($oGui->t('Standard-Template benutzen'), 'checkbox', array(
			'db_column' => 'use_default_template',
			'db_alias' => ''
		)));

		$oTab->setElement($oDialog->createNotification(
			$oGui->t('Achtung'),
			$oGui->t('Wenn das Standard-Template nicht verwendet wird, muss das individuelle Template nach einem Update gegebenenfalls angepasst werden!'),
			'hint',
			['row_id' => 'no_default_template_warning', 'row_style' => 'display: none']
		));

		$oTab->setElement($oDialog->createRow($oGui->t('Quelltext'), 'textarea', array(
			'db_column'			=> 'code',
			'db_alias'			=> '',
			'required'			=> true,
			'style'				=> '/*width:700px;*/height:450px;font-family:monospace;'
		)));

		$oDialog->setElement($oTab);

		/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */

		$oTab = $oDialog->createTab($oGui->t('Felder'));
		$oTab->setElement($oData->getDialogFieldGui());
		$oDialog->setElement($oTab);

		/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */

		$oTab = $oDialog->createTab($oGui->t('Fehlermeldungen'));

		$oNotification = $oDialog->createNotification(($oGui->t('Information')), $oGui->t('Sie können die Platzhalter "{label}" für die Anzeige des Feldnamens und "{value}" für den Wert des Feldes verwenden.'), 'info', [
			'dismissible' => false
		]);
		$oTab->setElement($oNotification);

		$oTab->setElement($oDialog->createRow($oGui->t('Pflichtfeld'), 'textarea', array(
			'db_column' => 'custom_errormessage_mandatoryfield',
		)));

		$oTab->setElement($oDialog->createRow($oGui->t('Formatprüfung'), 'textarea', array(
			'db_column' => 'custom_errormessage_formatcheck',
		)));

		$oTab->setElement($oDialog->createRow($oGui->t('Datum'), 'textarea', array(
			'db_column' => 'custom_errormessage_date',
		)));

		$oTab->setElement($oDialog->createRow($oGui->t('Geburtsdatum'), 'textarea', array(
			'db_column' => 'custom_errormessage_birthdate',
		)));

		$oTab->setElement($oDialog->createRow($oGui->t('E-Mail'), 'textarea', array(
			'db_column' => 'custom_errormessage_email',
		)));

		$oTab->setElement($oDialog->createRow($oGui->t('Telefon'), 'textarea', array(
			'db_column' => 'custom_errormessage_phone',
		)));

		$oTab->setElement($oDialog->createRow($oGui->t('Zahlung (Pflicht)'), 'textarea', array(
			'db_column' => 'custom_errormessage_payment_required',
		)));

		$oTab->setElement($oDialog->createRow($oGui->t('Zahlung (Fehler)'), 'textarea', array(
			'db_column' => 'custom_errormessage_payment_failed',
		)));

		$oDialog->setElement($oTab);

		/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */

		$oTab = $oDialog->createTab($oGui->t('CSS'));

		$oH3 = new Ext_Gui2_Html_H4();
		$oH3->setElement($oGui->t('CSS-Klassen'));
		$oTab->setElement($oH3);

		$oTab->setElement($oDialog->createRow($oGui->t('Standard'), 'input', array(
			'db_column' => 'custom_css_default',
		)));

		$oTab->setElement($oDialog->createRow($oGui->t('korrekter Eingabe'), 'input', array(
			'db_column' => 'custom_css_valid',
		)));

		$oTab->setElement($oDialog->createRow($oGui->t('falscher Eingabe'), 'input', array(
			'db_column' => 'custom_css_invalid',
		)));

		$oDialog->setElement($oTab);

		/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */

		$oTab = $oDialog->createTab($oGui->t('Weitere Vorlagen'));

		$oTab->setElement($oDialog->createRow($oGui->t('Preisliste'), 'textarea', array(
			'db_column' => 'custom_template_pricelist',
			'default_value' => \Factory::executeStatic('Ext_TC_Frontend_Template_Template', 'getDefaultValue', array('pricelist')),
			'style' => 'height:450px;'
		)));

		$oTab->setElement($oDialog->createRow($oGui->t('Feld-Vorlagen-Modus'), 'select', array(
			'db_column' => 'field_mode',
			'select_options' => array(
				'default' => $oGui->t('Standard'),
				'prefix' => $oGui->t('Prefix'),
				'individual' => $oGui->t('Individuell')
			)
		)));

		$oTab->setElement($oDialog->createRow($oGui->t('Prefix für individuelle Feld-Vorlagen'), 'input', array(
			'db_column' => 'custom_template_fieldtemplateprefix',
			'dependency_visibility' => array(
				'db_column' => 'field_mode',
				'on_values' => array('prefix')
			)
		)));

		$aFieldTemplates = Ext_TC_Frontend_Template::getDefaultTemplates();
		foreach($aFieldTemplates as $sFieldName=>$sFieldTemplate) {

			$sFieldLabel = ucwords(str_replace('_', ' ', $sFieldName));

			$oTab->setElement($oDialog->createRow(sprintf($oGui->t('Feld-Vorlagen für "%s"'), $sFieldLabel), 'textarea', array(
				'db_column' => 'custom_template_fieldtemplate_'.$sFieldName,
				'style' => 'width:700px;height:100px;',
				'default_value' => $sFieldTemplate,
				'dependency_visibility' => array(
					'db_column' => 'field_mode',
					'on_values' => array('individual')
				)
			)));
		}

		$oDialog->setElement($oTab);

		return $oDialog;
	}

	public static function getSelectOptionsUsageFilter()
    {

		return Ext_TC_Factory::executeStatic('Ext_TC_Frontend_Template_Gui2_Data', 'getUsageOptions', array(true));
	}

}
