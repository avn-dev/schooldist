<?
class Ext_Gui2_View_Format_Timestamp extends Ext_Gui2_View_Format_Date_Abstract {

	protected $aOption = array('format'=>'%x %X');
	protected $sWDDatePart = WDDate::TIMESTAMP;

	public function format($mValue, &$oColumn = null, &$aResultData = null){

		$oDate = new WDDate($mValue);
		$mValue = $oDate->get(WDDate::STRFTIME, $this->format);

		return $mValue;

	}

	public function convert($mValue, &$oColumn = null, &$aResultData = null){

		// fals es bereits ein timestamp ist gebe es direkt zurück
		if(is_numeric($mValue)){
			return $mValue;
		}

		$oDate = new WDDate($mValue, WDDate::STRFTIME, $this->format);
		$mValue = $oDate->get(WDDate::TIMESTAMP);

		return $mValue;

	}
	
}
