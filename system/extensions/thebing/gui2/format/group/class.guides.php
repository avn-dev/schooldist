<?

class Ext_Thebing_Gui2_Format_Group_Guides extends Ext_Gui2_View_Format_Abstract {

	public static $iLastGroupId = 0;
	public static $iLastGroupGuides = 0;

	// Liefert die Anzahl der Gruppenführer anhand der ID
	public function format($mValue, &$oColumn = null, &$aResultData = null){

		if($aResultData['id'] != self::$iLastGroupId){
			$aInquirys = Ext_Thebing_Inquiry_Group::getInquiriesOfGroup($aResultData['id']);
			$iGuides = 0;
			 /* @var $oInquiry Ext_TS_Inquiry */
			foreach((array)$aInquirys as $oInquiry){

				if($oInquiry->isGuide()){
					$iGuides++;
				}
			}
			// Erebnis in Cash speichern
			self::$iLastGroupId = $aResultData['id'];
			self::$iLastGroupGuides = $iGuides;
		}

		return (string)self::$iLastGroupGuides;

	}

}
