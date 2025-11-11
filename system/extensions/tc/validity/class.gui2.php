<?php

class Ext_TC_Validity_Gui2 extends Ext_TC_Gui2 {

	protected $_bFirstDisplay = true;
	
	/**
	 * ID of the Parent Element
	 * @var int 
	 */
	protected $_sValidityIdColumn = 'parent_id';
	
	/**
	 * ID of the Select Element
	 * @var int 
	 */
	protected $_sValidityItemColumn = 'item_id';
	
	/**
	 * Elements of the Select Element
	 * @var array 
	 */
	protected $_mValidityItemOptions = array();

	/**
	 * Optional Selection Klasse
	 * @var array
	 */
	protected $_mValidityItemSelection = array();
	
	/**
	 * 
	 * @var boolean 
	 */
	protected $_bCheckItemId = true;

	public function __construct($sHash='', $sDataClass = 'Ext_TC_Gui2_Data', $sViewClass = '', $sInstanceHash = null) {

		parent::__construct($sHash, $sDataClass, $sViewClass, $sInstanceHash);

		$this->row_icon_status_active = new \Ext_TC_Validity_Gui2_Icon();
		$this->row_style = new \Ext_TC_Validity_Gui2_Style();

	}

	/**
	 * get the Date Klass for Format Data
	 * @return Ext_TC_Gui2_Format_Date 
	 */
	public function getDateClass(){
		return Ext_TC_Factory::getObject('Ext_TC_Gui2_Format_Date');
	}

	/**
	 * Set the WDBasic an the Selectdata for the Dialog
	 * @param string $sClassName
	 * @param array OR SELECTION $mIdSelect
	 */
	public function setWDBasic($sClassName) {
		
		$sBack = parent::setWDBasic($sClassName);

		/** @var Ext_TC_Validity $oTemp */
		$oTemp = new $sClassName();
		$this->_sValidityIdColumn		= $oTemp->sParentColumn;
		$this->_bCheckItemId			= $oTemp->bCheckItemId;
		$this->setOption('validity_dependency_field', $oTemp->sDependencyColumn);
		
		if(!$this->getOption('validity_hide_select')) {
			$this->_sValidityItemColumn		= $oTemp->sItemColumn;
		}
		
		$sOrderField = 'valid_from';
		if($this->query_id_alias != '') {
			$sOrderField = $this->query_id_alias.'.'.$sOrderField;
		}

		// Standardsortierung
		$this->setTableData('orderby', array($sOrderField=>'DESC'));
		$this->setTableData('limit', 30);

		return $sBack;
	}
	
	public function setValiditySelectSettings($mIdSelect, $oIdSelection=null) {
		
		if(!$this->getOption('validity_hide_select')) {
			$this->_mValidityItemOptions	= $mIdSelect;
			$this->_mValidityItemSelection	= $oIdSelection;
		}
		
	}

	/**
	 * @param string $sLabel
	 * @param array $aSelectOptions
	 */
	public function setValidityDependency($sLabel, array $aSelectOptions) {
		$this->setOption('validity_dependency_label', $sLabel);
		$this->setOption('validity_dependency_options', $aSelectOptions);
	}
	
	public function startPageOutput($bFirst = true){

		if($this->_bFirstDisplay){
			$this->setDefaultBars();
			$this->addDefaultColumns();
			$this->_bFirstDisplay = false;
		}
		
		return parent::startPageOutput($bFirst);
	}
	
	public function getAccess(){
		
		$aAccess = $this->access;
		if(is_array($aAccess)) {
			$sAccess = $aAccess[0];
		} else {
			$sAccess = $aAccess;
		}

		if(empty($sAccess)){
			$sAccess = 'tc_validity';
		}
		
		return $sAccess;
	}

    /**
     * @param bool $bNew
     * @param string $sAlias
     * @return Ext_Gui2_Dialog
     */
	public function getDialog($bNew = true, $sAlias = 'validity'){
		
		$oValidityDialog = $this->createDialog($this->t('Gültigkeit editieren'), $this->t('Neue Gültigkeit anlegen'));
		$oValidityDialog->sDialogIDTag	= 'VALIDITY_';
				
		$bRequireFields = true;
		if($this->getOption('validity_no_required_fields')) {
			$bRequireFields = false;
		}

		if(!$this->getOption('validity_hide_select')) {
			$aData = array(
				'db_alias'			=> $sAlias,
				'db_column'			=> $this->_sValidityItemColumn,
				'select_options'	=> array(),
				'required'			=> $bRequireFields
			);

			if(is_object($this->_mValidityItemSelection)){
				$aData['selection'] = $this->_mValidityItemSelection;
			} else if(is_array($this->_mValidityItemOptions)){
				$aData['select_options'] = $this->_mValidityItemOptions;
			}
		}
		
		if(!$this->getOption('validity_hide_select')) {
			$oValidityDialog->setElement($oValidityDialog->createRow($this->t('Gültigkeit für'), 'select', $aData));
		}

		if(
			$bNew ||
			$this->getOption('validity_show_valid_from')
		) {

			$oValidityDialog->setElement($oValidityDialog->createRow($this->t('Gültig ab'), 'calendar', array(
				'db_alias'		=> $sAlias,
				'db_column'		=> 'valid_from',
				'format'		=> $this->getDateClass(),
				'required'		=> $bRequireFields
			)));
		}

		if($this->getOption('validity_show_valid_until')) {
			$oValidityDialog->setElement($oValidityDialog->createRow($this->t('Gültig bis'), 'calendar', array(
				'db_alias'		=> $sAlias,
				'db_column'		=> 'valid_until',
				'format'		=> $this->getDateClass(),
				'required'		=> $bRequireFields
			)));
		}

		if($this->getOption('validity_dependency_options')) {
			$oValidityDialog->setElement($oValidityDialog->createRow($this->getOption('validity_dependency_label'), 'select', [
				'db_alias' => $sAlias,
				'db_column' => $this->getOption('validity_dependency_field'),
				'select_options' => $this->getOption('validity_dependency_options'),
			]));
		}

		if($this->getOption('validity_show_comment_field')) {
			$oValidityDialog->setElement($oValidityDialog->createRow($this->t('Kommentar'), 'textarea', [
				'db_alias' => $sAlias,
				'db_column' => 'comment'
			]));
		}

		$oValidityDialog->width = 800;
		$oValidityDialog->height = 500;

		return $oValidityDialog;
	}
	
	public function display($aOptionalData = array(), $bNoJavaScript = false) {
		
		if($this->_bFirstDisplay){
			$this->setDefaultBars();
			$this->addDefaultColumns();
			$this->_bFirstDisplay = false;
		}
		
		parent::display($aOptionalData, $bNoJavaScript);
		
	}
	
	public function setDefaultBars($sTableAlias = ''){

		$bResize = true;
		
		if(!$this->getOption('validity_hide_select')) {
			$oColumn				= $this->createColumn();
			$oColumn->db_column		= $this->_sValidityItemColumn;
			$oColumn->db_alias		= $sTableAlias;
			$oColumn->title			= $this->t('Gültig für');
			$oColumn->width			= Ext_TC_Util::getTableColumnWidth('name');
			$oColumn->width_resize	= true;
			$oColumn->format		= new Ext_Gui2_View_Format_Selection($this->_mValidityItemOptions);
			$this->setColumn($oColumn);
			
			$bResize = false;
		}

		if($this->getOption('validity_dependency_options')) {
			$oColumn = $this->createColumn();
			$oColumn->db_column = $this->getOption('validity_dependency_field');
			$oColumn->db_alias = $sTableAlias;
			$oColumn->title = $this->getOption('validity_dependency_label');
			$oColumn->width = Ext_TC_Util::getTableColumnWidth('name');
			$oColumn->format = new Ext_Thebing_Gui2_Format_Select($this->getOption('validity_dependency_options'));
			$this->setColumn($oColumn);
		}
		
		$oColumn				= $this->createColumn();
		$oColumn->db_column		= 'valid_from';
		$oColumn->db_alias		= $sTableAlias;
		$oColumn->title			= $this->t('Gültig von');
		$oColumn->width			= Ext_TC_Util::getTableColumnWidth('date');
		$oColumn->format		= $this->getDateClass();
		$oColumn->width_resize	= $bResize;
		$this->setColumn($oColumn);
		
		$oColumn				= $this->createColumn();
		$oColumn->db_column		= 'valid_until';
		$oColumn->db_alias		= $sTableAlias;
		$oColumn->title			= $this->t('Gültig bis');
		$oColumn->width			= Ext_TC_Util::getTableColumnWidth('date');
		$oColumn->format		= $this->getDateClass();
		$oColumn->width_resize	= $bResize;
		$this->setColumn($oColumn);

		if($this->getOption('validity_show_comment_field')) {
			$oColumn = $this->createColumn();
			$oColumn->db_column = 'comment';
			$oColumn->db_alias = $sTableAlias;
			$oColumn->title = $this->t('Kommentar');
			$oColumn->width = Ext_TC_Util::getTableColumnWidth('comment');
			$this->setColumn($oColumn);
		}
		
		$this->addColumnsHook($sTableAlias);
		
		parent::addDefaultColumns($sTableAlias);

	}
	
	/**
	 * Möglichkeit weitere Spalten der GUI hinzuzufügen
	 * 
	 * @param string $sTableAlias
	 */
	protected function addColumnsHook($sTableAlias = '') {}
	
	public function addDefaultColumns($mFormat = NULL, $mWidth = NULL) {

		$sAccess = $this->getAccess();

		$oDialogNew = $this->getDialog();
		$oDialogEdit = $this->getDialog(false);

		$oBar = $this->createBar();
		$oBar->width = '100%';

		$oIcon = $oBar->createNewIcon($this->t('Neuer Eintrag'), $oDialogNew, $this->t('Neuer Eintrag'));
		$oIcon->access = array($sAccess, 'new');
		$oBar->setElement($oIcon);

		$oIcon = $oBar->createEditIcon($this->t('Editieren'), $oDialogEdit, $this->t('Editieren'));
		$oIcon->access = array($sAccess, 'edit');
		$oBar->setElement($oIcon);

		$oIcon = $oBar->createDeleteIcon($this->t('Löschen'), $this->t('Löschen'));
		$oIcon->access = array($sAccess, 'delete');
		$oBar->setElement($oIcon);

		$this->setBar($oBar);

		$oBar = $this->createBar();
		$oBar->width = '100%';
		$oBar->position = 'top';
		$oPagination = $oBar->createPagination(false, true);
		$oBar->setElement($oPagination);
		$oLoading = $oBar->createLoadingIndicator();
		$oBar->setElement($oLoading);
		$this->setBar($oBar);

	}
	
}
