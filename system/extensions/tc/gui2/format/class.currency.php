<?php


class Ext_TC_Gui2_Format_Currency extends Ext_TC_Gui2_Format
{
	public function format($mValue, &$oColumn = null, &$aResultData = null)
	{
		$sReturn	= null;
		
		$oCurrency	= new Ext_TC_Currency();
		
		$aCurrency	= $oCurrency->getArrayList2(true, 'sign');
		
		if(isset($aCurrency[$mValue]))
		{
			$sReturn = $aCurrency[$mValue];
		}

		return $sReturn;
		
	}
}