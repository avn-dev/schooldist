<?php

namespace Cms\Controller;

use Thepixeldeveloper\Sitemap\Urlset;
use Thepixeldeveloper\Sitemap\Url;
use Thepixeldeveloper\Sitemap\Drivers\XmlWriterDriver;

class SitemapController extends \MVC_Abstract_Controller {

	protected $_sInterface = 'frontend';
	protected $_sAccessRight = null;

	/**
	 * @todo Caching einbauen
	 * @param int $iSiteId
	 */
	public function generate($iSiteId) {
		
		$oSite = \Cms\Entity\Site::getInstance($iSiteId);
		$sDomain = $oSite->getMainDomain();
		
		$oUrlSet = new Urlset();
		
		// Routen aktualisieren
		$oRoutingService = new \Cms\Service\Routing();
		$aCmsRoutes = $oRoutingService->buildRoutes(true);

		$uniqueCheck = [];
		
		foreach($aCmsRoutes as $oRoute) {

			// Nur diese Site!
			if($oRoute->getDefault('iSiteId') != $oSite->id) {
				continue;
			}

			$aController = $oRoute->getController('_controller');
			if($aController[1] == 'redirectPage') {
				continue;
			}

			$sFullDomain = $oSite->getFullDomain();
			$sUrl = $sFullDomain.$oRoute->getPath();
			
			if(isset($uniqueCheck[$sUrl])) {
				continue;
			}
			
			$uniqueCheck[$sUrl] = 1;
			
			$page = \Cms\Entity\Page::getInstance($oRoute->getDefault('iPageId'));
			
			if(
				$page && 
				$page->search != 1
			) {
				continue;
			}
			
			$oUrl = new Url($sUrl);
		
			#$oUrl->setLastMod($lastMod);
			#$oUrl->setChangeFreq($changeFreq);
			#$oUrl->setPriority($priority);
		
			$oUrlSet->add($oUrl);
			
		}

		\System::wd()->executeHook('cms_sitemap_generator', $oUrlSet);

		$oDriver = new XmlWriterDriver();
		$oUrlSet->accept($oDriver);

		header('Content-Type: application/xml');
		
		echo $oDriver->output();
		die();
		
	}
	
}
