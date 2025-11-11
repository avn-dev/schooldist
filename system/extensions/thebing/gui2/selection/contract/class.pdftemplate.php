<?php

class Ext_Thebing_Gui2_Selection_Contract_PdfTemplate extends Ext_Gui2_View_Selection_Abstract {

	public function getOptions($aSelectedIds, $aSaveField, &$oWDBasic) {

		$sItem = $oWDBasic->getJoinedObject('kcont')->item;

		if(empty($sItem)) {
			return array();
		}

		$oContractTemplate = new Ext_Thebing_Contract_Template((int)$oWDBasic->getJoinedObject('kcont')->contract_template_id);

		$aTemplateTypes = array();
		$aPdfTemplates = array();

		if($oContractTemplate->type == 1) {
			$aTemplateTypes = array('document_'.$sItem.'_contract_basic');
		} elseif($oContractTemplate->type == 2) {
			$aTemplateTypes = array('document_'.$sItem.'_contract_additional');
		}

		if(!empty($aTemplateTypes)) {
			$aPdfTemplates = Ext_Thebing_Pdf_Template_Search::s($aTemplateTypes, false);
			$aPdfTemplates = Ext_Thebing_Util::convertArrayForSelect($aPdfTemplates);
			$aPdfTemplates = Ext_Thebing_Util::addEmptyItem($aPdfTemplates, Ext_Thebing_L10N::getEmptySelectLabel('please_choose'));
		}

		return $aPdfTemplates;

	}

}