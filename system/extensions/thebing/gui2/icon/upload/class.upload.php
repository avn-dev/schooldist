<?php

class Ext_Thebing_Gui2_Icon_Upload_Upload extends Ext_Gui2_View_Icon_Abstract {

	public function getStatus(&$aSelectedIds, &$aRowData, &$oElement) {

		// Löschen nur erlauben, wenn Datei nicht mehr verwendet wird
		if(
			$oElement->task == 'deleteRow' &&
			count($aSelectedIds) > 0
		) {

			$iSelectedId = (int)reset($aSelectedIds);

			if($iSelectedId > 0){
				$oUpload = Ext_Thebing_Upload_File::getInstance($iSelectedId);

				// Verwendungszweck
				$aUsage = $oUpload->getUsage();

				if(!empty($aUsage)){
					return 0;
				}
			}

		} else if(
			$oElement->action != 'new' &&
			count($aSelectedIds) <= 0
		) {
			return 0;
		}

		return 1;
		
	}

}