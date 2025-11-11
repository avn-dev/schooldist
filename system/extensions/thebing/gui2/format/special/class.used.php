<?

class Ext_Thebing_Gui2_Format_Special_Used extends Ext_Gui2_View_Format_Abstract {

	/**
	 * @param mixed $mValue
	 * @param null $oColumn
	 * @param null $aResultData
	 * @return string
	 * @throws Exception
	 */
	public function format($mValue, &$oColumn = null, &$aResultData = null){

		$oSpecial = Ext_Thebing_School_Special::getInstance($aResultData['id']);
		$iAvailable = $oSpecial->getUsedQuantity();

		$sReturn = (string)$iAvailable;

		return $sReturn;
	}

}
