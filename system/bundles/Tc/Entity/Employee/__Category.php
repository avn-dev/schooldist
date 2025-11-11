<?php

namespace Tc\Entity\Employee;

class Category extends \Ext_TC_Basic {

	protected $_sTable = 'tc_employees_categories';

	protected $_sTableAlias = 'tc_ec';

	protected $_aJoinTables = [
		'employees' => [
			'table' => 'tc_employees_to_categories',
			'foreign_key_field' => 'employee_id',
			'primary_key_field' => 'category_id',
			'class' => 'Ext_Thebing_User',
			'autoload' => false
		],
		'functions' => [
			'table' => 'tc_employees_categories_to_functions',
			'foreign_key_field' => 'function',
			'primary_key_field' => 'category_id',
			'autoload' => false
		],

	];

}