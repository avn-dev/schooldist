<?php

define('SEARCH_LIMIT_AGENCY', 20);

// TODO: PHP5: Should be a static class with static functions
class Ext_Thebing_Agency_Search {

	// public static function search($sSearch, $iLimit, $iOffset)
	public static function search($sSearch = '', $iLimit = 10000, $iOffset = 0) {

		$sQuery = "
			SELECT
				`id`
			FROM
				`ts_companies`
			WHERE
				(
					`ext_1` LIKE '%".db_addslashes($sSearch)."%'
				OR
					`ext_2` LIKE '%".db_addslashes($sSearch)."%'
				) AND
				`active` = 1 AND
				`type` & '".\TsCompany\Entity\AbstractCompany::TYPE_AGENCY."'
			ORDER BY
				`ext_1` ASC
			LIMIT
				".(int)$iOffset.", ".(int)$iLimit."
		";
		$rResult = db_query($sQuery);
		$aAgencyList = array();
		while ($aResult = get_data($rResult)) {
			$aAgencyList[] = Ext_Thebing_Agency::getInstance((int)$aResult['id']);
		}
		return $aAgencyList;

	}

	// public static function count($sSearch)
	public static function count($sSearch) {

		$sQuery = "
			SELECT
				COUNT(`id`) AS `count`
			FROM
				`ts_companies`
			WHERE
				(
					`ext_1` LIKE '%".db_addslashes($sSearch)."%'
				OR
					`ext_2` LIKE '%".db_addslashes($sSearch)."%'
				) AND
				`active` = 1 AND
				`type` & ".\TsCompany\Entity\AbstractCompany::TYPE_AGENCY."
		";
		$aResult = get_data(db_query($sQuery));
		return $aResult['count'];

	}

}
