<?

class Ext_Thebing_Gui2_Format_Special_Directbooking extends Ext_Gui2_View_Format_Abstract {


	public function format($mValue, &$oColumn = null, &$aResultData = null){

		$aAvailable = Ext_Thebing_School_Special::getAvailableFor();

		$values = [];
		if($aResultData['direct_bookings']) {
			$values[] = $aAvailable[1];
		}
		
		if($aResultData['agency_bookings']) {
			$values[] = $aAvailable[2];
		}

		return implode('<br>', $values);
	}

}
