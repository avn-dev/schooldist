<?php


class Ext_TC_Flexible_Option_Gui2 extends Ext_TC_Gui2_Data{
 
	
//
//	protected function saveDialogData($sAction, $aSelectedIds, $aData, $sAdditional=false) {
//		global $_VARS;
//
//		if($aSelectedIds == NULL){
//			$aSelectedIds = array();
//		}
//		$iSelectedId = (int)reset($aSelectedIds);
//		$iParentId = (int)reset($_VARS['parent_gui_id']);
//
//
//		$oFieldOption = Ext_Thebing_Flexible_Option::getInstance($iSelectedId);
//
//
//		$oFieldOption->title = $_VARS['save']['title'];
//		$oFieldOption->field_id = (int)$iParentId;
//		$oFieldOption->save();
//
//		$iSelectedId = $oFieldOption->id;
//
//		foreach((array)$_VARS['save'] as $sKey => $sValue){
//			if(strpos($sKey, '_title') !== false){
//				$aTemp = explode('_', $sKey);
//				$oFieldOption->saveTitle($aTemp[0], $sValue);
//			}
//		}
//
//		$aTransfer = array();
//		//$aTransfer['action'] = 'closeDialogAndReloadTable';
//		$aTransfer['action'] = 'saveDialogCallback';
//		$aTransfer['error'] = array();
//		$aTransfer['success'] = 1;
//		//$aTransfer['message'] = L10N::t('New password successfully send!');
//
//		$aTransfer['dialog_id_tag'] = 'ID_';
//
//		return $aTransfer;
//	}



}