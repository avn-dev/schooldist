<?php

class Ext_Thebing_Update extends Ext_TC_Update {

	// Hold die komplete Datenbankstructur vom aktuellen System
	public static function getDatabaseStructure(){
		$sSql = " SHOW TABLES  ";
		$aTables = DB::getQueryData($sSql);
		$aBack = array();
		foreach($aTables as $aTable){
			$sTable = reset($aTable);

			if(strpos($sTable, '__') === 0){
				continue;
			}
			if(strpos($sTable, 'office_') === 0){
				continue;
			}

			if(
				$sTable == 'kolumbus_access' ||
				$sTable == 'kolumbus_access_licence_client_access' ||
				$sTable == 'kolumbus_access_licence_client' ||
				$sTable == 'kolumbus_access_licence' ||
				$sTable == 'kolumbus_access_licence_access'
			){
				continue;
			}

			$sSql = " SHOW CREATE TABLE ".$sTable;
			$aT = DB::getQueryData($sSql);
			$aBack[] = $aT[0]['Create Table'];
		}
		return $aBack;
	}

	/**
	 * Update the default basic statistics
	 */
	public function updateBasicStatistics()
	{

		if(Ext_Thebing_Util::isTestSystem())
		{
			return false;
		}

		$sFullUrl = $this->_sFileServer.'/system/extensions/thebing/install/database.php?task=getBasicStatistics';
		$sSerialize = $this->_getFileContents($sFullUrl);

		$aData = json_decode($sSerialize, true);

		// Wenn es keine Standardstatistik gibt von Test, gibt es hier auch nichts zu tun.
		// Ansonsten würde es hier beim Datenbankupdate einen Fehler geben.
		if(empty($aData['page'])) {
			return false;
		}

		/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */ // Remove old entries

		$sSQL = "
			SELECT
				`ksp`.`id` AS `page_id`,
				`kss`.`id` AS `statistic_id`
			FROM
				`kolumbus_statistic_pages` AS `ksp`					LEFT OUTER JOIN
				`kolumbus_statistic_pages_statistics` AS `ksps`			ON
					`ksp`.`id` = `ksps`.`page_id`					LEFT OUTER JOIN
				`kolumbus_statistic_statistics` AS `kss`				ON
					`ksps`.`statistic_id` = `kss`.`id`
			WHERE
				`ksp`.`system` = 1 AND
				`ksp`.`active` = 1
		";
		$aEntries = DB::getQueryRows($sSQL);

		foreach((array)$aEntries as $aEntry)
		{
			$sSQL = "
				DELETE FROM
					`kolumbus_statistic_pages`
				WHERE
					`id` = :iPageID
			";
			$aSQL = array('iPageID' => (int)$aEntry['page_id']);
			DB::executePreparedQuery($sSQL, $aSQL);

			$sSQL = "
				DELETE FROM
					`kolumbus_statistic_pages_statistics`
				WHERE
					`page_id` = :iPageID
			";
			$aSQL = array('iPageID' => (int)$aEntry['page_id']);
			DB::executePreparedQuery($sSQL, $aSQL);

			$sSQL = "
				DELETE FROM
					`kolumbus_statistic_statistics`
				WHERE
					`id` = :iStatisticID
			";
			$aSQL = array('iStatisticID' => (int)$aEntry['statistic_id']);
			DB::executePreparedQuery($sSQL, $aSQL);

			$sSQL = "
				DELETE FROM
					`kolumbus_statistic_cols`
				WHERE
					`statistic_id` = :iStatisticID
			";
			$aSQL = array('iStatisticID' => (int)$aEntry['statistic_id']);
			DB::executePreparedQuery($sSQL, $aSQL);

			$sSQL = "
				DELETE FROM
					`kolumbus_statistic_statistic_intervals`
				WHERE
					`statistic_id` = :iStatisticID
			";
			$aSQL = array('iStatisticID' => (int)$aEntry['statistic_id']);
			DB::executePreparedQuery($sSQL, $aSQL);
		}

		/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */ // Add new page data

		// Clear page data
		unset($aData['page']['id'], $aData['page']['changed'], $aData['page']['created']);

		$aData['page']['created']	= date('Y-m-d H:i:s');
		$aData['page']['client_id']	= 0;
		$aData['page']['user_id']	= 0;

		DB::insertData('kolumbus_statistic_pages', $aData['page']);

		$iPageID = DB::fetchInsertID();

//		/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */ // Add page rights
//
//		$aItem = array(
//			'item_id'	=> $iPageID,
//			'item_type'	=> 'statistics_pages'
//		);
//		DB::insertData('kolumbus_access_matrix_items', $aItem);
//
//		$iItemID = DB::fetchInsertID();
//
//		foreach((array)$aGroups as $iGroupID => $sGroup)
//		{
//			$aItem = array(
//				'item_id'	=> $iItemID,
//				'group_id'	=> $iGroupID
//			);
//			DB::insertData('kolumbus_access_matrix_groups', $aItem);
//		}

		/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */ // Add new statistics data

		$iPosition = 1;

		foreach((array)$aData['statistics'] as $aStatistic)
		{
			$iTempID = $aStatistic['id'];

			// Clear statistic data
			unset($aStatistic['id'], $aStatistic['changed'], $aStatistic['created']);

			$aStatistic['created']		= date('Y-m-d H:i:s');
			$aStatistic['client_id']	= 0;
			$aStatistic['user_id']		= 0;

			DB::insertData('kolumbus_statistic_statistics', $aStatistic);

			$iStatisticID = DB::fetchInsertID();

//			/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */ // Add statistic rights
//
//			$aItem = array(
//				'item_id'	=> $iStatisticID,
//				'item_type'	=> 'statistics'
//			);
//			DB::insertData('kolumbus_access_matrix_items', $aItem);
//
//			$iItemID = DB::fetchInsertID();
//
//			foreach((array)$aGroups as $iGroupID => $sGroup)
//			{
//				$aItem = array(
//					'item_id'	=> $iItemID,
//					'group_id'	=> $iGroupID
//				);
//				DB::insertData('kolumbus_access_matrix_groups', $aItem);
//			}

			/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */ // Link page to statistic

			$aLink = array(
				'page_id'		=> $iPageID,
				'statistic_id'	=> $iStatisticID,
				'position'		=> $iPosition
			);
			DB::insertData('kolumbus_statistic_pages_statistics', $aLink);

			/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */ // Add statistic settings

			foreach((array)$aData['settings'] as $aSettings)
			{
				if($aSettings['statistic_id'] == $iTempID)
				{
					$aSettings['statistic_id'] = $iStatisticID;

					DB::insertData('kolumbus_statistic_cols', $aSettings);
				}
			}

			/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */ // Add statistic intervals

			foreach((array)$aData['intervals'] as $aInterval)
			{
				if($aInterval['statistic_id'] == $iTempID)
				{
					$aInterval['statistic_id'] = $iStatisticID;

					DB::insertData('kolumbus_statistic_statistic_intervals', $aInterval);
				}
			}

			$iPosition++;
		}
	}
	
	public function getFiltersets()
	{
		$sFullUrl = $this->_sFileServer.'/system/extensions/tc/system/update/database.php?task=getFiltersets';
		
		$sSerialize = $this->_getFileContents($sFullUrl);
		
		$aData = json_decode($sSerialize, true);
		
		return $aData;
	}

	public static function updateAccessDatabase() {

		Ext_Thebing_Access_File::create(System::d('license'));

	}

}
