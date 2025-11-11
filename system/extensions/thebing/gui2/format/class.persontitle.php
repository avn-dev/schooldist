<?
/*
 * Anrede (Mann/Frau),...
 */
class Ext_Thebing_Gui2_Format_PersonTitle extends Ext_Gui2_View_Format_Abstract {

	public function format($mValue, &$oColumn = null, &$aResultData = null){

		$aTitles = Ext_Thebing_Util::getPersonTitles();

		return $aTitles[$mValue];

	}

}
