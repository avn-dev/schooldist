<?php

namespace Office\Entity;

class Product extends Basic {

	protected $_sTable = 'office_articles';
	protected $_sTableAlias = 'op';

	protected $_aFormat = array(
		'product' => array('required' => true)
	);
	
}