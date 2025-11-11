<?php

class Ext_Thebing_Accommodation_Gui2 extends Ext_Thebing_Gui2_Basic_School {

	use \Tc\Traits\Gui2\Import;

	// ========== DAS IST EIN KOMPLETT SEPARATER STATISCHER TEIL ==========

	const L10N_PATH = 'Thebing » Accommodation » Accommodations';

	/**
	 * @var mixed
	 */
	public static $oDialog;

	/**
	 * @var mixed
	 */
	public static $oTab;

	/**
	 * @param string $sName
	 * @param string $sInputType
	 * @param mixed[] $aInputOptions
	 * @param sreing $sAccommodationInfoField
	 * @return boolean
	 */
	public static function createRow(
		$sName,
		$sInputType,
		$aInputOptions = [],
		$sAccommodationInfoField = '',
		Ext_Gui2_View_Format_Abstract $accommodationInfoFieldFormatClass = null
	) {

		/*
		 * Wenn kein Übernahmefeld angegeben ist, kann das Feld normal angezeigt werden
		 * @todo Wenn der Anbieterlogin wieder da ist, das "1 ||" entfernen!
		 */
		if(empty($sAccommodationInfoField)) {
			$oRow = self::$oDialog->createRow($sName, $sInputType, $aInputOptions);
			self::$oTab->setElement($oRow);
			return true;
		}

		$aSelectOptions = $aInputOptions['select_options'];
		unset($aInputOptions['select_options']);

		$oDiv = new Ext_Gui2_Html_Div();
		$oDiv->class = 'flex flex-row items-center gap-x-2';

		$oDivLeft = new Ext_Gui2_Html_Div();
		$oDivLeft->class = "grow";

		$oInput = self::$oDialog->createSaveField($sInputType, $aInputOptions);

		if($sInputType == 'select') {
			foreach((array)$aSelectOptions as $iValue => $sText) {
				$oOption = new Ext_Gui2_Html_Option();
				$oOption->value = $iValue;
				$oOption->setElement((string)$sText);
				$oInput->setElement($oOption);
			}
		}

		$oDivLeft->setElement($oInput);
		$oDiv->setElement($oDivLeft);

		if($sAccommodationInfoField != '') {

			$oImg = new Ext_Gui2_Html_I();
			$oImg->class = 'fa fa-angle-double-left flex-none accommodation_info_icon';
			$oImg->title = L10N::t('Eingaben aus dem Anbieter-Portal übernehmen', self::L10N_PATH);
			$oDiv->setElement($oImg);

			$oDivRight = new Ext_Gui2_Html_Div();
			$oDivRight->class = "grow";
			$oDivRight->title = L10N::t('Eingaben aus dem Anbieter-Portal', self::L10N_PATH);

			$aTempInputOptions = $aInputOptions;
			$aTempInputOptions['db_column'] = $sAccommodationInfoField;
			if ($accommodationInfoFieldFormatClass !== null) {
				$aTempInputOptions['format'] = $accommodationInfoFieldFormatClass;
			}
			$aTempInputOptions['readonly'] = 'readonly';
			$oInput = self::$oDialog->createSaveField($sInputType, $aTempInputOptions);
			$oDivRight->setElement($oInput);

			$oDivRight->class = "accommodation_info";
			$oDiv->setElement($oDivRight);

		}

		$oRow = self::$oDialog->createRow($sName, $oDiv, $aInputOptions);
		self::$oTab->setElement($oRow);

		return true;

	}


	// ========== ENDE KOMPLETT SEPARATER STATISCHER TEIL ==========


	/**
	 * @var string
	 */
	protected $sSchoolField = '';

	/**
	 * @var string
	 */
	protected $sClientField = '';

	/**
	 * @param Ext_Gui2 $oGui
	 * @return Ext_Gui2
	 */
	public static function getCostcategoryGui(Ext_Gui2 $oGui) {

		$aPeriods = Ext_Thebing_Accommodation_Salary::getPeriods();

		$oSchool = Ext_Thebing_School::getSchoolFromSession();
		$iCurrency = $oSchool->getAccommodationCurrency();

		$oCurrency = Ext_Thebing_Currency::getInstance($iCurrency);
		$sSchoolCurrency = $oCurrency->getSign();

		$oInnerGui = $oGui->createChildGui(md5('thebing_accommodation_salary'), 'Ext_Thebing_Accommodation_Gui2_Salary');
		$oInnerGui->query_id_column = 'id';
		$oInnerGui->query_id_alias = '';
		$oInnerGui->foreign_key = 'accommodation_id';
		$oInnerGui->foreign_key_alias = '';
		$oInnerGui->parent_hash = $oGui->hash;
		$oInnerGui->parent_primary_key = 'id';
		$oInnerGui->load_admin_header = false;
		$oInnerGui->multiple_selection = false;
		$oInnerGui->row_style = new Ext_Thebing_Gui2_Style_Teacher_Salary_Row();
		$oInnerGui->calendar_format = new Ext_Thebing_Gui2_Format_Date();
		$oInnerGui->row_icon_status_active = new Ext_Thebing_Gui2_Icon_Teacher_Salary();
		//$oInnerGui->class_js = 'AccommodationSalaryGui';
		$oInnerGui->access = 'thebing_marketing_accommodationcategories';

		$oInnerGui->setWDBasic('Ext_Thebing_Accommodation_Salary');
		$oInnerGui->setTableData('limit', 30);
		$oInnerGui->setTableData('orderby', ['valid_from' => 'DESC']);
		$oInnerGui->setTableData('where', ['active' => 1]);

		// Editieren MIT Datum
		$oInnerDialog = $oInnerGui->createDialog(
			$oGui->t('Kostenparameter von "{accommodation_name}" bearbeiten'),
			$oGui->t('Kostenparameter von "{accommodation_name}" bearbeiten')
		);
		$oInnerDialog->sDialogIDTag = 'ACCOMMODATION_SALARY_';
		$oInnerDialog->width = 850;
		$oInnerDialog->height = 300;

		$oInnerDialog->setElement(
			$oInnerDialog->createRow(
				L10N::t('Kostenkategorie', $oGui->gui_description),
				'select',
				[
					'db_column' => 'costcategory_id',
					'selection' => new Ext_Thebing_Gui2_Selection_Accommodation_CostCategory($oGui->gui_description),
					'required' => true,
				]
			)
		);

		/*$oContainer = new Ext_Gui2_Html_Div();
		$oContainer->id = 'salary_container_'.$oInnerGui->hash;
		$oContainer->style = 'display: none;';
		$oContainer->setElement(
			Ext_Thebing_Gui2_Util::getInputSelectRow(
				$oInnerDialog,
				[
					'db_alias' => 'kas',
					'db_column_1' => 'salary',
					'db_column_2' => 'salary_period',
					'select_options' => $aPeriods,
					'class_1' => 'amount',
					'format_1' => new Ext_Thebing_Gui2_Format_Float(),
				],
				L10N::t('Gehalt', $oGui->gui_description),
				$sSchoolCurrency.' '.L10N::t('pro', $oGui->gui_description)
			)
		);
		$oInnerDialog->setElement($oContainer);*/

		$oInnerDialog->setElement(
			$oInnerDialog->createRow(
				L10N::t('Gültig ab', $oGui->gui_description),
				'calendar',
				[
					'db_column' => 'valid_from',
					'format' => new Ext_Thebing_Gui2_Format_Date(),
					'required' => true,
				]
			)
		);

		$oInnerDialog->setElement(
			$oInnerDialog->createRow(
				L10N::t('Kommentar', $oGui->gui_description),
				'textarea',
				[
					'db_column' => 'comment',
				]
			)
		);

		// Editieren OHNE Datum
		$oInnerDialog2 = $oInnerGui->createDialog(
			$oGui->t('Kostenparameter von "{accommodation_name}" bearbeiten'),
			$oGui->t('Kostenparameter von "{accommodation_name}" bearbeiten')
		);
		$oInnerDialog2->sDialogIDTag = 'ACCOMMODATION_SALARY_';
		$oInnerDialog2->width = 850;
		$oInnerDialog2->height = 300;

		$oInnerDialog2->setElement(
			$oInnerDialog2->createRow(
				L10N::t('Kostenkategorie', $oGui->gui_description),
				'select',
				[
					'db_column' => 'costcategory_id',
					'selection' => new Ext_Thebing_Gui2_Selection_Accommodation_CostCategory($oGui->gui_description),
					'required' => true,
				]
			)
		);

		/*$oContainer = new Ext_Gui2_Html_Div();
		$oContainer->id = 'salary_container_'.$oInnerGui->hash;
		$oContainer->style = 'display: none;';
		$oContainer->setElement(
			Ext_Thebing_Gui2_Util::getInputSelectRow(
				$oInnerDialog2,
				[
					'db_alias' => 'kas',
					'db_column_1' => 'salary',
					'db_column_2' => 'salary_period',
					'select_options' => $aPeriods,
					'class_1' => 'amount',
					'format_1' => new Ext_Thebing_Gui2_Format_Float(),
				],
				L10N::t('Gehalt', $oGui->gui_description),
				$sSchoolCurrency.' '.L10N::t('pro', $oGui->gui_description)
			)
		);
		$oInnerDialog2->setElement($oContainer);*/

		$oInnerDialog2->setElement(
			$oInnerDialog2->createRow(
				L10N::t('Kommentar', $oGui->gui_description),
				'textarea',
				[
					'db_column' => 'comment',
				]
			)
		);

		// Leiste(n)
		$oBar = $oInnerGui->createBar();
		$oBar->width = '100%';
		/*$oBar->setElement(
			$oBar->createLabelGroup(
				L10N::t('Aktionen', $oGui->gui_description)
			)
		);*/
		$oBar->setElement(
			$oBar->createNewIcon(
				L10N::t('Neuer Eintrag', $oGui->gui_description),
				$oInnerDialog,
				L10N::t('Neuer Eintrag', $oGui->gui_description)
			)
		);
		$oBar->setElement(
			$oBar->createEditIcon(
				L10N::t('Editieren', $oGui->gui_description),
				$oInnerDialog2,
				L10N::t('Editieren', $oGui->gui_description)
			)
		);
		$oBar->setElement(
			$oBar->createDeleteIcon(
				L10N::t('Löschen', $oGui->gui_description),
				L10N::t('Löschen', $oGui->gui_description)
			)
		);
		$oInnerGui->setBar($oBar);

		$oBar = $oInnerGui->createBar();
		$oBar->width = '100%';
		$oBar->position = 'top';
		$oBar->setElement(
			$oBar->createPagination(false, true)
		);
		$oBar->setElement(
			$oBar->createLoadingIndicator()
		);
		$oInnerGui->setBar($oBar);

		// Spalten
		$oColumn = $oInnerGui->createColumn();
		$oColumn->db_alias = '';
		$oColumn->db_column = 'costcategory_id';
		$oColumn->title = L10N::t('Kostenkategorie', $oGui->gui_description);
		$oColumn->width = Ext_Thebing_Util::getTableColumnWidth('name');
		$oColumn->width_resize = true;
		$oColumn->format = new Ext_Thebing_Gui2_Format_Accommodation_Costcategory();
		$oInnerGui->setColumn($oColumn);

		$oColumn = $oInnerGui->createColumn();
		$oColumn->db_alias = 'kts';
		$oColumn->db_column = 'valid_from';
		$oColumn->title = L10N::t('Gültig ab', $oGui->gui_description);
		$oColumn->width = Ext_Thebing_Util::getTableColumnWidth('date');
		$oColumn->width_resize = false;
		$oColumn->format = new Ext_Thebing_Gui2_Format_Date();
		$oInnerGui->setColumn($oColumn);

		$oColumn = $oInnerGui->createColumn();
		$oColumn->db_alias = 'kts';
		$oColumn->db_column = 'valid_until';
		$oColumn->title = L10N::t('Gültig bis', $oGui->gui_description);
		$oColumn->width = Ext_Thebing_Util::getTableColumnWidth('date');
		$oColumn->width_resize = false;
		$oColumn->format = new Ext_Thebing_Gui2_Format_Date();
		$oInnerGui->setColumn($oColumn);

		$oColumn = $oInnerGui->createColumn();
		$oColumn->db_alias = 'kts';
		$oColumn->db_column = 'comment';
		$oColumn->title = L10N::t('Kommentar', $oGui->gui_description);
		$oColumn->width = 200;
		$oColumn->width_resize = true;
		$oInnerGui->setColumn($oColumn);

		$oColumn = $oInnerGui->createColumn();
		$oColumn->db_alias = 'kts';
		$oColumn->db_column = 'user_id';
		$oColumn->title = L10N::t('Bearbeiter', $oGui->gui_description);
		$oColumn->width = Ext_Thebing_Util::getTableColumnWidth('user_name');
		$oColumn->width_resize = false;
		$oColumn->inplaceEditor = 0;
		$oColumn->format = new Ext_Gui2_View_Format_UserName();
		$oInnerGui->setColumn($oColumn);

		$oColumn = $oInnerGui->createColumn();
		$oColumn->db_alias = 'kts';
		$oColumn->db_column = 'changed';
		$oColumn->db_type = 'timestamp';
		$oColumn->title = L10N::t('Verändert', $oGui->gui_description);
		$oColumn->width = Ext_Thebing_Util::getTableColumnWidth('date');
		$oColumn->format = new Ext_Thebing_Gui2_Format_Date_DateTime();
		$oInnerGui->setColumn($oColumn);

		return $oInnerGui;

	}

	/**
	 * {@inheritdoc}
	 */
	public function getTranslations($sL10NDescription) {

		$aData = parent::getTranslations($sL10NDescription);

		$aData['sure_override_all_data'] = L10N::t('Informationen wirklich übernehmen? Alle vorhandenen Daten werden überschrieben!', $sL10NDescription);

		return $aData;

	}

	/**
	 * {@inheritdoc}
	 */
	protected function getEditDialogHTML(&$oDialogData, $aSelectedIds, $sAdditional = false) {

		global $_VARS;

		$aData = parent::getEditDialogHTML($oDialogData, $aSelectedIds, $sAdditional);

		// Alle aktiven Kategorien abfragen, keine Filterung nach Schule o.ä.
		$aData['category_data'] = Ext_Thebing_Accommodation_Category::getRepository()->findBy(['active' => 1]);
		$aData['category_data'] = array_map(
			function(Ext_Thebing_Accommodation_Category $oAccommodation) {
				return $oAccommodation->getData();
			},
			$aData['category_data']
		);

		return $aData;
	}

	/**
	 * {@inheritdoc}
	 */
	protected function getDialogHTML(&$sIconAction, &$oDialogData, $aSelectedIds = array(), $sAdditional = false) {

		$aSelectedIds = (array)$aSelectedIds;

		switch($sIconAction) {

			case 'accommodation_blocking':
				$oDialog = $this->_oGui->createDialog($this->_oGui->t('Blockierung für').' {ext_33}');
				$oDialog->width = 950;
				return $oDialog->generateAjaxData($aSelectedIds, $this->_oGui->hash);

		}

		return parent::getDialogHTML($sIconAction, $oDialogData, $aSelectedIds, $sAdditional);

	}

	/**
	 * {@inheritdoc}
	 */
	protected function _getErrorMessage($sError, $sField = '', $sLabel = '', $sAction = null, $sAdditional = null) {

		if(
			$sError == 'INVALID_MAIL' &&
			$sField == 'email'
		) {
			$sMessage = L10N::t('Das Feld "%s" muss eine gültige E-Mail-Adresse enthalten.', $this->_oGui->gui_description);
			return sprintf($sMessage, L10N::t('E-Mail', $this->_oGui->gui_description));
		}

		if($sError == 'ALLOCATIONS_EXISTS') {
			$sMessage = 'Es befinden sich noch Zuweisungen zu dieser Familie.';
			return $this->t($sMessage);
		}

		return parent::_getErrorMessage($sError, $sField, $sLabel);

	}

	/**
	 * {@inheritdoc}
	 */
	protected function saveEditDialogData(array $aSelectedIds, $aSaveData, $bSave = true, $sAction = 'edit', $bPrepareOpenDialog = true) {

		$aTransfer = parent::saveEditDialogData($aSelectedIds, $aSaveData, $bSave, $sAction, $bPrepareOpenDialog);

		foreach((array)$aTransfer['error'] as $iKey => $mError) {
			if(
				is_array($mError) &&
				$mError['input']['dbalias'] == 'customer_db_4' &&
				$mError['input']['dbcolumn'] == 'email'
			) {
				$aTransfer['error'][$iKey]['input']['dbalias'] = '';
			}
		}

		return $aTransfer;

	}

	/**
	 * @inheritdoc
	 */
	protected function _setFilterElementDataByRef($oElement, $iElementKey, &$aLastFilterData, $bWDSearch = false) {

		if($oElement->id === 'filter_school') {

			$aSchoolIds = array_keys(Ext_Thebing_Client::getStaticSchoolListByAccess());
			$this->_aQueryParts['where'] .= " AND
				/* Für Schulfilter auf Option 0: Nur Schulen mit Recht! */
				`filter_schools`.`school_id` IN ( ".join(',', $aSchoolIds)." )
			";

		}

		parent::_setFilterElementDataByRef($oElement, $iElementKey, $aLastFilterData, $bWDSearch);

	}

	protected function getImportService(): \Ts\Service\Import\AbstractImport {
		return new \Ts\Service\Import\Accommodation;
	}

	protected function getImportDialogId() {
		return 'ACCOMMODATION_IMPORT_';
	}
	
	protected function addSettingFields(\Ext_Gui2_Dialog $oDialog) {
		
		$oRow = $oDialog->createRow($this->t('Vorhandene Einträge aktualisieren'), 'checkbox', ['db_column'=>'settings', 'db_alias'=>'update_existing']);
		$oDialog->setElement($oRow);

		$oRow = $oDialog->createRow($this->t('Fehler überspringen'), 'checkbox', ['db_column'=>'settings', 'db_alias'=>'skip_errors']);
		$oDialog->setElement($oRow);
		
	}
	
}
