<?php

class Ext_Thebing_Gui2_Format_Pdf_ImageList extends Ext_Gui2_View_Format_Abstract
{

	public function format($mValue, &$oColumn = null, &$aResultData = null)
	{
		$aBack = array();

		$oFormatImage = new Ext_Thebing_Gui2_Format_Language_Image();

		$oTemplate = Ext_Thebing_Pdf_Template::getInstance((int)$mValue);

		$aLanguagesList = $oTemplate->languages;

		foreach((array)$aLanguagesList as $sLang)
		{
			$aBack[] = $oFormatImage->format($sLang);
		}

		$sBack = implode('&nbsp;', $aBack);

		return $sBack;

	}

}