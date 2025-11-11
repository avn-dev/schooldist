<?php

class Ext_Multi_Entry_Gui2_Format_Validity extends Ext_Gui2_View_Format_Abstract
{
	public function format($mValue, &$oColumn = null, &$aResultData = null)
	{
		$sReturn = '';

		try
		{
			$oDate = new WDDate($aResultData['validfrom'], WDDate::DB_DATETIME);
			$iFrom = $oDate->get(WDDate::TIMESTAMP);

			$oDate = new WDDate($aResultData['validuntil'], WDDate::DB_DATETIME);
			$iTill = $oDate->get(WDDate::TIMESTAMP);
		}
		catch(Exception $e)
		{
			$iFrom = $iTill = false;
		}

		if($iFrom !== false && $iTill === false)
		{
			if($iFrom <= time())
			{
				$sReturn = date('d.m.Y', $iFrom) . ' -';
			}
			else
			{
				$sReturn = '<span style="color:red;">' . date('d.m.Y', $iFrom) . ' -</span>';
			}
		}
		else if($iFrom === false && $iTill !== false)
		{
			if($iTill > time())
			{
				$sReturn = '- ' . date('d.m.Y', $iTill);
			}
			else
			{
				$sReturn = '<span style="color:red;">-' . date('d.m.Y', $iTill) . '</span>';
			}
		}
		else if($iFrom !== false && $iTill !== false)
		{
			if($iFrom <= time() && $iTill >= time())
			{
				$sReturn = date('d.m.Y', $iFrom) . ' - ' . date('d.m.Y', $iTill);
			}
			else
			{
				$sReturn = '<span style="color:red;">' . date('d.m.Y', $iFrom) . ' - ' . date('d.m.Y', $iTill) . '</span>';
			}
		}

		return $sReturn;
	}
}