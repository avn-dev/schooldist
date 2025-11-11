<?php

namespace Cms\Entity;

class Snippet extends \WDBasic {
	
	protected $_sTable = 'cms_snippets';
	protected $_sTableAlias = 'cms_s';
	
	static public function getContent($sPlaceholder, $sLanguage) {
		
		$oSnippet = self::getRepository()->findOneBy(['placeholder' => $sPlaceholder]);
		
		$aReturn = "";
		if(empty($oSnippet)) {
			$aReturn = 'Der Platzhalter konnte nicht gefunden werden.';
		} else {		
			$aReturn = $oSnippet->$sLanguage;
		}

		return $aReturn;
	}
	
}