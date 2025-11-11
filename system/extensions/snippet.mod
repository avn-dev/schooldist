<?php

require_once \Util::getDocumentRoot().'system/extensions/snippet/class.snippet.php';

/**
 * oConfig contains the configuration data
**/
try {
	if(
		!empty($_VARS['get_file']) ||
		!empty($_VARS['get_request'])
	){
		ob_end_clean();
	}

	$oConfig = new \Cms\Helper\ExtensionConfig($element_data['page_id'], $element_data['id'], $element_data['content_id'], $element_data['language']);

	$sCombinationKey	= $oConfig->combination_key;
	$sTemplateKey		= $oConfig->template_key;
	$sUrl				= $oConfig->url;

	if(
		!empty($_VARS['get_file']) ||
		!empty($_VARS['get_request'])
	){
		ob_end_clean();
	}

	$oSnippet = new Thebing_Snippet($sUrl, $sCombinationKey, $sTemplateKey);
	$oSnippet->execute();

	echo $oSnippet->getContent();

	if(
		!empty($_VARS['get_file']) ||
		!empty($_VARS['get_request'])
	){
		die();
	}
} catch (Exception $exc) {
  __pout($exc);
}
