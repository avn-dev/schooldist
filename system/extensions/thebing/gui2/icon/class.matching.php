<?php

class Ext_Thebing_Gui2_Icon_Matching extends Ext_Gui2_View_Icon_Abstract {

	public function getStatus(&$aSelectedIds, &$aRowData, &$oElement) {

		$aData = reset($aRowData);

		if(
			$oElement->action == 'matching_cut' &&
			count($aSelectedIds) > 0 &&
			(int)$aData['payment_id'] <= 0 // Nur wenn keine Bezahlung vorliegt
		) {
			// #12767: Dialog funktioniert bei mehr als einer aktiven Zuweisung nicht
			if(!empty($aData['allocated_room_ids'])) {
				$aAllocationIds = explode(',', $aData['allocated_ids']);
				if(count($aAllocationIds) > 1) {
					return false;
				}
			}

			return true;
		}
		
		if(
			$oElement->action === null || # Kein Dialog der über ICON aufgerufen wird
			$oElement->task === 'export_csv' ||
			$oElement->task === 'export_excel' ||
			$oElement->action === 'overview' ||
			$oElement->action === 'availability' ||
			$oElement->task === 'requestAsUrl'
		) {
			return 1;
		}

		return 0;
	}

}
