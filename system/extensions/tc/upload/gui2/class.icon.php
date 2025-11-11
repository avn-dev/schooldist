<?php

class Ext_TC_Upload_Gui2_Icon extends Ext_Gui2_View_Icon_Abstract {

	public function getStatus(&$aSelectedIds, &$aRowData, &$oElement) {

		// Löschen nur erlauben, wenn Datei nicht mehr verwendet wird
		if(
			$oElement->task == 'deleteRow' &&
			count($aSelectedIds) > 0
		) {

			$iSelectedId = (int)reset($aSelectedIds);

			if($iSelectedId > 0){
				$oUpload = Ext_TC_Upload::getInstance($iSelectedId);

				// Verwendungszweck
				$aUsage = $oUpload->getUsage();

				if(!empty($aUsage)){
					return 0;
				}
			}

		} 

		if(count($aSelectedIds) > 0) {
			return 1;
		}

		return $oElement->active;
		
	}

}