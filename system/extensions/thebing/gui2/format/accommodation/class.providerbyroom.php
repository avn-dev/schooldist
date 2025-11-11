<?

/**
 * Familienname anhand der room_id
 */
class Ext_Thebing_Gui2_Format_Accommodation_ProviderByRoom extends Ext_Gui2_View_Format_Abstract {

	public function format($mValue, &$oColumn = null, &$aResultData = null) {

		if($mValue > 0) {

			$oRoom = Ext_Thebing_Accommodation_Room::getInstance($mValue);
		
			$oProvider = $oRoom->getProvider();
			
			// Provider gefunden, Name ausgeben
			if($oProvider instanceof Ext_Thebing_Accommodation) {
				return $oProvider->getName();
			} else {
				return '';
			}

			
		} else {
			return '';
		}

	}

}
