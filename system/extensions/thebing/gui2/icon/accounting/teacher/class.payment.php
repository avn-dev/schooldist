<?php

class Ext_Thebing_Gui2_Icon_Accounting_Teacher_Payment extends Ext_Gui2_View_Icon_Abstract {

	public function getStatus(&$aSelectedIds, &$aRowData, &$oElement) {
		global $_VARS;

		// No entries are selected
		if(empty($aSelectedIds))
		{
			// Return default value
			return $oElement->active;
		}

		if(
			$oElement->action == 'export_csv' ||
			$oElement->action == 'toggleMenu' ||
			$oElement->action == ''
		){
			return 1;
		}

		$oGui = Ext_Gui2::getClass($_VARS['hash'], $_VARS['instance_hash']);

		$bPayments = false;

		// Check the sum of payed amount
		foreach((array)$aSelectedIds as $iGuiId)
		{
			$aData = $oGui->decodeId($iGuiId);

			if($aData['payed_amount'] > 0)
			{
				$bPayments = true;
			}
		}

		// This is the payment button
		if(
			$oElement->action == 'teacher_payment' ||
			$oElement->action == 'accommodation_payment'
		)
		{
			if($bPayments)
			{
				return 0;
			}
			else
			{
				return 1;
			}
		}
		else // This is the delete button
		{
			if(!$bPayments)
			{
				return 0;
			}
			else
			{
				return 1;
			}
		}
	}
}