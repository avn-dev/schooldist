<?php

namespace Office\Entity;

class PriceQuantityScalePart extends Basic {

	protected $_sTable = 'office_price_quantity_scale_parts';
	protected $_sTableAlias = 'opqsp';

	protected $_aFormat = array(
		'from' => array('required' => true),
		'to' => array('required' => true)
	);
	
}