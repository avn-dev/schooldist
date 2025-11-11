<?php

class Ext_Thebing_Management_Page extends Ext_Thebing_Basic
{
	// DB table
	protected $_sTable = 'kolumbus_statistic_pages';

	// Selected statistics
	protected $_aStatistics = array();

	/* ==================================================================================================== */

	public function __construct($iDataID = 0, $sTable = null)
	{
		parent::__construct($iDataID, $sTable);

		$this->_aData['client_id'] = \Ext_Thebing_Client::getClientId();
	}

	public function __get($sName)
	{
		
		Ext_Gui2_Index_Registry::set($this);
		
		if($sName == 'statistics')
		{
			$sValue = $this->_aStatistics;
		}
		else
		{
			$sValue = parent::__get($sName);
		}

		return $sValue;
	}

	public function __set($sName, $mValue)
	{
		if($sName == 'statistics')
		{
			$this->_aStatistics = $mValue;
		}
		else if($sName == 'client_id' || $sName == 'user_id')
		{
			throw new Exception('Client-ID and User-ID are not rewritable values.');
		}
		else
		{
			parent::__set($sName, $mValue);
		}
	}

	/* ==================================================================================================== */

	/**
	 * Load linked statistics
	 */
	public function getStatisticsLinks()
	{
		$sSQL = "
			SELECT
				`statistic_id`
			FROM
				`kolumbus_statistic_pages_statistics`
			WHERE
				`page_id` = :iPageID
			ORDER BY
				`position`
		";
		$aSQL = array('iPageID' => $this->id);
		$aStatistics = DB::getQueryCol($sSQL, $aSQL);

		if(empty($aStatistics))
		{
			return array();
		}

		return $aStatistics;
	}


	/**
	 * Gui2 wrapper
	 */
	public function getListQueryData($oGui = null)
	{

		$aQueryData = array();

		$aQueryData['data'] = array();
		$aQueryData['data']['iCliendID'] = \Ext_Thebing_Client::getClientId();
		$aQueryData['data']['table'] = $this->_sTable;

		/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */

		$aMasterUserIds = Ext_Thebing_Access::getMasterUserIds();

		$oMatrix = new Ext_Thebing_Access_Matrix_StatisticPages;

		$aItems = $oMatrix->getListByUserRight();

		$sAccessWhere = '';

		if(!in_array(\Access::getInstance()->id, $aMasterUserIds)) {

			if(empty($aItems)) {
				$aItems[0] = true;
			}

			$sAccessWhere .= " AND `kmp`.`id` IN ( ";

			foreach((array)$aItems as $iKey => $sValue) {
				$sAccessWhere .= $iKey . ", ";
			}

			$sAccessWhere = rtrim($sAccessWhere, ", ");
			$sAccessWhere .= " ) ";
		}

		if(
			!Ext_Thebing_Util::isDevSystem() && 
			!Ext_Thebing_Util::isTestSystem()
		) {
			$sAccessWhere .= " AND `kmp`.`system` = 0 ";
		}

		/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */

		$aQueryData['sql'] = "
			SELECT
				`kmp`.*,
				UNIX_TIMESTAMP(`kmp`.`changed`) AS `changed`,
				UNIX_TIMESTAMP(`kmp`.`created`) AS `created`,
				GROUP_CONCAT(`kms`.`title` ORDER BY `kmps`.`position` SEPARATOR ', ') AS `statistics`
			FROM
				#table AS `kmp` LEFT OUTER JOIN
				`kolumbus_statistic_pages_statistics` AS `kmps` ON
					`kmp`.`id` = `kmps`.`page_id` LEFT OUTER JOIN
				`kolumbus_statistic_statistics` `kms` ON
					`kmps`.`statistic_id` = `kms`.`id`
			WHERE
				`kmp`.`client_id` = :iCliendID AND
				`kmp`.`active` = 1
				" . $sAccessWhere . "
			GROUP BY
				`kmp`.`id`
			ORDER BY
				`kmp`.`title`
		";

		return $aQueryData;
	}


	/**
	 * Get pages list by user access right
	 */
	public function getListByUserRight()
	{

		$oMatrix = new Ext_Thebing_Access_Matrix_StatisticPages();

		$aList = $oMatrix->getListByUserRight();
		
		return $aList;
	}


	/**
	 * Save entry
	 */
	public function save($bLog = true)
	{
		$aStatistics = $this->_aStatistics;

		parent::save();

		/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */ // DELETE old links

		$sSQL = "
			DELETE FROM
				`kolumbus_statistic_pages_statistics`
			WHERE
				`page_id` = :iPageID
		";
		$aSQL = array('iPageID' => $this->id);
		DB::executePreparedQuery($sSQL, $aSQL);

		/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */

		foreach((array)$aStatistics as $iKey => $iStatisticID)
		{
			$aInsert = array(
				'page_id'		=> $this->id,
				'statistic_id'	=> $iStatisticID,
				'position'		=> $iKey + 1
			);
			DB::insertData('kolumbus_statistic_pages_statistics', $aInsert);
		}

		$this->_aStatistics = $aStatistics;

		/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */ // Create access right

		$oMatrix = new Ext_Thebing_Access_Matrix_StatisticPages;
		$oMatrix->createOwnerRight($this->id);

		/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */

		WDCache::deleteGroup(\Admin\Helper\Navigation::CACHE_GROUP_KEY);

		return $this;
	}


	/**
	 * Return myself class name
	 */
	public function getClassName()
	{
		return get_class($this);
	}

	/* ==================================================================================================== */

	/**
	 * See parent
	 */
	protected function _loadData($iDataID)
	{
		parent::_loadData($iDataID);

		if($iDataID > 0)
		{
			// Load statistics links
			$this->_aStatistics = $this->getStatisticsLinks();
		}
	}
}

?>