<?php

/**
 * @property $id
 * @property $changed
 * @property $created
 * @property $active
 * @property $editor_id 
 * @property $creator_id
 * @property $cost_id 
 * @property $symbol
 * @property $factor
 */

class Ext_Thebing_School_Cost_Combination extends Ext_Thebing_Basic {

	// Tabellenname
	protected $_sTable = 'kolumbus_costs_price_calculation_combinations';

	// Tabellenalias
	protected $_sTableAlias = 'kcpcc';

	protected $_aFormat = array(
		'factor' => array(
			'validate'	=> 'INT_NOTNEGATIVE'
		),
	);



}