<?

class Ext_Thebing_Gui2_Selection_School_DefaultLanguage extends Ext_Gui2_View_Selection_Abstract
{
	/**
	 * Get the options
	 *
	 * @param array $aSelectedIds
	 * @param array $aSaveField
	 * @param object $oWDBasic
	 * @return array
	 */
    public function getOptions($aSelectedIds, $aSaveField, &$oWDBasic)
    {
		$aLanguages = Ext_Thebing_Data::getSystemLanguages();

		$aNew = array();

		foreach((array)$oWDBasic->languages as $sKey)
		{
			$aNew[$sKey] = $aLanguages[$sKey];
		}

		return $aNew;

	}

}