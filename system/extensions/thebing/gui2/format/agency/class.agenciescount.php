<?

class Ext_Thebing_Gui2_Format_Agency_Agenciescount extends Ext_Thebing_Gui2_Format_Format {

	public function format($mValue, &$oColumn = null, &$aResultData = null){

		$oAgencyList = Ext_Thebing_Agency_List::getInstance($mValue);

		$aAgencies = Ext_Thebing_Util::convertDataIntoObject($oAgencyList->join_agencies, 'Ext_Thebing_Agency');

		return count($aAgencies);
	}

}
