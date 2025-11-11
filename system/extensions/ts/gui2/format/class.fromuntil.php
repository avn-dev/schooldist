<?php


class Ext_TS_Gui2_Format_FromUntil extends Ext_Gui2_View_Format_Abstract
{
	protected $_sFromColumn;

	protected $_sUntilColumn;

	public function __construct($sFromColumn, $sUntilColumn)
	{
		$this->_sFromColumn = $sFromColumn;
		
		$this->_sUntilColumn = $sUntilColumn;
	}


	public function format($mValue, &$oColumn = null, &$aResultData = null)
	{
		if(
			isset($aResultData[$this->_sFromColumn]) && 
			isset($aResultData[$this->_sUntilColumn])
		)
		{
			$oFormatDate	= new Ext_Thebing_Gui2_Format_Date();
			
			$sFrom			= $oFormatDate->format($aResultData[$this->_sFromColumn]);
			
			$sUntil			= $oFormatDate->format($aResultData[$this->_sUntilColumn]);
			
			$mValue			= $sFrom . ' - ' . $sUntil;
		}
		
		return $mValue;
	}
}