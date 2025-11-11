<?php

namespace Cms\Controller;

/**
 * Controller für die Anzeige der Website
 */
class PageController extends AbstractPageController {
	
	protected $_sInterface = 'frontend';
	protected $_sAccessRight = null;
	
	public function outputPage($iPageId, $iSiteId, $sLanguageIso, $aParameters=[]) {

		$oPage = \Cms\Entity\Page::getInstance($iPageId);

		$this->generatePage($oPage, $sLanguageIso, $aParameters);
		
	}

	public function redirectPage($iPageId, $iSiteId, $sLanguageIso, $aParameters=[]) {

		$oPage = \Cms\Entity\Page::getInstance($iPageId);

		$sSql = "
			SELECT 
				*
			FROM 
				`cms_pages` 
			WHERE 
				`element` != 'template' AND
				`site_id` = :site_id AND
				`path` = :path AND 
				`indexpage` = 1 AND 
				(
					`language` = :language OR 
					`language` = ''
				)
			LIMIT 1";
		$aSql = [
			'site_id' => (int)$oPage->site_id,
			'path' => $oPage->path,
			'language' => $sLanguageIso
		];
		$aIndexPage = \DB::getQueryRow($sSql, $aSql);
		
		// Darf nicht passieren
		if(empty($aIndexPage)) {
			$this->redirectUrl('/');
		} else {
			$oIndexPage = \Cms\Entity\Page::getObjectFromArray($aIndexPage);
		
			$this->redirectUrl($oIndexPage->getLink($sLanguageIso));
		}
		
	}
	
}