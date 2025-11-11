<?php

class Ext_TC_Gui2_Design_Gui2_Data extends Ext_TC_Gui2_Data
{
	
	public function switchAjaxRequest($_VARS) {
		
		$oFactory = &$_SESSION['Gui2Designer']['factory'];
		
		if(
			$_VARS['task'] == 'reloadDialogTab' &&
			!empty($_VARS['save']['section'])
		){
			$oFactory->sSection = $_VARS['save']['section'];
		}
		
		parent::switchAjaxRequest($_VARS);
	}
	
	/**
	 * See parent
	 */
	public function prepareOpenDialog($sIconAction, $aSelectedIds, $iTab = false, $sAdditional = false, $bSaveSuccess = true) {

		$aData = parent::prepareOpenDialog($sIconAction, $aSelectedIds, $iTab, $sAdditional, $bSaveSuccess);

		if(
			($sIconAction == 'new' || $sIconAction == 'edit') &&
			empty($sAdditional)
		) {

			$aSelectedIds = (array)$aSelectedIds;
			$iDesign = reset($aSelectedIds);

			$oDesign = $this->oWDBasic;

			if(
				!$oDesign ||
				$oDesign->id != $iDesign
			){
				$oDesign = Ext_TC_Gui2_Design::getInstance($iDesign);
			}

			if(isset($aData['tabs'][1])) {

				$sDesignerClass = Ext_TC_Factory::getClassName('Ext_TC_Gui2_Designer');
				/* @var Ext_TC_Gui2_Designer $oDesigner */
				$oDesigner = new $sDesignerClass($oDesign);

				$aData['tabs'][1]['html'] = $oDesigner->generateHtml();

				if($oDesign->section != '') {

					$oDesigner->setSection($oDesign->section);

					$aElementList = $oDesigner->getFullElementArray(false, true);
			
					$aCurrentElements = $oDesign->getTabElements();

					foreach((array)$aCurrentElements as $oElement){

						// Deisgner Element suchen für die Erlaubten Eltern Felder
						$oTempElement = $oElement->searchDesignerElement();
						// Element Daten setzten
						$aTemp = array();					
						// Hash setzten
						$aTemp['type'] = 'element_'.$oElement->type;
						// Hash setzten
						$aTemp['special_type'] = 'element_'.$oElement->special_type;
						// Hash setzten
						$aTemp['hash'] = 'element_'.$oElement->id;
						// Erlaubte Eltern setzten
						$aTemp['allowed_parent'] = $oTempElement->allowed_parent;
						// Daten setzten
						$aElementList[] = $aTemp;
					}
					// Element Daten übermitteln
					$aData['element_list_data'] = $aElementList; 

				}
				
			}

		}

		return $aData;
	}

	protected function saveEditDialogData(array $aSelectedIds, $aSaveData, $bSave = true, $sAction = 'edit', $bPrepareOpenDialog = true)
	{

		if (is_array($sAction) && $sAction['additional'] ===  'copy') {

			if (!$this->oWDBasic) {
				$this->getWDBasicObject($aSelectedIds);
			}

			\DB::begin('copy_design_dialog');

			try {

				$oCopy = $this->oWDBasic->createCopy();

				foreach($aSaveData as $sField => $mValue) {
					$oCopy->$sField = $mValue;
				}

				$oCopy->save();

				\DB::commit('copy_design_dialog');

			} catch (\Throwable $e) {
				\DB::rollback('copy_design_dialog');

				throw $e;
			}

			$aTransfer = [];
			$aTransfer['action'] = 'closeDialogAndReloadTable';
			$aTransfer['data']['id'] = 'COPY_'.implode('_', $aSelectedIds);

		} else {
			$aTransfer = parent::saveEditDialogData($aSelectedIds, $aSaveData, $bSave, $sAction, $bPrepareOpenDialog);
		}

		return $aTransfer;
	}

}
