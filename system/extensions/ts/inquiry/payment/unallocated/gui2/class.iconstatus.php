<?php

class Ext_TS_Inquiry_Payment_Unallocated_Gui2_IconStatus extends Ext_Gui2_View_Icon_Active
{
	public function getStatus(&$aSelectedIds, &$aRowData, &$oElement)
	{
		if (
			$oElement->additional === 'allocate' &&
			collect($aRowData)->first()['status'] !== \Ext_Thebing_Inquiry_Payment::STATUS_PAID
		) {
			return false;
		}

		return true;
	}
}