<?php

class Ext_TC_User_Gui2_Icon extends Ext_Gui2_View_Icon_Abstract {

	public function getStatus(&$aSelectedIds, &$aRowData, &$oElement) {
		global $user_data;

		if(!empty($aSelectedIds)) {
			if($oElement->task == 'deleteRow') {

				if(in_array($user_data['id'], $aSelectedIds)) {
					return 0;
				}

			} elseif(
				$oElement->task == 'request' &&
				$oElement->action == 'unblock'
			) {
				$iReturn = 1;
				foreach($aRowData as $aRow) {
					if($aRow['blocked'] == 0) {
						return 0;
					}
				}
			} elseif(
				$oElement->task == 'request' &&
				$oElement->action == 'remove-secret'
			) {
				$iReturn = 1;
				foreach($aRowData as $aRow) {
					if(empty($aRow['secret'])) {
						return 0;
					}
				}
			}
		} else {
			if($oElement->action == 'new') {
				return $oElement->active;	
			} else {
				return 0;
			}
		}

		return 1;

	}

}