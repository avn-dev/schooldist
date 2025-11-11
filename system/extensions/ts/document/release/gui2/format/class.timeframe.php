<?php


class Ext_TS_Document_Release_Gui2_Format_TimeFrame extends Ext_Thebing_Gui2_Format_Date
{	
	public function format($mValue, &$oColumn = null, &$aResultData = null)
	{
		if($aResultData['type'] == 'manual_creditnote')
		{
			// Datum wurde bei manuellen Creditnotes gefaked, den Wert hier resetten damit nichts angezeigt wird
			
			return '';
		}
		else
		{
			$mValue = parent::format($mValue, $oColumn, $aResultData);
			
			return $mValue;
		}
	}
}