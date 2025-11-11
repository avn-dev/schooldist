<?php


class Ext_TS_Document_Gui2_Selection_TemplateLanguage extends Ext_Gui2_View_Selection_Abstract
{
	public function getOptions($aSelectedIds, $aSaveField, &$oWDBasic)
	{
		$aLanguages			= array();
		
		$oSchool			= Ext_Thebing_Client::getFirstSchool();
		
		$aSchoolLanguages	= $oSchool->getLanguageList();
		
		$aLanguagesLabels	= Ext_Thebing_Data::getLanguageSkills();
		
		$oTemplate			= Ext_Thebing_Pdf_Template::getInstance($oWDBasic->template_id);
		
		$aTemplateLanguages	= (array)$oTemplate->languages;
		
		foreach($aTemplateLanguages as $sLang)
		{
			// Es muss geprüft werden ob die gerade aktive Schule auch die Templatesprache verwenden darf
			if(!isset($aSchoolLanguages[$sLang]))
			{
				continue;
			}

			$aLanguages[$sLang] = $aLanguagesLabels[$sLang];
		}
		
		return $aLanguages;
	}
}