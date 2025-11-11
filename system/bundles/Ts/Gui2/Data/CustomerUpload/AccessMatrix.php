<?php

namespace Ts\Gui2\Data\CustomerUpload;

class AccessMatrix extends \Ext_TC_Access_Matrix {

	protected $_sItemTable = 'ts_flex_uploads';
	protected $_sItemNameField = 'name';
	protected $_sItemOrderbyField = 'name';

	protected $_sType = 'customer_uploads';

	protected $aRight = ['core_admin_emailaccounts', ''];

}