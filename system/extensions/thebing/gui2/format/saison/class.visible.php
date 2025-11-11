<?php

class Ext_Thebing_Gui2_Format_Saison_Visible extends Ext_Gui2_View_Format_Abstract
{
	protected $_sTranslationPart;

	public function  __construct($sTranslationPart)
	{
		$this->_sTranslationPart = $sTranslationPart;
	}

	public function format($mValue, &$oColumn = null, &$aResultData = null)
	{
		if( 0 == $mValue )
		{
			$mValue = L10N::t('nicht sichtbar', $this->_sTranslationPart);
		}
		else
		{
			$mValue = L10N::t('sichtbar', $this->_sTranslationPart);
		}

		return $mValue;
	}
}

?>
