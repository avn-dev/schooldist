<?
class Ext_Visual_Util {


	protected static function handleErrors($iSiteID = false, $sPathofStandardVisual = false, $aVisualArray = false)
	{
		// handle may given failures
		$iSiteID = (int)$iSiteID;
		if($iSiteID == 0)
		{
			throw new Exception("Parameter SiteID/PageID must be an Integer and over 0");
		} else if ($sPathofStandardVisual && trim($sPathofStandardVisual) == '')
		{
			throw new Exception("Parameter PathOfStandardVisual is empty");
		} else if ($aVisualArray && !is_array($aVisualArray))
		{
			throw new Exception("Parameter VisualsArray is not an Array");
		}
		return true;
	}

	/**
	 * @description gets all in system added visuals where active
	 * @return array visual ids
	 */
	public static function getAllVisualGroups()
	{
		$sSQL = "
			SELECT
				`id`,
				`name`
			FROM
				`visual_groups`
			WHERE
				`active` = 1
		";
		$aVisualIGroups = DB::getQueryPairs($sSQL);
		
		return (array)$aVisualIGroups;
	}


	public static function getSiteIDFromPage($iPageID = false)
	{
		// handle may given failures
		self::handleErrors($iPageID);
		
		// get site_id
		$aSql = array(
			'page_id'	=> $iPageID
		);
		$sSql = "
			SELECT
				`site_id`
			FROM
				`cms_pages`
			WHERE
				`id` = :page_id
		";
		$aSiteID = DB::getPreparedQueryData($sSql, $aSql);
		if(!empty($aSiteID))
		{
			$iSiteID = (int)$aSiteID[0]['site_id'];
		} else {
			$iSiteID = false;
		}
		
		return $iSiteID;
	}



	/**
	 * @param integer $iSiteID ID of CMS Site (Unique)
	 */
	public static function getStandardSiteVisual($iSiteID = false, $iGroupID)
	{
		// handle may given failures
		self::handleErrors($iSiteID);
		
		// get standart visual
		$aSql = array(
			'site_id'	=> $iSiteID,
			'iGroupID'	=> $iGroupID
		);
		$sSql = "
			SELECT
				*
			FROM
				`visual`
			WHERE
				`standard` 			= 1 AND
				`active` 			= 1 AND
				`site_id` 			= :site_id AND
				`visual_groups_id`	= :iGroupID
			LIMIT 1
		";
		$aStandardVisual = DB::getPreparedQueryData($sSql, $aSql);

		// prepare return
		if(empty($aStandardVisual))
		{
			$sPathofStandardVisual = false;
		} else {
			$sPathofStandardVisual = $aStandardVisual[0]['visual_path'];
		}
		return $sPathofStandardVisual;
	}



	public static function updateStandardVisual($iSiteID, $sPathofStandardVisual, $iGroupID)
	{
		// handle may given failures
		self::handleErrors($iSiteID, $sPathofStandardVisual);

		// update new standard visual
		$aSql = array(
			'site_id'		=> $iSiteID,
			'visual_path'	=> $sPathofStandardVisual,
			'iGroupID'		=> $iGroupID
		);
		$sSql = "
			UPDATE
				`visual`
			SET
				`visual_path` = :visual_path
			WHERE
				`site_id` 			= :site_id AND
				`standard` 			= 1 AND
				`visual_groups_id`	= :iGroupID
			LIMIT 1
		";
		DB::executePreparedQuery($sSql, $aSql);

		return true;
	}



	public static function saveNewStandardVisual($iSiteID, $sPathofStandardVisual, $iGroupID)
	{
		// handle may given failures
		self::handleErrors($iSiteID, $sPathofStandardVisual);
		
		// save new standard visual
		$aSql = array(
			'created'		=> date('YmdHis'),
			'active'		=> 1,
			'site_id'		=> $iSiteID,
			'visual_path'	=> $sPathofStandardVisual,
			'standard'		=> 1,
			'iGroupID'		=> $iGroupID
		);
		$sSql = "
			INSERT INTO
				`visual`
					(
						`created`,
						`active`,
						`site_id`,
						`visual_groups_id`,
						`visual_path`,
						`standard`
					)
				VALUES
					(
						:created,
						:active,
						:site_id,
						:iGroupID,
						:visual_path,
						:standard
					)
		";
		DB::executePreparedQuery($sSql, $aSql);
		
		return true;
	}
	
	
	
	public static function resetStandardVisual($iSiteID, $iGroupID)
	{
		// handle may given failures
		self::handleErrors($iSiteID);
		
		// update standard visual
		$aSql = array(
			'site_id'		=> $iSiteID,
			'iGroupID'		=> $iGroupID
		);
		$sSql = "
			UPDATE
				`visual`
			SET
				`active` = 0
			WHERE
				`site_id` 			= :site_id AND
				`standard`			= 1 AND
				`visual_groups_id`	= :iGroupID
			LIMIT 1
		";
		DB::executePreparedQuery($sSql, $aSql);
		
		return true;
	}
	

	
	public static function createNewElement($iPageID, $iGroupID)
	{
		// handle may given failures
		self::handleErrors($iPageID);
		
		// create new visual
		$aSql = array(
			'created'		=> date('YmdHis'),
			'active'		=> 1,
			'page_id'		=> $iPageID,
			'visual_path'	=> '',
			'standard'		=> 0,
			'iGroupID'		=> $iGroupID
		);
		$sSql = "
			INSERT INTO
				`visual`
				(
					`created`,
					`active`,
					`page_id`,
					`visual_groups_id`,
					`visual_path`,
					`standard`
				)
			VALUES
				(
					:created,
					:active,
					:page_id,
					:iGroupID,
					:visual_path,
					:standard
				)
		";
		DB::executePreparedQuery($sSql, $aSql);
		
		// get id of new visual
		$iLastInsertID = DB::fetchInsertID();
		
		return $iLastInsertID;
	}
	
	
	
	public static function updateElements($iPageID, $aVisuals, $iGroupID)
	{
		// handle may given failures
		self::handleErrors($iPageID, false, $aVisuals);
		
		foreach($aVisuals as $sKey => $sValue)
		{
			$iVisualID = substr($sKey, 10);
			$aSql = array(
				'visual_path'	=> $sValue,
				'id'			=> $iVisualID,
				'iGroupID'		=> $iGroupID
			);
			
			if(trim($sValue) != '')
			{
				$sSql = "
					UPDATE
						`visual`
					SET
						`visual_path` = :visual_path
					WHERE
						`id` 				= :id AND
						`visual_groups_id`	= :iGroupID
					LIMIT 1
				";
				DB::executePreparedQuery($sSql, $aSql);
			}
			else
			{
				unset($aSql['visual_path']);
				
				$sSql = "
					UPDATE
						`visual`
					SET
						`active` = 0
					WHERE
						`id` 				= :id AND
						`visual_groups_id`	= :iGroupID
					LIMIT 1
				";
				DB::executePreparedQuery($sSql, $aSql);
			}
		}
		
		return true;
	}
	
	
	
	public static function getAllElements($iPageID, $iGroupID)
	{
		// handle may given failures
		self::handleErrors($iPageID);
		
		// get all elements of pageid
		$aSql = array(
			'page_id' 	=> $iPageID,
			'iGroupID'	=> $iGroupID
		);
		$sSql = "
			SELECT
				*
			FROM
				`visual`
			WHERE
				`active` 			= 1 AND
				`standard` 			= 0 AND
				`page_id` 			= :page_id AND
				`visual_groups_id` 	= :iGroupID
			ORDER BY `id`
		";
		$aVisuals = DB::getPreparedQueryData($sSql, $aSql);

		if(!empty($aVisuals))
		{
			return $aVisuals;
		} else {
			return false;
		}
	}
	
	
	
	public static function checkParentOfPage($iPageID = false, $iSiteID = false)
	{
		// handle may given errors
		self::handleErrors($iPageID);
		self::handleErrors($iSiteID);
		
		// get existent Page ID if exists
		$aSql = array(
			'site_id'	=> $iSiteID,
			'page_id'	=> $iPageID
		);
		$sSql = "
			SELECT
				*
			FROM
				`cms_pages`
			WHERE
				`site_id` 			= :site_id AND
				`id` 				= :page_id
			LIMIT 1
		";
		$aSystemPage = DB::getPreparedQueryData($sSql, $aSql);
		
		if(empty($aSystemPage))
		{
			return false;
		} else {
			return true;
		}
	}
	
}