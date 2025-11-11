<?php


class Ext_Thebing_Gui2_Format_Date_Withday extends Ext_Gui2_View_Format_Date_Time
{
	protected $_sFormat = '%a';

	public function __construct($sFormat=false)
	{
		if($sFormat){
			$this->_sFormat = $sFormat;
		}
	}

	public function format($mValue, &$oColumn = null, &$aResultData = null)
	{
		$sReturn = '';

		$oFormatDate = new Ext_Thebing_Gui2_Format_Date();
		$sDate = $oFormatDate->format($mValue, $oColumn, $aResultData);
		if(!empty($sDate))
		{
			$sDay = strftime($this->_sFormat, strtotime($mValue));
			$sReturn = $sDate.' '.$sDay;
		}

		return $sReturn;
	}

}