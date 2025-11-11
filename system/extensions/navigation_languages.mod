<?php

$oSite = Cms\Entity\Site::getInstance($page_data['site_id']);

$arrLanguages = $oSite->getLanguages();

$objWebDynamicsDAO = new Cms\Helper\Data;

$arrPageData = $objWebDynamicsDAO->getPageData($page_data['id']);

foreach((array)$arrLanguages as $intKey=>$arrLanguage) {

	$sLink = false;
	
	if(!empty($file_data['parameters'])) {
		// dynamischer Link
		$oDynamicRouting = \Factory::getObject('\Cms\Service\DynamicRouting');
		$sLink = $oDynamicRouting->getLink($arrLanguage['code'].'-'.implode('_', $file_data['parameters']));
	} 
	
	if($sLink === false) {
		if($arrPageData['original_language'] == "") {
			$oPage = Cms\Entity\Page::getInstance($page_data['id']);
			$sLink = $oPage->getLink($arrLanguage['code']);
		} else {
			$sLink = "/".$arrLanguage['code']."/";
		}
	}

	$arrLanguage['link'] = $sLink;
	
	$arrLanguages[$intKey] = $arrLanguage;
}

$objSmarty = new \Cms\Service\Smarty();

$objSmarty->assign('arrLanguages', $arrLanguages);
$objSmarty->assign('strLanguage', $page_data['language']);

$objSmarty->displayExtension($element_data);
