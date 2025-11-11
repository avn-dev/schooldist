<?php

namespace Office\Entity;

class RevenueAccounts extends \WDBasic {

	protected $_sTable = 'office_revenue_accounts';
	protected $_sTableAlias = 'ora';

	protected $_aFormat = array(
		'name' => array('required' => true),
		'number' => array('required' => true)
	);

}