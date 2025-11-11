<?php

class Ext_TS_Inquiry_Group_Gui2_Icon extends Ext_Gui2_View_Icon_Abstract {

	public function getStatus(&$aSelectedIds, &$aRowData, &$oElement) {

		if(
			$oElement->task == 'deleteRow' &&
			$oElement->action == ''
		) {
			if(empty($aSelectedIds)) {
				return 0;
			}
			
            $aSelectedIds = (array)$aSelectedIds;
			$iSelectedId = (int)reset($aSelectedIds);
            $aInquiries = Ext_Thebing_Inquiry_Group::getInquiriesOfGroup($iSelectedId);
            foreach($aInquiries as $oInquiry){
                if(
					$oInquiry->has_invoice || 
					$oInquiry->has_proforma
				) {
                    return 0;
                }
            }
			return 1;

		} elseif($oElement->action == 'edit') {
			if(empty($aSelectedIds)) {
				return 0;
			}
		}

		return 1;
	}

}