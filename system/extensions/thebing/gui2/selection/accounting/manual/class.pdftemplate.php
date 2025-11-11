<?php

class Ext_Thebing_Gui2_Selection_Accounting_Manual_PdfTemplate extends Ext_Gui2_View_Selection_Abstract {

	public function getOptions($aSelectedIds, $aSaveField, &$oWDBasic) {

		$oSchool = Ext_Thebing_Client::getFirstSchool();
		$iAgency = $oWDBasic->agency_id;
		$oAgency = Ext_Thebing_Agency::getInstance((int)$iAgency);
		$sLang = $oAgency->getLanguage();

		$aPdfTemplates = array();
		if($iAgency > 0){
			$aPdfTemplates = Ext_Thebing_Pdf_Template_Search::s('manual_creditnotes', $sLang, $oSchool->id);
			$aPdfTemplates = Ext_Thebing_Util::convertArrayForSelect($aPdfTemplates);
		}

		$aPdfTemplates = Ext_Thebing_Util::addEmptyItem($aPdfTemplates);

		return $aPdfTemplates;

	}

}