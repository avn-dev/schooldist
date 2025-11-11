<?php

class Ext_TC_Gui2_Design_Tab_Element_Gui2_Data extends Ext_TC_Gui2_Data
{
			
	public function switchAjaxRequest($_VARS) {
						
		if($_VARS['task'] == 'moveAllElements'){
				
			$aTransfer			= array();
			$aTransfer['error'] = array();
			$aElementList		= $_VARS['element'];

			foreach((array)$aElementList as $iParentId => $aColumnData){
				
				// #2093 wurde aus ihrgendeinem grund eine weitere "0" Ebene mit falschen(nicht vorhandenen) IDs übermittelt
				// was fehler verursachte
				//
				// Deswegen lassen sich aber jetzt keine Elemente in der ersten Ebene mehr verschieben. R-#4957
//				if($iParentId <= 0){
//					continue;
//				}
				
				$iPosition = 0;
				
				foreach((array)$aColumnData as $iColumn => $aElements){
					
					foreach((array)$aElements as $iElement) {

						$oElement							= Ext_TC_Gui2_Design_Tab_Element::getInstance($iElement);

						// Prüfen, ob Element vorkommt
						if($oElement instanceof Ext_TC_Gui2_Design_Tab_Element) {

							$bValid								= $oElement->checkParent($iParentId);

							if($bValid){

								$oElement->position					= $iPosition;
								$oElement->parent_element_id		= (int)$iParentId;
								$oElement->parent_element_column	= (int)$iColumn;
								$oElement->save();

								$iPosition++;

							} else {

								$oParentElement	= Ext_TC_Gui2_Design_Tab_Element::getInstance($iParentId);

								$sError = $this->t('{element} kann nicht in {parent_element} verschoben werden');
								$sError = str_replace(array('{element}', '{parent_element}'), array($oElement->getName(), $oParentElement->getName()), $sError);
								$aTransfer['error'][] = $sError;

							}
							
						}
					
					}
					
				}
				
			}
		
			if(empty($aTransfer['error'])){
				$aTransfer['action']			= 'closeDialogAndReloadTable';
				$aTransfer['dialog_id_tag']		= 'ID_';
				$aTransfer['save_id']			= 0;
				$aTransfer['data']				= array('id' => 'ID_0');

			} else {
				$aTransfer['action'] 	= 'showError';
			}

			echo json_encode($aTransfer);
			
		} else {
			parent::switchAjaxRequest($_VARS);
		}
	}


	/**
	 * See parent
	 */
	public function prepareOpenDialog($sIconAction, $aSelectedIds, $iTab=false, $sAdditional=false, $bSaveSuccess = true) {
		global $_VARS;
		
		// WDBasic Instanz aufbauen, falls nicht vorhanden
		if(
			is_null($this->oWDBasic) ||
			!($this->oWDBasic instanceof WDBasic)
		) {
			$this->_getWDBasicObject($aSelectedIds);
		}
		
		if(
			$_VARS['element_hash'] || 
			$_VARS['parent_element_id'] || 
			$_VARS['parent_element_column']
		){

			// Wenn elemente übergeben wurde, sortiere
			if($_VARS['element']){
				$iPosition = 0;
				// Such die Position des elementes
				foreach((array)$_VARS['element'] as $iSortElement){
					if(!is_numeric($iSortElement)){
						break;
					}
					$iPosition++;
				}
				// Setzt die Position ( die anderen werden beim speichern neu positioniert )
				$this->oWDBasic->position = $iPosition;
				// Werte merken um beim speichern alle andere Elemente zu sortieren
				$this->_oGui->setOption('current_element_sort', $_VARS['element']);
			}

		}

		$oTab = Ext_TC_Gui2_Design_Tab::getInstance($this->oWDBasic->tab_id);

		$iDesignId = $oTab->design_id;

		$sDesignerClass = Ext_TC_Factory::getClassName('Ext_TC_Gui2_Designer');
		$oDesigner = new $sDesignerClass($iDesignId);

		// Section setzen
		$this->oWDBasic->designer_section = $oDesigner->getDesign()->section;

		if($_VARS['element_hash']){
			$this->oWDBasic->element_hash = $_VARS['element_hash'];
		}
		if($_VARS['parent_element_id']){
			$this->oWDBasic->parent_element_id = $_VARS['parent_element_id'];
		}
		if(!empty ($_VARS['parent_element_column'])){
			$this->oWDBasic->parent_element_column = $_VARS['parent_element_column'];
		}

		$aData = parent::prepareOpenDialog($sIconAction, $aSelectedIds, $iTab, $sAdditional, $bSaveSuccess);

		if($_VARS['element_hash']){
			$aData['element_hash'] = $_VARS['element_hash'];
		}
		
		$aElementList = $oDesigner->getFullElementArray(false, true);
		$aData['element_data'] = $aElementList;

		return $aData;
		
	}

	/**
	 * See parent
	 */
	protected function saveDialogData($sAction, $aSelectedIds, $aData, $sAdditional=false, $bSave=true)
	{
		$aSelectedIds = (array)$aSelectedIds;

		$aTransfer = parent::saveDialogData($sAction, $aSelectedIds, $aData, $sAdditional, $bSave);

		switch($sAction)
		{
			case 'new':
			case 'edit':
			{
				if(
					empty($aSelectedIds) ||
					$aSelectedIds[0] <= 0 ||
					!is_numeric($aSelectedIds[0])
				){
					$aTransfer['data']['id']		= 'ID_0';
				}
				
				$aSort = $this->_oGui->getOption('current_element_sort');
				$iSort = 0;
				foreach((array)$aSort as $iSortElement){
					if(is_numeric($iSortElement)){
						$oSortElement = Ext_TC_Gui2_Design_Tab_Element::getInstance($iSortElement);
						$oSortElement->position = $iSort;
						$oSortElement->save();
					}
					$iSort++;
				} 
				
				$aTransfer['action']			= 'closeDialogAndReloadTable';
				break;
			}
		}

		return $aTransfer;
	}
	
	protected function saveEditDialogData(array $aSelectedIds, $aSaveData, $bSave = true, $sAction = 'edit', $bPrepareOpenDialog = true) {
		global $_VARS;

		$bValid = true;
		
		if(
			$aSaveData['element_hash'] != ""
		){

			$aParents		= (array)$_VARS['parent_gui_id'];
			$iParent		= reset($aParents);

			if($this->oWDBasic){
				$this->_getWDBasicObject($aSelectedIds);
			}

			// @TODO Keine Ahnung, was hier passiert, aber das verursacht eine Warnung ab PHP 5.4,
			// 	da oWDBasic null ist. Ruft man aber _getWDBasicObject() generell auf, gibt es auch eine Exception.
			if($this->oWDBasic instanceof WDBasic) {
				$this->oWDBasic->tab_id = $iParent;
			}

			$oTab = Ext_TC_Gui2_Design_Tab::getInstance($iParent);

			$sDesignerClass = Ext_TC_Factory::getClassName('Ext_TC_Gui2_Designer');
			$oDesigner		= new $sDesignerClass($oTab->design_id);

			// Section setzen
			if($this->oWDBasic instanceof WDBasic) {
				$this->oWDBasic->designer_section = $oDesigner->getDesign()->section;
			}

			$oDesignElement = $oDesigner->findElementWithHash($aSaveData['element_hash']);

			if($oDesignElement instanceof Ext_TC_Gui2_Design_Tab_Element) {
				$oDesignElement->designer_section = $oDesigner->getDesign()->section;
				$bValid = $oDesignElement->checkParent($aSaveData['parent_element_id']);
			}

		}
		
		if($bValid){
			return parent::saveEditDialogData($aSelectedIds, $aSaveData, $bSave, $sAction, $bPrepareOpenDialog);
		} else {
			$aTransfer = [];
			$aTransfer['action'] = 'showError';
			$aTransfer['error'] = [$this->t('Das Element kann nicht an diese Stelle gesetzt werden')];
			return $aTransfer;
		}
	}


	
}
