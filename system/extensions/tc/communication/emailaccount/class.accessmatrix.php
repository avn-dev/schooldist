<?php

class Ext_TC_Communication_EmailAccount_AccessMatrix extends Ext_TC_Access_Matrix {
	
	protected $_sItemTable = 'tc_communication_emailaccounts';
	protected $_sItemNameField = 'email';
	protected $_sItemOrderbyField = 'email';
	protected $_sType = 'communication_emailaccounts';
	protected $aRight = ['core_admin_emailaccounts', ''];
	
}
