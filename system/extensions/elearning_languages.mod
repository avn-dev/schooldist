<?php

$oConfig = new \Cms\Helper\ExtensionConfig($element_data['page_id'], $element_data['id'], $element_data['content_id'], $element_data['language']);

if(isset($oConfig->exam_id)) {

	$oExam = new Ext_Elearning_Exam($oConfig->exam_id);
	$aExamLanguages = $oExam->getLanguages();
	
	$arrLanguages = array();
	
	$arrLanguages = $oSite->getLanguages();
	
	$arrPageData = $objWebDynamicsDAO->getPageData($page_data['id']);
	
	foreach((array)$arrLanguages as $intKey=>$arrLanguage) {
	
		if(!in_array($arrLanguage['code'], $aExamLanguages)) {
			unset($arrLanguages[$intKey]);
			continue;
		}

		if($arrPageData['original_language'] == "") {
			$arrLanguage['link'] = idtopath($page_data['id'], $arrLanguage['code']);
		} else {
			$arrLanguage['link'] = "/".$arrLanguage['code']."/";
		}

		$arrLanguages[$intKey] = $arrLanguage;

	}
	
	$objSmarty = new \Cms\Service\Smarty();
	
	$objSmarty->assign('arrLanguages', $arrLanguages);
	$objSmarty->assign('strLanguage', $page_data['language']);
	
	$objSmarty->displayExtension($element_data);

}
