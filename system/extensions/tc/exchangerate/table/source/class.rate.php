<?php

/**
 * @property int $id
 * @property timestamp $created
 * @property int $source_id
 * @property int $table_id
 * @property string $currency_iso_from
 * @property string $currency_iso_to
 * @property float $price
 * @property date $date
 */
class Ext_TC_Exchangerate_Table_Source_Rate extends Ext_TC_Basic {

	protected $_sTable = 'tc_exchangerates_tables_rates';
	protected $_sTableAlias = 'tc_etr';

	protected $_aRateCache = array();

	public function manipulateSqlParts(&$aSqlParts, $sView=null) {
	
		$aSqlParts['select'] .= "
			, `tc_ets`.`name` `source_name`
		";
		
		$aSqlParts['from'] .= "
			LEFT JOIN `tc_exchangerates_tables_sources` `tc_ets` ON
				`tc_ets`.`id` = `tc_etr`.`source_id` AND
				`tc_ets`.`active` = 1	
		";
		
	}

}