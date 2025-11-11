<?php

class Ext_TC_Placeholder_Productline extends Ext_TC_Placeholder_Abstract {
	
	protected $_aSettings = array(
		'variable_name' => 'oProductline'
	);
	
	protected $_aPlaceholders = array(
		'productline_id' => array(
			'label' => 'Produktlinie: ID',
			'type' => 'field',
			'source' => 'id'
		),
		'productline_name' => array(
			'label' => 'Produktlinie: Name',
			'type' => 'method',
			'source' => 'getName',
			'pass_language' => true
		)
	);
	
}

?>
