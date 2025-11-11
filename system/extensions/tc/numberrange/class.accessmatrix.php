<?php

class Ext_TC_Numberrange_AccessMatrix extends Ext_TC_Access_Matrix {

	protected $_sItemTable = 'tc_number_ranges';
	protected $_sItemNameField = 'name';
	protected $_sItemOrderbyField = 'name';
	protected $_sType = 'number_ranges';
	protected $aRight = ['core_numberranges', ''];

}
