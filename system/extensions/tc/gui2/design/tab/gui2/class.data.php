<?php
class Ext_TC_Gui2_Design_Tab_Gui2_Data extends Ext_TC_Gui2_Data
{
	
	public function switchAjaxRequest($_VARS) {		
		
		if($_VARS['task'] == 'moveTab'){
			
			$aTransfer = array();
			
			foreach((array)$_VARS['pages_tab'] as $iSortTab){
				
				if(
					is_numeric($iSortTab)
				){
					$oSortTab = Ext_TC_Gui2_Design_Tab::getInstance($iSortTab);
					$oSortTab->position = $iPosition;
					$oSortTab->save();
				}
				
				$iPosition++;
				
			}

			$aTransfer['action']			= 'closeDialogAndReloadTable';
			$aTransfer['dialog_id_tag']		= 'ID_';
			$aTransfer['save_id']			= 0;
			$aTransfer['data']				= array('id' => 'ID_0');
			$aTransfer['error']				= array();
			
			echo json_encode($aTransfer);
		} else {
			parent::switchAjaxRequest($_VARS);
		}
	}
	
	/**
	 * See parent
	 */
	protected function saveDialogData($sAction, $aSelectedIds, $aData, $sAdditional=false, $bSave=true)
	{
		global $_VARS;
		
		$aTransfer = parent::saveDialogData($sAction, $aSelectedIds, $aData, $sAdditional, $bSave);
		

		switch($sAction)
		{
			case 'new':
				$aTransfer['action']			= 'closeDialogAndReloadTable';
				$aTransfer['data']['id']		= 'ID_0';
				break;
			case 'edit':
			{
				$aTransfer['action']			= 'closeDialogAndReloadTable';
				break;
			}
		}
		

		return $aTransfer;
	}


	
}
