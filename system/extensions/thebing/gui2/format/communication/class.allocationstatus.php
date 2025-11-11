<?

/**
 * Familienname anhand der room_id
 */
class Ext_Thebing_Gui2_Format_Communication_AllocationStatus extends Ext_Gui2_View_Format_Abstract {

	public function format($mValue, &$oColumn = null, &$aResultData = null){

		if(
			$aResultData['active'] == 1 &&
			$aResultData['accommodation_confirmed'] == 0
		){
			// Status neu
			$mValue = L10N::t('Neuer Eintrag');
		}elseif(
			$aResultData['active'] == 1 &&
			$aResultData['accommodation_confirmed'] > 0
		){
			// Status Aktiv
			$mValue = L10N::t('Aktiv');
		}else{
			// Status Alt
			$mValue = L10N::t('Alt');
		}

		return $mValue;

	}

}
