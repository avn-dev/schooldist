<?php

class Ext_TC_Contact_Detail_Gui2 extends Ext_TC_Gui2 {

	protected $_bFirstDisplay = true;
	
	public function __construct($sHash='', $sDataClass = 'Ext_TC_Gui2_Data', $sViewClass = '', $sInstanceHash = null) {
		parent::__construct($sHash, $sDataClass, $sViewClass, $sInstanceHash);

		$this->setWDBasic('Ext_TC_Contact_Detail');
		
	}
	
	public function startPageOutput($bFirst = true){
		
		if($this->_bFirstDisplay){
		
			$this->setDefaultBars();
			$this->addDefaultColumns();
			$this->_bFirstDisplay = false;
		
		}
		
		return parent::startPageOutput($bFirst);
	}
	
	public function display($aOptionalData = array(), $bNoJavaScript = false) {
		
		if($this->_bFirstDisplay){

			$this->setDefaultBars();
			$this->addDefaultColumns();
			$this->_bFirstDisplay = false;

		}
		
		parent::display($aOptionalData, $bNoJavaScript);
		
	}
	
	public function addDefaultColumns($sTableAlias='', $oColumnGroup = NULL) {
		
		$aTypes					= Ext_TC_Contact_Detail::getTypes();
		
		$oColumn				= $this->createColumn();
		$oColumn->db_column		= 'type';
		$oColumn->db_alias		= $sTableAlias;
		$oColumn->title			= L10N::t('Art');
		$oColumn->width			= Ext_TC_Util::getTableColumnWidth('name');
		$oColumn->width_resize	= false;
		$oColumn->format		= new Ext_Gui2_View_Format_Selection($aTypes);
		$this->setColumn($oColumn);
		
		$oColumn				= $this->createColumn();
		$oColumn->db_column		= 'value';
		$oColumn->db_alias		= $sTableAlias;
		$oColumn->title			= L10N::t('Wert');
		$oColumn->width			= Ext_TC_Util::getTableColumnWidth('name');
		$oColumn->width_resize	= true;
		$this->setColumn($oColumn);
		
		//parent::addDefaultColumns($sTableAlias, $oColumnGroup);
	}

	protected function getDialog(){
		
		$sAccess = $this->getAccess();
		
		$aTypes	= Ext_TC_Contact_Detail::getTypes();
		
		$oDialog = $this->createDialog($this->t('Kontaktdaten bearbeiten'), $this->t('Neue Kontaktinformation'));
		$oDialog->access = array($sAccess, 'edit');
		
		$oDialog->setElement(
			$oDialog->createRow(
					$this->t('Art'), 
					'select', 
					array(
						'db_column' => 'type',
						'select_options' => $aTypes,
						'required' => 1,
						'db_alias' => 'tc_cd'
						)
					)
			);
		$oDialog->setElement(
			$oDialog->createRow(
					$this->t('Wert'), 
					'input', 
					array(
						'db_column' => 'value',
						'required' => 1,
						'db_alias' => 'tc_cd'
						)
					)
			);

		return $oDialog;
	}

	public function getAccess(){
		
		$aAccess = $this->access;
		$sAccess = $aAccess[0];
		
		if(empty($sAccess)){
			$sAccess = 'tc_contact';
		}
		
		return $sAccess;
	}

	protected function setDefaultBars(){

		$sAccess = $this->getAccess();
		
		if(empty($sAccess)){
			$sAccess = 'tc_contact';
		}
		
		$oDialog = $this->getDialog();
		
		$oBar = $this->createBar();
		$oBar->width = '100%';
		$oIcon = $oBar->createNewIcon($this->t('Neuer Eintrag'), $oDialog, $this->t('Neuer Eintrag'));
		$oIcon->access = array($sAccess, 'new');
		$oBar->setElement($oIcon);
		$oIcon = $oBar->createEditIcon($this->t('Editieren'), $oDialog, $this->t('Editieren'));
		$oIcon->access = array(
			array($sAccess, 'show'),
			array($sAccess, 'edit')
		);
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
