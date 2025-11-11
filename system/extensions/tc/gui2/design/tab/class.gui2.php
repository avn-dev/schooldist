<?php

class Ext_TC_Gui2_Design_Tab_Gui2 extends Ext_TC_Gui2_Design_Gui2_Basic {

	/**
	 * get uniq hash!
	 * WARNING! Do not change that hash!
	 * The hash string is used in the JS File
	 * @return type 
	 */
	public function getHash(){
		$sHash = md5('core_gui2_design_tab');
		return $sHash;
	}
	
	/**
	 * Set all basic GUI Informations
	 */
	public function setConstructData(){
		$this->setWDBasic('Ext_TC_Gui2_Design_Tab');
		$this->multiple_selection = 0;
		$this->class_js = 'DesignerGui';
		$this->access = array('core_gui2_designer', '');
	}
	
	/**
	 * get the Design Dialog
	 * @param type $bNew
	 * @return type 
	 */
	protected function getDialog($bNew = true){
		
		$oFactory = $_SESSION['Gui2Designer']['factory'];
		
		$oDialog = $this->createDialog($this->t('Reiter editieren'), $this->t('Reiter anlegen'));
		$oTab = $oDialog->createTab($this->t('Einstellungen'));

		$oTab->setElement(
				$oDialog->createI18NRow(
						$this->t('Name'), 
						array(
							'db_alias' => 'i18n',  
							'db_column'=> 'name', 
							'i18n_parent_column' => 'tab_id'
							), 
						$oFactory->aLanguages
						)
				);
		$this->setAdditionalTabRowsByRef($oTab, $oDialog);
		$oDialog->setElement($oTab);

		
		$oDialog->access = array($this->getAccess(), 'edit');
				
		$this->setAdditionalTabsByRef($oDialog);
		
		return $oDialog;
	}
	

	protected function setDefaultBars(){
		
		$oFactory = $_SESSION['Gui2Designer']['factory'];
		
		$sAccess = $this->getAccess();
		
		$oDialogNew = $this->getDialog();
		$oDialogEdit = $this->getDialog(false);
		
		
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
		$oLoading = $oBar->createLoadingIndicator();
		$oBar->setElement($oLoading);
		$this->setBar($oBar);
		
		$this->addLanguageColumns($oFactory->aLanguages, 'name', 'i18n', $this->t('Name'));

		
	}
		
}