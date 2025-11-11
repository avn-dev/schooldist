<?

class Ext_Thebing_Data_Companie{
	
	public static function getCompaniePriceList($id = 0){
		global $oL10N;
		$aSaisons = Ext_Thebing_Data_school::getSaisonList(true,false,false,true);
		$aBack = array();
				
		foreach($aSaisons as $iKey => $sSaison){
			
			$aBack[$iKey]["weekday"] = $oL10N->translate("Wochentag");
			$aBack[$iKey]["sa"] = $oL10N->translate("Samstag");
			$aBack[$iKey]["su"] = $oL10N->translate("Sontag");
			$aBack[$iKey]["holiday"] = $oL10N->translate("Holiday");
			$aBack[$iKey]["night"] = $oL10N->translate("Nachtzuschlag");
			
			
		}
		return $aBack;
	}
}