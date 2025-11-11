<?php

/**
 * Modulverwaltung GUI2 Ableitung
 */
class Ext_TC_User_Group_Gui2 extends Ext_TC_Gui2_Data {
	
	protected function saveDialogData($sAction, $aSelectedIds, $aData, $sAdditional=false, $bSave=true){
		global $_VARS;
		
		$aTransfer = array();

		/**
		 * Switch standard actions
		 * Bei neuem Eintrag werden die Selected IDs zurückgesetzt
		 */
		switch($sAction) {
			case 'new':
				// Ein neuer Eintrag darf die ID nicht vorselektiert haben
				$aSelectedIds = array();
			case 'edit':
				$aTransfer = $this->saveEditDialogData((array)$aSelectedIds, $aData, $bSave, $sAction);
				break;
			case 'access':
				
				$iGroupID = reset($aSelectedIds);
				$aAccessList = array();
				$oGroup = Ext_TC_User_Group::getInstance($iGroupID);
				foreach((array)$_VARS['access'] as $iCategory => $aData){
					foreach((array)$aData as $iSection => $aAccess){
						foreach((array)$aAccess as $iAccess){
							$aAccessList[] = $iAccess;
						}
					}
				}
				$oGroup->access = $aAccessList;
				$oGroup->save();
				
				Ext_TC_User::resetAccessCache();
				
				$aData = $this->prepareOpenDialog($sAction, $aSelectedIds);
				$aTransfer					= array();
				$aTransfer['action'] 		= 'saveDialogCallback';
				$aTransfer['dialog_id_tag']	= 'ACCESS_';
				$aTransfer['error'] 		= array();
				$aTransfer['data'] 			= $aData;
				$aTransfer['save_id'] 		= reset($aSelectedIds);
				break;
		}

		return $aTransfer;
	}
	
		/**
	   Erzeugt ein Array mit den HTML und Tab Daten
	 *
	 * @param type $sIconAction
	 * @param Ext_Gui2_Dialog $oDialogData
	 * @param type $aSelectedIds
	 * @param type $sAdditional
	 * @return type 
	 */
	protected function getDialogHTML(&$sIconAction, &$oDialogData, $aSelectedIds = array(), $sAdditional=false){
		
		if($sIconAction == 'access'){

			$aSelectedIds = (array)$aSelectedIds;
			$iGroup = reset($aSelectedIds);
			$oGroup = Ext_TC_User_Group::getInstance($iGroup);
			$aSavedAccess = $oGroup->access;
			$oDialogData = Ext_TC_User_Gui2::getAccessDialog($this->_oGui, $aSavedAccess, true);
		}
		
		$aData = $oDialogData->generateAjaxData($aSelectedIds, $this->_oGui->hash);
		return $aData;
	}
	
	/**
	 * Das Modul aus der Zwischentabelle entfernen
	 * @param int $iRowId 
	 */
	protected function deleteRowHook($iRowId)
	{
		
		$aSql = array(
			'row_id' => $iRowId
		);
		
		$sSql = "
		DELETE FROM
			`tc_system_user_groups_to_access`
		WHERE
			`group_id` = :row_id
		";
		
		DB::executePreparedQuery($sSql, $aSql);
		
	}
}
?>
