<?php

class Ext_TC_Frontend_Extrafield_Gui2_Data extends Ext_TC_Gui2_Data {	

	/**
	 * Methode, um dynamisch einen objektabhängigen Dialog zu setzen
	 * 
	 * @param string $sIconAction
	 * @param array $aSelectedIds
	 * @param int $iTab
	 * @param string $sAdditional
	 * @param boolean $bSaveSuccess
	 * @return array 
	 */
	public function prepareOpenDialog($sIconAction, $aSelectedIds, $iTab=false, $sAdditional=false, $bSaveSuccess = true) {

		if(
			$sIconAction == 'new' || 
			$sIconAction == 'edit'
		) {

			$sIconKey = self::getIconKey($sIconAction, $sAdditional);

			// Falls noch kein WDBasic-Objekt gesetzt wurde
			if(!$this->oWDBasic){
				$this->_getWDBasicObject($aSelectedIds);
			}

			//Dialog-Objekt holen
			$oDialog = $this->getGuiDialog();

			//Dialog für $sIconKey setzen
			$this->aIconData['new']['dialog_data'] = $oDialog;
			$this->aIconData['edit']['dialog_data'] = $oDialog;

		}

		$aData = parent::prepareOpenDialog($sIconAction, $aSelectedIds, $iTab, $sAdditional, $bSaveSuccess);
		
		if(
			$sIconAction == 'new' || 
			$sIconAction == 'edit'
		) {
			
			$aSubObjects = Ext_TC_Factory::executeStatic('Ext_TC_Object', 'getSubObjects', array(false));

			foreach($aSubObjects as $oObject){
				$aLangs = $oObject->getLanguages();
				$aOfficeLangs[] = array(
					'object_id' => $oObject->id,
					'langs'		=> $aLangs
				);
			}
			
			$aData['object_langs'] = $aOfficeLangs;
		}
		
		return $aData;

	}
	
	/**
	 * Dialog-Objekt der Liste "Extrafelder"
	 * @return object Ext_Gui2_Dialog
	 */
	
	public function getGuiDialog(){

		/**
		 * Daten vorbereiten 
		 */
		
		$sSubObjectLabel = Ext_TC_Factory::executeStatic('Ext_TC_Object', 'getSubObjectLabel');

		/**
		 * DIALOG Objekt aufbauen 
		 */
		
		$oDialog = $this->_oGui->createDialog($this->t('Extra Feld "{name}" editieren'), $this->t('Extra Feld anlegen'));
		$oDialog->height = '500';
		
		$oDialog->setElement($oDialog->createRow($this->t('Name'), 'input', array(
			'db_alias' => 'tc_fc',
			'db_column' => 'name',
			'required' => 1
		)));

		$oJoinContainer = $oDialog->createJoinedObjectContainer('content', array('min' => 1, 'max' => 20));

		$oJoinContainer->setElement($oJoinContainer->createRow($this->t($sSubObjectLabel), 'select', array(
			'db_alias' => 'tc_fcc',
			'db_column' => 'objects',
			'multiple' => 5, 
			'jquery_multiple' => 1,
			'selection' => new Ext_TC_Frontend_Extrafield_Selection_SubObjects(),
			'required' => 1,
			'searchable' => 1,
			'dependency' => array(
				array(
					'db_alias' => 'tc_fcc',
					'db_column' => 'objects'
				)
			)
		)));

		$oJoinContainer->setElement($oJoinContainer->createRow($this->t('Art des Inhaltes'), 'select', array(
			'db_alias' => 'tc_fcc',
			'db_column' => 'type',
			'select_options' => Ext_TC_Extrafield::getFieldList(true),
			'class' => 'content_type',
			'required' => true
		)));
		
		$oJoinContainer->setElement($oDialog->createI18NRow($this->t('Inhalt'), array(
			'db_alias' => 'tc_fcc_i18n',
			'db_column'=> 'content',
			'i18n_parent_column' => 'content_id',	
			'joined_object_key' => 'content',
			'required' => false, // darf nicht required sein da sprachfelder ein/ausgeblendet werden!
		)));

		$oDialog->setElement($oJoinContainer);
		
		return $oDialog;
		
	}
	
	
}