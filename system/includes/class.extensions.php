<?php

/**
 * The extensions class
 */
class Extensions
{
	/**
	 * Checks the existence of an extension
	 * 
	 * @param string : The name of an extension
	 * @return bool : true (if exists) || false (if do not exists)
	 */
	public static function doExists($sExtension)
	{
		global $oZendDB;

		$sSQL = "
			SELECT
				`id`
			FROM
				`system_elements`
			WHERE
				`file` = :sFile
			LIMIT
				1
		";
		$mCheck = $oZendDB->fetchOne($sSQL, array('sFile' => $sExtension));

		if($mCheck)
		{
			return true;
		}
		return false;
	}
}