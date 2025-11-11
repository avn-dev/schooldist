<?

class Ext_Thebing_Gui2_Format_Timestamp extends Ext_Thebing_Gui2_Format_Date {

	public $sWDDatePart = WDDate::TIMESTAMP;

	public function format($mValue, &$oColumn = null, &$aResultData = null){

		if(
			$mValue == '0000-00-00' || 
			$mValue == NULL ||
			$mValue == '0'				# wird benötigt z.B. "Buchhaltung -> Rechnung drucken" sonst würde 1.1.1970 angezeigt werden
		) {
			return '';
		}

		if(empty($aResultData['school_id'])){
			$aResultData['school_id'] = \Core\Handler\SessionHandler::getInstance()->get('sid');
		}

		$this->format = Ext_Thebing_Format::getDateFormat($aResultData['school_id']);
		
		try {
			$mValue = parent::format($mValue, $oColumn, $aResultData);
		} catch(Exception $e) {
			$mValue = $mValue;
		}
		
		return $mValue;

	}
}