<?
class Ext_Thebing_Gui2_Format_Payment_Reminder extends Ext_Gui2_View_Format_Abstract {

	protected $_bUseIndex = false;

	public function __construct($bUseIndex = false) {
		$this->_bUseIndex = $bUseIndex;
	}
	
	public function format($mValue, &$oColumn = null, &$aResultData = null){

		$aData = $aResultData['payment_reminder'];
		if($this->_bUseIndex) {
			$aData = $mValue;
		}
		
		$aReminderData = explode(',', $aData);

		$sBack = '';

		if(!empty($aReminderData[0])){
			
			$oFormat = new Ext_Thebing_Gui2_Format_Date_Time();
			
			$sDate	= $aReminderData[1];
			$iCount = $aReminderData[0];
			if($this->_bUseIndex) {
				$oFormat = new Ext_Thebing_Gui2_Format_Date_Time();
				$iCount = count($aReminderData);
				$sLastDate = array_pop($aReminderData);
				$sDate = str_replace('T', ' ', $sLastDate);
			}
			
			$sBack = $iCount.' | '.$oFormat->format($sDate, $oColumn, $aResultData);
		}

		return $sBack;

	}

}
