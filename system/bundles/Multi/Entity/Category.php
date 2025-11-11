<?php

namespace Multi\Entity;

class Category extends \WDBasic {

	protected $_sTable = 'multi_categories';
	protected $_sTableAlias = 'm_c';

	protected $_aFormat = array(
		'name' => array('required' => true)
	);

}