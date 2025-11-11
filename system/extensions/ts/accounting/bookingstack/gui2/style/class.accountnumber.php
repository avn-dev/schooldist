<?php


class Ext_TS_Accounting_BookingStack_Gui2_Style_AccountNumber extends Ext_Gui2_View_Style_Abstract
{
	public function getStyle($mValue, &$oColumn, &$aRowData)
	{
		$sStyle = '';

		$sColumn = $oColumn->db_column;
		
		$sColumnAutomaticAccount = str_replace('account_number', 'automatic_account', $sColumn);

		if(isset($aRowData[$sColumnAutomaticAccount]))
		{
			if($aRowData[$sColumnAutomaticAccount] == 1)
			{
				$sStyle .= 'background-color:' . Ext_Thebing_Util::getColor('green');
			}
		}

		return $sStyle;
	}
}