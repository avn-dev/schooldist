<?php


class Ext_TS_Gui2_Format_Amount extends Ext_Gui2_View_Format_Abstract
{
	public function format($mValue, &$oColumn = null, &$aResultData = null)
	{
		
		$fAmount = Ext_Thebing_Format::Number($mValue);
		
		if(isset($aResultData['currency_iso']))
		{
			$sIso = $aResultData['currency_iso'];
			
			$oCurrency = Ext_TC_Currency::getInstance($sIso); 
			
			// Dollar7Pfund soll IMMER vor der Zahl stehen
			if($sIso == 'USD' || $sIso == 'GBP')
			{
				$mBack = $oCurrency->getSign() . " " . $fAmount;
			}
			else
			{
				$mBack = $fAmount." ".$oCurrency->getSign();
			}
		}
		else
		{
			$mBack = $fAmount;
		}
		
		return $mBack;
		
	}
	
	public function align(&$oColumn = null)
	{
		return 'right';
	}
}