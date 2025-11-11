<?php

/**
 *  WDBasic-Klasse der Kontenpläne
 */
class Ext_TC_Accounting_Accountscode extends Ext_TC_Basic {

	/**
	 * @var string
	 */
	protected $_sTable = 'tc_accounting_accountscode';

	/**
	 * @var string
	 */
	protected $_sTableAlias = 'tc_ac';

	/**
	 * @var array
	 */
	protected $_aJoinedObjects = array(
		'categories'=>array(
			'class'=>'Ext_TC_Accounting_Category',
			'key'=>'accountscode_id',
			'type'=>'child',
			'check_active'=>true,
			'on_delete' => 'cascade'
		),
		'accounts'=>array(
			'class'=>'Ext_TC_Accounting_Account',
			'key'=>'accountscode_id',
			'type'=>'child',
			'check_active'=>true,
			'on_delete' => 'cascade'
		)
	);

	/**
	 * Gibt ein Array mit allen Kontenplänen zurück.
	 *
	 * @param bool $bForSelect
	 * @return array
	 */
	public static function getSelectOptions($bForSelect = false) {

		$aList = WDCache::remember('Ext_TC_Accounting_Accountscode::getSelectOptions', 86400, function() {
			return (new Ext_TC_Accounting_Accountscode())->getArrayList(true);
		});

		if($bForSelect) {
			$aList = Ext_TC_Util::addEmptyItem($aList);
		}
		
		return $aList;
	}	
	
	/**
	 * Gibt ein Array mit den Zwischenkategorien des Kontenplans zurück
	 *
	 * @return Ext_TC_Accounting_Category[]
	 */
	public function getCategories() {
		
		$aCategories = (array) $this->getJoinedObjectChilds('categories');
		
		return $aCategories;
	}
	
	/**
	 * Gibt ein Array mit den Konten des Kontenplans zurück
	 *
	 * @return Ext_TC_Accounting_Account[]
	 */
	public function getAccounts() {
		
		$aAccounts = (array) $this->getJoinedObjectChilds('accounts');
		
		return $aAccounts;
	}
	
	/**
	 * @param bool $bForSelect
	 * @return Ext_TC_Accounting_Accountscode 
	 */
	public static function getTypes($bForSelect = false){
		
		$aArray['income_account'] = L10N::t('Ertrag');
		$aArray['activity_account'] = L10N::t('Aufwand');
		$aArray['allocation_account'] = L10N::t('Verrechnungskonto');
		$aArray['assets_account'] = L10N::t('Aktiva');
		$aArray['liabilities_account'] = L10N::t('Passiva');
		
		if($bForSelect){
			$aArray = Ext_TC_Util::addEmptyItem($aArray);
		}
		
		return $aArray;
	}

	public function deleteEntryCache() {
		WDCache::delete('Ext_TC_Accounting_Accountscode::getSelectOptions');
	}

	public function save($bLog = true) {
		$this->deleteEntryCache();
		parent::save($bLog);
	}
	
	/**
	 * Gui-Liste der Kategorien
	 * @return Ext_TC_Gui2 
	 */
	
	public static function getCategoryGui(){
		
		$oGui = new Ext_TC_Gui2(md5('tc_accounting_accountscode_category'));

		$oGui->access = array('core_accounting_categories', '');
		$oGui->gui_description = Ext_TC_System_Navigation::tp();
		$oGui->gui_title = $oGui->t('Kontenklassen');
		$oGui->include_jquery = true;
		$oGui->include_jquery_multiselect = true;

		$oGui->setWDBasic('Ext_TC_Accounting_Category');
		$oGui->setTableData('orderby', array('tc_aca.name'=>'ASC'));
		$oGui->setTableData('limit', 30);
		
		// Dialog

		$oDialog = $oGui->createDialog($oGui->t('Kategorie "{name}" editieren'), $oGui->t('Kategorie anlegen'));
		
		$oDialog->setElement($oDialog->createRow($oGui->t('Art'), 'select', array(
			'db_alias' => 'tc_aca', 
			'db_column' => 'type', 
			'select_options' => Ext_TC_Accounting_Accountscode::getTypes(true),
			'required' => 1
		)));
		
		$oDialog->setElement($oDialog->createRow($oGui->t('Nummer'), 'input', array(
			'db_alias' => 'tc_aca', 
			'db_column' => 'number', 
			'style' => 'width: 100px;'
		)));

		$oDialog->setElement($oDialog->createRow($oGui->t('Name der Zwischenkategorie'), 'input', array(
			'db_alias' => 'tc_aca', 
			'db_column' => 'name', 
			'required' => 1
		)));		
		
		// Filter

		$oBar = $oGui->createBar();
		$oBar->width = '100%';

		$oFilter = $oBar->createFilter();
		$oFilter->db_column = array('name', 'number');
		$oFilter->db_alias = array('tc_aca', 'tc_aca');
		$oFilter->id = 'search';
		$oFilter->placeholder = $oGui->t('Suche').'…';

		$oBar->setElement($oFilter);
		
		$oBar->setElement($oBar->createSeperator());
		
		$oFilter = $oBar->createFilter('select');
		$oFilter->id = 'filter_type';
		$oFilter->db_alias = 'tc_aca';
		$oFilter->db_column = 'type';
		$oFilter->select_options = Ext_TC_Util::addEmptyItem(Ext_TC_Accounting_Accountscode::getTypes(),'--'.$oGui->t('Art').'--');
		$oFilter->db_operator = '=';
		$oFilter->value	= '';
		
		$oBar->setElement($oFilter);

		$oGui->setBar($oBar);

		// Icon

		$oBar = $oGui->createBar();

		$oIcon = $oBar->createNewIcon($oGui->t('Neuer Eintrag'), $oDialog, $oGui->t('Neuer Eintrag'));
		$oBar->setElement($oIcon);

		$oIcon = $oBar->createEditIcon($oGui->t('Editieren'), $oDialog, $oGui->t('Editieren'));
		$oBar->setElement($oIcon);

		$oIcon = $oBar->createDeleteIcon($oGui->t('Löschen'), $oGui->t('Löschen'));
		$oBar->setElement($oIcon);

		$oGui->setBar($oBar);

		// Loading / Pagination

		$oBar = $oGui->createBar();
		$oBar->width = '100%';
		$oBar->position = 'top';
		$oPagination = $oBar->createPagination(false, true);
		$oBar->setElement($oPagination);
		$oBar->createCSVExportWithLabel();
		$oLoading = $oBar->createLoadingIndicator();
		$oBar->setElement($oLoading);
		$oGui->setBar($oBar);

		// List
		
		$oColumn				= $oGui->createColumn();
		$oColumn->db_column		= 'name';
		$oColumn->db_alias		= 'tc_aca';
		$oColumn->title			= $oGui->t('Zwischenkategorie');
		$oColumn->width			= Ext_TC_Util::getTableColumnWidth('name');
		$oColumn->width_resize	= true;
		$oGui->setColumn($oColumn);

		$oColumn				= $oGui->createColumn();
		$oColumn->db_column		= 'type';
		$oColumn->db_alias		= 'tc_aca';
		$oColumn->title			= $oGui->t('Art');
		$oColumn->width			= Ext_TC_Util::getTableColumnWidth('name');
		$oColumn->width_resize	= false;
		$oColumn->format		= new Ext_TC_Accounting_Format_Type();
		$oGui->setColumn($oColumn);
		
		$oGui->addDefaultColumns();
		
		return $oGui;

	}
	
	/**
	 * Gui-Liste der Konten
	 * @return Ext_TC_Gui2 
	 */
	public static function getAccountGui(){
		
		$oGui = new Ext_TC_Gui2(md5('tc_accounting_accountscode_account'));

		$oGui->access = array('core_accounting_accounts', '');
		$oGui->gui_description = Ext_TC_System_Navigation::tp();
		$oGui->gui_title = $oGui->t('Konten');
		$oGui->include_jquery = true;
		$oGui->include_jquery_multiselect = true;

		$oGui->setWDBasic('Ext_TC_Accounting_Account');
		$oGui->setTableData('orderby', array('tc_aa.name'=>'ASC'));
		$oGui->setTableData('limit', 30);
		
		$aCurrencies = Ext_TC_Util::addEmptyItem(Ext_TC_Currency::getISOSelectOptions());
	
		// Dialog

		$oDialog = $oGui->createDialog($oGui->t('Konto "{name}" editieren'), $oGui->t('Konto anlegen'));

		$oDialog->setElement($oDialog->createRow($oGui->t('Art'), 'select', array(
			'db_alias' => 'tc_aa', 
			'db_column' => 'type', 
			'select_options' => Ext_TC_Accounting_Accountscode::getTypes(true),
			'required' => 1,
			'events' => array(
				array(
					'event' 		=> 'change',
					'function' 		=> 'prepareUpdateSelectOptions'
				)
			)
		)));		
		
		$oDialog->setElement($oDialog->createRow($oGui->t('Zwischenkategorie'), 'select', array(
			'db_alias' => 'tc_aa', 
			'db_column' => 'category_id',
			'selection' => new Ext_TC_Accounting_Selection_Category(),
			'required' => 1		
		)));
		
		$oDialog->setElement($oDialog->createRow($oGui->t('Nummer'), 'input', array(
			'db_alias' => 'tc_aa', 
			'db_column' => 'number',
			'style' => 'width: 100px;',
			'required' => 1
		)));
		
		$oDialog->setElement($oDialog->createRow($oGui->t('Kontoname'), 'input', array(
			'db_alias' => 'tc_aa', 
			'db_column' => 'name', 
			'required' => 1
		)));
		
		$oDialog->setElement($oDialog->createRow($oGui->t('Währung'), 'select', array(
			'db_alias' => 'tc_aa', 
			'db_column' => 'currency_iso',
			'select_options' => $aCurrencies,
			'required' => 1
		)));
		
		// Filter

		$oBar = $oGui->createBar();
		$oBar->width = '100%';

		$oFilter = $oBar->createFilter();
		$oFilter->db_column = array('name', 'number');
		$oFilter->db_alias = array('tc_aa', 'tc_aa');
		$oFilter->id = 'search';
		$oFilter->placeholder = $oGui->t('Suche').'…';

		$oBar->setElement($oFilter);
		
		$oFilter = $oBar->createFilter('select');
		$oFilter->id = 'filter_type';
		$oFilter->db_alias = 'tc_aa';
		$oFilter->db_column = 'type';
		$oFilter->select_options = Ext_TC_Accounting_Accountscode::getTypes(true);
		$oFilter->label = $oGui->t('Art');
		$oFilter->db_operator = '=';
		$oFilter->value	= '';
		
		$oBar->setElement($oFilter);
		
		$oFilter = $oBar->createFilter('select');
		$oFilter->id = 'filter_category';
		$oFilter->db_alias = 'tc_aa';
		$oFilter->db_column = 'category_id';
		$oFilter->select_options = Ext_TC_Util::addEmptyItem(Ext_TC_Accounting_Category::getSelectOptions());
		$oFilter->label = $oGui->t('Zwischenkategorie');
		$oFilter->db_operator = '=';
		$oFilter->value	= '';
		
		$oBar->setElement($oFilter);
		
		$oGui->setBar($oBar);

		// Icon

		$oBar = $oGui->createBar();

		$oIcon = $oBar->createNewIcon($oGui->t('Neuer Eintrag'), $oDialog, $oGui->t('Neuer Eintrag'));
		$oBar->setElement($oIcon);

		$oIcon = $oBar->createEditIcon($oGui->t('Editieren'), $oDialog, $oGui->t('Editieren'));
		$oBar->setElement($oIcon);

		$oIcon = $oBar->createDeleteIcon($oGui->t('Löschen'), $oGui->t('Löschen'));
		$oBar->setElement($oIcon);

		$oGui->setBar($oBar);

		// Loading / Pagination

		$oBar = $oGui->createBar();
		$oBar->width = '100%';
		$oBar->position = 'top';
		$oPagination = $oBar->createPagination(false, true);
		$oBar->setElement($oPagination);
		$oBar->createCSVExportWithLabel();
		$oLoading = $oBar->createLoadingIndicator();
		$oBar->setElement($oLoading);
		$oGui->setBar($oBar);

		// List
		
		$oColumn				= $oGui->createColumn();
		$oColumn->db_column		= 'name';
		$oColumn->db_alias		= 'tc_aa';
		$oColumn->title			= $oGui->t('Kontoname');
		$oColumn->width			= Ext_TC_Util::getTableColumnWidth('name');
		$oColumn->width_resize	= true;
		$oGui->setColumn($oColumn);

		$oColumn				= $oGui->createColumn();
		$oColumn->db_column		= 'number';
		$oColumn->db_alias		= 'tc_aa';
		$oColumn->title			= $oGui->t('Kontonummer');
		$oColumn->width			= Ext_TC_Util::getTableColumnWidth('number');
		$oColumn->width_resize	= false;
		$oColumn->format		= new Ext_TC_Accounting_Format_Number();
		$oGui->setColumn($oColumn);
		
		$oColumn				= $oGui->createColumn();
		$oColumn->db_column		= 'type';
		$oColumn->db_alias		= 'tc_aa';
		$oColumn->title			= $oGui->t('Art');
		$oColumn->width			= Ext_TC_Util::getTableColumnWidth('name');
		$oColumn->width_resize	= false;
		$oColumn->format		= new Ext_TC_Accounting_Format_Type();
		$oGui->setColumn($oColumn);
		
		$oColumn				= $oGui->createColumn();
		$oColumn->db_column		= 'category_id';
		$oColumn->db_alias		= 'tc_aa';
		$oColumn->title			= $oGui->t('Zwischenkategorie');
		$oColumn->width			= Ext_TC_Util::getTableColumnWidth('name');
		$oColumn->format		= new Ext_TC_Accounting_Format_Category();
		$oColumn->width_resize	= false;
		$oGui->setColumn($oColumn);
		
		$oColumn				= $oGui->createColumn();
		$oColumn->db_column		= 'currency_iso';
		$oColumn->db_alias		= 'tc_aa';
		$oColumn->title			= $oGui->t('Währung');
		$oColumn->width			= Ext_TC_Util::getTableColumnWidth('name');
		$oColumn->format		= new Ext_Gui2_View_Format_Selection($aCurrencies);
		$oColumn->width_resize	= false;
		$oGui->setColumn($oColumn);
		
		$oGui->addDefaultColumns();
		

		return $oGui;

	}	
	
	
}
