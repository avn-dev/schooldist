<?php

class Ext_Gui2_View_Format_Date_Time extends Ext_Gui2_View_Format_Date_Abstract {

	protected $aOption = array('format'=>'%x %X');
	protected $sWDDatePart = WDDate::DB_DATETIME;

}