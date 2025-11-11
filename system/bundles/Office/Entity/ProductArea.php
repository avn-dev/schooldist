<?php

namespace Office\Entity;

class ProductArea extends Basic {

	protected $_sTable = 'office_product_areas';
	protected $_sTableAlias = 'opa';

	protected $_aFormat = array(
		'name' => array('required' => true)
	);
	
}