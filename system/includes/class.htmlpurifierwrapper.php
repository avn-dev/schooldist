<?php

/**
 * @depricated
 */
class HTMLPurifierWrapper extends HTMLPurifier {
	
	public function __construct($sMode='limited') {

		$oConfig = HTMLPurifier_Config::createDefault();
		$oConfig->set('Core.Encoding', 'UTF-8'); // replace with your encoding
		$oConfig->set('HTML.Doctype', 'XHTML 1.0 Strict'); // replace with your doctype
		switch($sMode) {
			case 'all':
				break;
			case 'limited':
			default:

				$oConfig->set('HTML.Allowed', 'a[href|title|onclick],em,p,blockquote,b,strong,br,li,ol,ul,h1,h2,h3,i,img[src|alt]');
				$oDef = $oConfig->getHTMLDefinition(true);
				$oDef->addAttribute('a', 'onclick', 'CDATA');
				
				break;
		}

		// call parent constructor
		parent::__construct($oConfig);

	}
	
}