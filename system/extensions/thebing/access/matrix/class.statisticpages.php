<?php

class Ext_Thebing_Access_Matrix_StatisticPages extends Ext_TC_Access_Matrix {
	
	protected $_sItemTable = 'kolumbus_statistic_pages';
	protected $_sItemNameField = 'title';
	protected $_sItemOrderbyField = 'title';
	protected $_sType = 'statistics_pages';
	protected $aRight = ['thebing_management_pages', ''];
	
	protected function _manipulateLoadItemsSqlParts(&$aSqlParts, &$aSql) {
		
		parent::_manipulateLoadItemsSqlParts($aSqlParts, $aSql);

		if(
			!Ext_Thebing_Util::isDevSystem() && 
			!Ext_Thebing_Util::isTestSystem()
		) {
			$aSqlParts['where'] .= " AND `system` = 0 ";
		}

		$aSqlParts['select'] .= ", `system`";

	}
	
}
