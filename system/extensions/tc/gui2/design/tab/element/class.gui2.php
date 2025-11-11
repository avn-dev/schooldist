<?php

class Ext_TC_Gui2_Design_Tab_Element_Gui2 extends Ext_TC_Gui2_Design_Gui2_Basic {

	/**
	 * get uniq hash!
	 * WARNING! Do not change that hash!
	 * The hash string is used in the JS File
	 * @return type 
	 */
	public function getHash(){
		$sHash = md5('core_gui2_design_tab_element');
		return $sHash;
	}
	
	/**
	 * Set all basic GUI Informations
	 */
	public function setConstructData(){
		$this->setWDBasic('Ext_TC_Gui2_Design_Tab_Element');
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
		
		$oDialog = $this->createDialog($this->t('Element editieren'), $this->t('Element anlegen'));

		$oTab = $oDialog->createTab($this->t('Übersetzungen'));

		$oTab->setElement($oDialog->createI18NRow($this->t('Label'), [
			'db_alias' => 'i18n',
			'db_column'=> 'name',
			'i18n_parent_column' => 'element_id'
		], $oFactory->aLanguages));

		$this->setAdditionalTabRowsByRef($oTab, $oDialog);

		$oDialog->setElement($oTab);

		$oTab = $oDialog->createTab($this->t('Einstellungen'));

		//$oDesigner = new Ext_TC_Gui2_Designer(0);
		//$aElementList = $oDesigner->getElementArray();

		$oTab->setElement($oDialog->createRow($this->t('Elternelement'), 'select', [
			'db_column' => 'parent_element_id',
			'selection' => new Ext_TC_Gui2_Design_Tab_Selection_Element('content')
		]));

		$oSelection = new Ext_TC_Gui2_Design_Tab_Element_Selection_Parent_Column();
		$oTab->setElement($oDialog->createRow($this->t('Spalte im Eltern Element'), 'select', [
			'db_column' => 'parent_element_column',
			'select_options' => $oSelection->getDefaultOptions(),
			'selection' => $oSelection,
			'row_style' => 'display:none;',
			'dependency' => [
				['db_alias'=>'', 'db_column' => 'parent_element_id']
			]
		]));
		
		$oTab->setElement($oDialog->createRow($this->t('Art'), 'select', [
			'db_column' => 'element_hash',
			//'select_options' => $aElementList ,
			'selection' => new Ext_TC_Gui2_Design_Tab_Element_Selection_Type(),
			'required' => 1
		]));

		$oTab->setElement($oDialog->createRow($this->t('Pflichtfeld'), 'checkbox', [
			'db_column' => 'required',
			'row_style' => 'display:none;',
		]));

		$aDepTypes = Ext_TC_Gui2_Design_Tab_Element::getPlaceholderDependencyVisibility();
		$oTab->setElement($oDialog->createRow($this->t('Platzhalter'), 'input', [
			'db_column' => 'placeholder',
			'dependency_visibility' => [
				'db_column' => 'element_hash',
				'on_values' => $aDepTypes
			]
		]));
		
		$oNotification = $oDialog->createNotification($this->t('Platzhalter'), $this->t('Beschreibung'), 'info', [
			'row_id' => 'gui_designer_element_placeholder',
			'row_style' => 'display: none;'
		]);
		$oTab->setElement($oNotification);
		
		$oTab->setElement($oDialog->createRow($this->t('Spalten'), 'select', [
			'db_column' => 'column_count',
			'row_style' => 'display:none;',
			'select_options' => [1 => '1', 2 => '2']
		]));
		
		$oTab->setElement($oDialog->createRow('', 'hidden', [
			'db_column' => 'position',
			'row_style' => 'display:none;'
		]));

		$this->setAdditionalTabRowsByRef($oTab, $oDialog);

		$oDialog->setElement($oTab);
		
		$sHash = $this->getHash();
		$sAccess = $this->getAccess();
		
		$oTab = $oDialog->createTab($this->t('Dropdown - Auswahlmöglichkeiten'));
		if(!$bNew){
			
			$oInnerGui						= new Ext_TC_Gui2(md5('core_gui2_design_tab_element_selectoption'));
			$oInnerGui->parent_hash			= $sHash;
			$oInnerGui->parent_gui			= [$sHash];
			$oInnerGui->gui_description		= $oFactory->sL10NPath;
			$oInnerGui->gui_title			= $oInnerGui->t('Dopdownauswahlen');
			$oInnerGui->access				= [$sAccess, 'edit'];
			$oInnerGui->multiple_selection	= 0;
			$oInnerGui->row_sortable		= true;
			$oInnerGui->query_id_column		= 'id';
			$oInnerGui->foreign_key			= 'element_id';
			$oInnerGui->parent_primary_key	= 'id';
			$oInnerGui->i18n_languages		= $oFactory->aLanguages;
			$oInnerGui->setWDBasic('Ext_TC_Gui2_Design_Tab_Element_Selectoption');

			$oInnerDialog = $oInnerGui->createDialog($oInnerGui->t('Neue Auswahl'), $oInnerGui->t('Auswahl bearbeiten'));
			$oInnerHint = $oInnerDialog->createNotification($oInnerGui->t('Bitte beachte'), $oInnerGui->t('Der Suchstring, ist der Wert nach dem die Liste gefiltert wird. Dieser ist für den Benutzer nicht sichtbar'), 'hint');
			$oInnerDialog->setElement($oInnerHint);
			$oInnerDialog->setElement($oInnerDialog->createRow($this->t('Suchstring'), 'input', [
				'db_column' => 'value',
				'required' => true
			]));
			$oInnerHint = $oInnerDialog->createNotification($oInnerGui->t('Bitte beachte'), $oInnerGui->t('Die Beschreibung ist das was am Ende im Dropdown zu sehen ist'), 'hint');
			$oInnerDialog->setElement($oInnerHint);
			$oInnerDialog->setElement($oInnerDialog->createI18NRow($this->t('Bezeichnung'), [
				'db_alias' => 'i18n',
				'db_column'=> 'name',
				'i18n_parent_column' => 'option_id'
			], $oFactory->aLanguages));
			
			// Buttons
			$oBar			= $oInnerGui->createBar();

			$oIcon			= $oBar->createNewIcon($oInnerGui->t('Neuer Eintrag'), $oInnerDialog, $oInnerGui->t('Neuer Eintrag'));
			$oIcon->access = [$sAccess, 'new'];
			$oBar->setElement($oIcon);
			$oIcon			= $oBar->createEditIcon($oInnerGui->t('Editieren'), $oInnerDialog, $oInnerGui->t('Editieren'));
			$oIcon->access = [$sAccess, 'edit'];
			$oBar->setElement($oIcon);
			$oIcon			= $oBar->createDeleteIcon($oInnerGui->t('Löschen'), $oInnerGui->t('Löschen'));
			$oIcon->access = [$sAccess, 'delete'];
			$oBar->setElement($oIcon);
			$oLoading		= $oBar->createLoadingIndicator();
			$oBar->setElement($oLoading);
			$oInnerGui->setBar($oBar);
			
			$oInnerGui->addI18nColumns($oInnerGui->t('Bezeichnung'), [
				'join_table_key'	=> 'i18n',
				'join_table_field'	=> 'name',
				'width'				=> Ext_TC_Util::getTableColumnWidth('name'),
				'width_resize'		=> true
			]);
			
			$oInnerGui->addDefaultColumns();

			$oTab->setElement($oInnerGui);
		}	else {
			$oHint = $oFactory->getPleaseSaveHint();
			$oTab->setElement($oHint);
		}	
		
		$this->setAdditionalTabRowsByRef($oTab, $oDialog);
		$oDialog->setElement($oTab);

		$oDialog->access = [$this->getAccess(), 'edit'];
		
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
