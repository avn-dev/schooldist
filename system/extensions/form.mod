<?php

$oConfig = new \Cms\Helper\ExtensionConfig($element_data['page_id'], $element_data['id'], $element_data['content_id'], $element_data['language']);

if($oConfig->form_id > 0) {

	$oForm = \Form\Entity\Init::getInstance($oConfig->form_id);

	if($oConfig->use_smarty) {
		$sClass = '\Form\Service\Frontend\Smarty';
	} else {
		$sClass = '\Form\Service\Frontend\Block';
	}

	$bSessionNotFound = false;

	/* @var $oFormService \Form\Service\Frontend */
	if(
		$oRequest->get('fo_action_'.$element_data['content_id']) == 'send' ||
		$oRequest->get('fo_action_'.$element_data['content_id']) == 'show'
	) {
		$sInstanceHash = $oRequest->get('fo_instance_hash');
		$oFormService = $sClass::getSessionInstance($sInstanceHash);

		if(empty($oFormService)) {
			$bSessionNotFound = true;
		}

	} 

	if(empty($oFormService)) {
		$sInstanceHash = Util::generateRandomString(32);
		$oFormService = new $sClass($sInstanceHash);
	}

	$oFormService->setForm($oForm);
	
	$oFormService->initSpamShield($oRequest);
	
	$oFormService->setElementData($element_data);

	$aPages = $oForm->getJoinedObjectChilds('pages');

	if($bSessionNotFound === true) {

		$oFormService->setMessage(L10N::t('Ihre Formular-Session ist bereits abgelaufen oder wurde bereits abgeschickt.'));

		$oCurrentPage = reset($aPages);
		$oFormService->setPage($oCurrentPage);

	} else {

		if($oRequest->exists('fo_page_id')) {
			$iRequestPageId = (int)$oRequest->get('fo_page_id');
			if(isset($aPages[$iRequestPageId])) {
				$oCurrentPage = $aPages[$iRequestPageId];
			} else {
				throw new RuntimeException('Invalid form page "'.$iRequestPageId.'"');
			}
		} else {
			$oCurrentPage = reset($aPages);
		}

		$oFormService->setPage($oCurrentPage);

		$oFormService->handleRequest($oRequest);

	}

	$oFormService->parse();

	$oFormService->saveInSession();
	
} else {
	echo \L10N::t('Es wurde noch kein Formular ausgewählt!');
}