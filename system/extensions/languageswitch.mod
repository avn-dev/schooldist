<?PHP

// Nur ausführen, wenn nicht im CMS
if(!$user_data['cms']) {

	$oSite = Cms\Entity\Site::getInstance($page_data['site_id']);
	
	$config = new \Cms\Helper\ExtensionConfig($element_data['page_id'], $element_data['id'], $element_data['content_id'], $element_data['language']);

	$aLanguages = array();
	$aItems = $oSite->getLanguages();
	foreach((array)$aItems as $aData) {
		$aLanguages[$aData['code']] = $aData['code'];
	}

	$sLanguage = \Core\Helper\Agent::getBrowserLanguage(array_flip($aLanguages), $config->defaultlanguage);

	$mTarget = $config->target[$sLanguage];

	if(is_numeric($mTarget)) {
		$oTargetPage = Cms\Entity\Page::getInstance($mTarget);
		$mTarget = $oTargetPage->getLink($sLanguage);
	}

	header("Location: ".$mTarget, true, 302);

}