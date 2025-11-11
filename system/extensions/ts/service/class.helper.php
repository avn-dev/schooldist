<?php

class Ext_TS_Service_Helper
{
	/**
	 *
	 * Sortieren der Services nach Leistungsbeginn
	 * 
	 * @param array $aServiceA
	 * @param array $aServiceB
	 * @return int 
	 */
	public static function sortServices($mServiceA, $mServiceB)
	{
		$iCompare = 0;

		if(is_object($mServiceA))
		{
			$sFromA = $mServiceA->getFrom();
		}
		else
		{
			$sFromA = $mServiceA['from'];
		}
		
		if(is_object($mServiceB))
		{
			$sFromB = $mServiceB->getFrom();
		}
		else
		{
			$sFromB = $mServiceB['from'];
		}

		if(!empty($sFromA) && !empty($sFromB))
		{
			$oDate	= new WDDate($sFromA, WDDate::DB_DATE);

			$iCompare = $oDate->compare($sFromB, WDDate::DB_DATE);	
		}

		return $iCompare;
	}
}