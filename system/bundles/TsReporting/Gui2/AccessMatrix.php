<?php

namespace TsReporting\Gui2;

class AccessMatrix extends \Ext_TC_Access_Matrix
{
	protected $_sItemTable = 'ts_reporting_reports';
	protected $_sItemNameField = 'name';
	protected $_sItemOrderbyField = 'name';

	protected $_sType = 'ts_reporting_reports';

	protected $aRight = ['ts_reporting_overview', ''];
}