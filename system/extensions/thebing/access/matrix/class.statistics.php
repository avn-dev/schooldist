<?php

class Ext_Thebing_Access_Matrix_Statistics extends Ext_TC_Access_Matrix {
	
	protected $_sItemTable = 'kolumbus_statistic_statistics';
	protected $_sItemNameField = 'title';
	protected $_sItemOrderbyField = 'title';
	protected $_sType = 'statistics';
	protected $aRight = ['thebing_management_statistics', ''];
	
	protected function _manipulateLoadItemsSqlParts(&$aSqlParts, &$aSql) {

		parent::_manipulateLoadItemsSqlParts($aSqlParts, $aSql);

		$aSqlParts['from'] .= " LEFT JOIN
				`kolumbus_statistic_pages_statistics` `ksps` ON
					`items`.`id` = `ksps`.`statistic_id` LEFT JOIN
				`kolumbus_statistic_pages` `ksp` ON
					`ksps`.`page_id` = `ksp`.`id` ";

		$aSqlParts['select'] .= ", `ksp`.`system`";

	}
	
}
