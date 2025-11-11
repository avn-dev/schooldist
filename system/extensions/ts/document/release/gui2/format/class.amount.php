<?php


class Ext_TS_Document_Release_Gui2_Format_Amount extends Ext_Thebing_Gui2_Format_Amount
{
	public function format($mValue, &$oColumn = null, &$aResultData = null)
	{
		$iCurrencyId		= 0;
		
		$oDocument			= Ext_Thebing_Inquiry_Document::getInstance($aResultData['id']);
		$oManualCreditnote	= $oDocument->getManualCreditnote();
		
		if($oManualCreditnote)
		{
			$iCurrencyId	= $oManualCreditnote->currency_id;
		}
		else
		{
			$oInquiry		= $oDocument->getInquiry();
			
			if($oInquiry)
			{
				$iCurrencyId = $oInquiry->getCurrency();
			}
		}
		
		$aResultData['currency_id'] = $iCurrencyId;
		
		$mValue = parent::format($mValue, $oColumn, $aResultData);
		
		return $mValue;
	}
}