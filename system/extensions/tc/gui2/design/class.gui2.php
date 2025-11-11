<?php

class Ext_TC_Gui2_Design_Gui2 extends Ext_TC_Gui2_Design_Gui2_Basic {

	/**
	 * get uniq hash!
	 * WARNING! Do not change that hash!
	 * The hash string is used in the JS File
	 * @return type 
	 */
	public function getHash(){
		$sHash = md5('core_gui2_design');
		return $sHash;
	}
	
	/**
	 * Set all basic GUI Informations
	 */
	public function setConstructData(){
		$this->setWDBasic('Ext_TC_Gui2_Design');
		$this->multiple_selection = 0;
		//$this->include_jquery = true;
		//$this->include_jquery_multiselect = true;
		$this->class_js = 'DesignerGui';
		$this->access = array('core_gui2_designer', '');
		
		$this->setTableData('orderby', array('intern_name'=>'ASC'));
		
	}
		
	/**
	 * add Default columns
	 * @param string $sTableAlias
	 * @param Ext_Gui2_Head $oColumnGroup 
	 */
	public function addDefaultColumns($sTableAlias='', $oColumnGroup = NULL) {

		
		$oColumn				= $this->createColumn();
		$oColumn->db_column		= 'intern_name';
		$oColumn->db_alias		= $sTableAlias;
		$oColumn->title			= L10N::t('Interne Bezeichnung');
		$oColumn->width			= Ext_TC_Util::getTableColumnWidth('name');
		$oColumn->width_resize	= true;
		$this->setColumn($oColumn);
		
		$oColumn				= $this->createColumn();
		$oColumn->db_column		= 'section';
		$oColumn->db_alias		= $sTableAlias;
		$oColumn->title			= L10N::t('Bereich');
		$oColumn->width			= Ext_TC_Util::getTableColumnWidth('name');
		$oColumn->width_resize	= false;
		$oColumn->format		= new Ext_Gui2_View_Format_Selection($this->getDesignSections());
		$this->setColumn($oColumn);
		
		parent::addDefaultColumns($sTableAlias, $oColumnGroup);
	}
	
	
	/**
	 * get the Design Dialog
	 * @param type $bNew
	 * @return type 
	 */
	protected function getDialog($bNew = true){
		
		// Section Mapping Data
		$aSections = $this->getDesignSections();
		$aSections = Ext_TC_Util::addEmptyItem($aSections);
		
		// Get Factory
		$oFactory = $_SESSION['Gui2Designer']['factory'];
		
		// Create Dialog
		$oDialog = $this->createDialog($this->t('Design editieren'), $this->t('Design anlegen'));
		$oDialog->width = 1500;
		
		// Tab Einstellungen
		$oTab = $oDialog->createTab($this->t('Einstellungen'));
		$oRow = $oDialog->createRow(
				$this->t('Bereich'), 
				'select', 
				array(
					'db_alias' => '', 
					'db_column' => 'section', 
					'required' => 1, 
					'select_options' => $aSections,
					'events' => array(
							array('event' => 'change', 'function' => 'reloadCurrentDialogTab', 'parameter' => array(1,2))
					)
				)
			);
		$oTab->setElement($oRow);
		$oRow = $oDialog->createRow(
				$this->t('Interne Bezeichnung'), 
				'input', 
				array(
					'db_alias' => '', 
					'db_column' => 'intern_name', 
					'required' => 1
				)
			);
		$oTab->setElement($oRow);
		$oRow = $oDialog->createI18NRow(
					$this->t('Dialog Titel - Neu'), 
					array(
						'db_alias' => 'dialog_i18n',
						'db_column'=> 'title_new',
						'i18n_parent_column' => 'design_id'
					), 
					$oFactory->aLanguages
				);
		$oTab->setElement($oRow);
		$oRow = $oDialog->createI18NRow(
					$this->t('Dialog Titel - Editieren'), 
					array(
						'db_alias' => 'dialog_i18n',
						'db_column'=> 'title_edit',
						'i18n_parent_column' => 'design_id'
					), 
					$oFactory->aLanguages
				);
		$oTab->setElement($oRow);

		/*
		$oRow = $oDialog->createRow($this->t('Übersichtstab einblenden'), 'checkbox', array('db_alias' => '', 'db_column' => 'show_overview_tab'));
		$oTab->setElement($oRow);
		 */

		$oTab->aOptions['task'] = 'options';
		$this->setAdditionalTabRowsByRef($oTab, $oDialog);
		$oDialog->setElement($oTab);
		
		// Tab Layout
		$oTab = $oDialog->createTab($this->t('Dialog: Layout'));
		$oTab->aOptions['task'] = 'dialog_layout';
		$this->setAdditionalTabRowsByRef($oTab, $oDialog);
		$oDialog->setElement($oTab);

		$oDialog->access = array($this->getAccess(), 'edit');
		
		$this->setAdditionalTabsByRef($oDialog);
		
		return $oDialog;
	}

	protected function getCopyDialog() {

		$oDialog = $this->createDialog($this->t('Duplizieren'), $this->t('Duplizieren'));
		$oDialog->sDialogIDTag = 'COPY_';

		$oTab = $oDialog->createTab($this->t('Einstellungen'));

		$oTab->setElement($oDialog->createRow(
			$this->t('Interne Bezeichnung'),
			'input',
			array(
				'db_alias' => '',
				'db_column' => 'intern_name',
				'required' => 1
			)
		));

		$oTab->aOptions['task'] = 'copy_options';
		$this->setAdditionalTabRowsByRef($oTab, $oDialog);

		$oDialog->setElement($oTab);

		return $oDialog;
	}

	/**
	 * Set default bars
	 */
	protected function setDefaultBars(){
		
		$sAccess = $this->getAccess();
		
		// get Dialog
		$oDialogNew = $this->getDialog();
		$oDialogEdit = $this->getDialog(false);
		$oDialogCopy = $this->getCopyDialog();

		// create ICON bar
		$oBar = $this->createBar();
		$oBar->width = '100%';

		$oIcon = $oBar->createNewIcon($this->t('Neuer Eintrag'), $oDialogNew, $this->t('Neuer Eintrag'));
		$oIcon->access = array($sAccess, 'new');
		$oBar->setElement($oIcon);

		$oIcon = $oBar->createEditIcon($this->t('Editieren'), $oDialogEdit, $this->t('Editieren'));
		$oIcon->access = array(
			array($sAccess, 'show'),
			array($sAccess, 'edit')
		);
		$oBar->setElement($oIcon);

		$oIcon = $oBar->createDeleteIcon($this->t('Löschen'), $this->t('Löschen'));
		$oIcon->access = array($sAccess, 'delete');
		$oBar->setElement($oIcon);

		$oIcon = $oBar->createIcon('fa-copy', 'openDialog', $this->t('Duplizieren'));
		$oIcon->access = array($sAccess, 'copy');
		$oIcon->label = $oIcon->title;
		$oIcon->action = 'edit';
		$oIcon->additional = 'copy';
		$oIcon->dialog_data = $oDialogCopy;
		$oBar->setElement($oIcon);

		$oLoading = $oBar->createLoadingIndicator();
		$oBar->setElement($oLoading);

		$this->setBar($oBar);
		
		
	}
	
	// get Sections
	public function getDesignSections(){
		$oFactory = $_SESSION['Gui2Designer']['factory'];
		return $oFactory->getSections();
	}
	
	
}
