<?
class Ext_Gui2_View_Format_Time extends Ext_Gui2_View_Format_Date_Abstract {

	protected $aOption = array('format'=>'%X');
	protected $sWDDatePart = WDDate::DB_TIME;
	
}
