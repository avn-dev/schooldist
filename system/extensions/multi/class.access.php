<?php

class Ext_Multi_Access
{
	public static function saveAccess($iEntryID, $aAccess, $iAccess)
	{
		$sSQL = "DELETE FROM `multi_access` WHERE `access` = " . (int)$iAccess . " AND `multi_entry_id` = " . (int)$iEntryID;
		DB::executeQuery($sSQL);

		foreach((array)$aAccess as $sDB)
		{
			if(empty($sDB))
			{
				continue;
			}

			$aTemp = explode('_', $sDB);

			$sSQL = "
				REPLACE INTO `multi_access`
				SET
					`multi_entry_id`	= :iEntryID,
					`customer_db_id`	= :iDB_ID,
					`customer_group_id`	= :iGroupID,
					`access`			= :iAccess
			";
			$aSQL = array(
				'iEntryID'	=> $iEntryID,
				'iDB_ID'	=> $aTemp[0],
				'iGroupID'	=> $aTemp[1],
				'iAccess'	=> $iAccess
			);
			DB::executePreparedQuery($sSQL, $aSQL);
		}
	}


	public static function getAccess($iEntryID)
	{
		$sSQL = "
			SELECT *
			FROM `multi_access`
			WHERE `multi_entry_id` = " . (int)$iEntryID . "
		";
		$aResult = DB::getQueryData($sSQL);

		$aAccess = array();

		foreach((array)$aResult as $iKey => $aValue)
		{
			$aAccess[$aValue['access']][$aValue['customer_db_id']][$aValue['customer_group_id']] = true;
		}

		return $aAccess;
	}


	public static function getGroups($aGroups)
	{
		$aList = array();

		foreach((array)$aGroups as $sKey => $sValue)
		{
			$aTemp = explode('|', $sKey);

			$aList[$aTemp[0]][$aTemp[1]] = $sValue;
		}

		return $aList;
	}


	public static function checkAccess($sNoAccess, $sAccess)
	{
		global $user_data;

		$aAccess = array(0 => array(), 1 => array());

		$aTemp[0] = explode('|', $sNoAccess);
		$aTemp[1] = explode('|', $sAccess);

		for($i = 0; $i < 2; $i++)
		{
			foreach((array)$aTemp[$i] as $k => $v)
			{
				if(!empty($v))
				{
					$aAccess[$i][$v] = 1;
				}
			}
		}

		$aYesAccess	= array_intersect_key((array)$aAccess[1],(array)$user_data['access']);
		$aNoAccess	= array_intersect_key((array)$aAccess[0],(array)$user_data['access']);

		if(
			((count($aAccess[1]) == 0 || count($aYesAccess) > 0) && (count($aAccess[0]) == 0 || count($aNoAccess) == 0)) ||
			$user_data['cms']
		)
		{
			return true;
		}

		return false;
	}
}

?>