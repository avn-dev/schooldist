<?php

namespace Cms\Service;

class Smarty extends \SmartyWrapper {
	
	public function __construct() {
		global $page_data;

		parent::__construct();
		
		$this->registerPlugin("modifier", 'getPageUrl', '\Cms\Service\Smarty::getPageUrl');
		$this->assign('page_data', $page_data);

	}

	static public function getPageUrl($iPageId, $sLanguage=null) {
		
		$oPage = \Cms\Entity\Page::getInstance($iPageId);
		
		$sLink = $oPage->getLink($sLanguage, true);
		
		return $sLink;
	}
	
}
