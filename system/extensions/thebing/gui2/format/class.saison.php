<?

class Ext_Thebing_Gui2_Format_Saison extends Ext_Gui2_View_Format_Abstract {

	public function format($mValue, &$oColumn = null, &$aResultData = null){

		$oSchool = Ext_Thebing_School::getSchoolFromSession();
		$aSaisons = $oSchool->getSaisonList(true, true, true, true, true, true, true);

		$mValue = $aSaisons[$mValue];

		return $mValue;

	}

}
