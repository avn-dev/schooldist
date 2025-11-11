<?php

class Ext_Thebing_Gui2_Format_Saison_Title extends Ext_Gui2_View_Format_Abstract
{
	protected $_aSchoolLanguages;

	public function  __construct($aSchoolLanguages)
	{
		$this->_aSchoolLanguages	= $aSchoolLanguages;
	}

	public function format($mValue, &$oColumn = null, &$aResultData = null)
	{
		// Wenn kein Titel in Loginsprache vorhanden, versuchen wir es mit einer anderen Schulsprache damit Feld Column nicht leer bleibt
		if(empty($mValue))
		{
			foreach((array)$this->_aSchoolLanguages as $sCode => $sLanguage)
			{
				$sColumn = 'title_'.$sCode;
				if(array_key_exists($sColumn, $aResultData) && !empty($aResultData[$sColumn]))
				{
					$mValue			= $aResultData[$sColumn];
				}
			}
		}

		return $mValue;
	}
}

?>
