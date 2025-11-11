<?php

namespace Cms\Service;

class Routing {

	private $aSites = [];
	private $aSiteDomains = [];
	private $aSiteLanguages	 = [];
	
	private function getSitesData() {
		
		$aSites = \Cms\Entity\Site::getRepository()->findBy(['active'=>1]);
		
		$this->aSites = [];
		$this->aSiteDomains = [];
		$this->aSiteLanguages = [];

		foreach($aSites as $oSite) {
			$this->aSites[$oSite->id] = $oSite;
			$this->aSiteDomains[$oSite->id] = implode('|', $oSite->domains);
			$this->aSiteLanguages[$oSite->id] = $oSite->getLanguages(1);
		}
	}


	private function buildPageRoutes(array &$aRoutes) {
		
		/*
		 * Nur öffentliche, also von außen erreichbare Seiten per Route öffnen
		 * Seiten die als Zielseite einer dynamischen Route dienen auch ausschliessen
		 */
		$sSql = "
			SELECT 
				`cp`.* 
			FROM
				`cms_pages` `cp` LEFT JOIN 
                `cms_dynamic_routings` `cdr` ON
                	`cp`.`id` = `cdr`.`page_id` AND
                    `cdr`.`active` = 1
			WHERE 
				cp.`active` = 1 AND
				cp.`element` != 'template' AND
                `cdr`.`id` IS NULL
		";
		$aSql = [];
		$aPages = (array)\DB::getQueryRows($sSql, $aSql);
		
		foreach($aPages as $aPage) {

			$oPage = \Cms\Entity\Page::getObjectFromArray($aPage);
						
			$sLanguage = $oPage->language;
			
			$aPageLanguages = [];
			if(!empty($sLanguage)) {
				$aPageLanguages = [$sLanguage];
			} else {
				$aPageLanguages = $this->aSiteLanguages[$oPage->site_id];
			}
			
			$oSite = $this->aSites[$oPage->site_id];
			
			if(empty($aPageLanguages)) {
				continue;
			}
			
			foreach($aPageLanguages as $sLanguage) {
				
				if(!empty($this->aSiteDomains[$oPage->site_id])) {
				
					$oRoute = new \Core\Model\DynamicRoute();
					
					$sLink = $oPage->getLink($sLanguage);
				
					// Keine valide Seite (TODO: Fehler abfangen)
					if($sLink === null) {
						continue;
					}
					
					if($oPage->file === 'index') {
						$oRoute->setController('Cms\Controller\PageController', 'redirectPage');
					} else {
						$oRoute->setController('Cms\Controller\PageController', 'outputPage');
					}

					$oRoute->setName('cms_page_'.$aPage['id'].'_'.$sLanguage);
					$oRoute->setPath($sLink);
					$oRoute->setDefault('iPageId', $oPage->id);
					$oRoute->setDefault('iSiteId', $oPage->site_id);
					$oRoute->setDefault('sLanguageIso', $sLanguage);
					$oRoute->setHost('{hosts}');
					$oRoute->setRequirements(['hosts'=>$this->aSiteDomains[$oPage->site_id]]);
					
					$aRoutes[] = $oRoute;
					
				}

			}

		}
		
	}

	private function buildDynamicRoutes(array &$aRoutes) {
		
		$oDynamicRouting = \Factory::getObject('\Cms\Service\DynamicRouting');

		$aLinks = $oDynamicRouting->buildLinks();

		if(empty($aLinks)) {
			return;
		}
		
		foreach($aLinks as $aLink) {
			
			if(!isset($aLink['key'])) {
				throw new InvalidArgumentException('Missing "key" in dynamic route settings!');
			}
			
			$oRoute = new \Core\Model\DynamicRoute();

			$oRoute->setController('Cms\Controller\PageController', 'outputPage');

			$oDynamicRouting = \Cms\Entity\DynamicRouting::getInstance($aLink['dynamic_routing']);

			$oRoute->setName('cms_dynamic_route_'.$oDynamicRouting->id.'_'.$oDynamicRouting->page_id.'_'.$oDynamicRouting->language_iso.'_'.$aLink['key']);
			$oRoute->setPath($aLink['link']);
			$oRoute->setDefault('iPageId', $oDynamicRouting->page_id);
			$oRoute->setDefault('iSiteId', $oDynamicRouting->site_id);
			$oRoute->setDefault('sLanguageIso', $oDynamicRouting->language_iso);
			$oRoute->setDefault('aParameters', $aLink);
			$oRoute->setHost('{hosts}');
			$oRoute->setRequirements(['hosts'=>$this->aSiteDomains[$oDynamicRouting->site_id]]);
			
			$aRoutes[] = $oRoute;
		}
		
	}

	private function buildTechnicalPagesRoutes(array &$aRoutes) {
		
		foreach($this->aSites as $oSite) {

			// Ohne Domains gibt es keine Routen
			if(empty($this->aSiteDomains[$oSite->id])) {
				continue;
			}
		
			// sitemap.xml
			$oRoute = new \Core\Model\DynamicRoute();

			$oRoute->setController('Cms\Controller\SitemapController', 'generate');

			$oRoute->setName('cms_sitemap_'.$oSite->id);
			$oRoute->setPath('/sitemap.xml');
			$oRoute->setDefault('iSiteId', $oSite->id);
			$oRoute->setHost('{hosts}');
			$oRoute->setRequirements(['hosts'=>$this->aSiteDomains[$oSite->id]]);

			$aRoutes[] = $oRoute;

			// robots.txt
			$oRoute = new \Core\Model\DynamicRoute();

			$oRoute->setController('Cms\Controller\RobotsController', 'generate');

			$oRoute->setName('cms_robots_'.$oSite->id);
			$oRoute->setPath('/robots.txt');
			$oRoute->setDefault('iSiteId', $oSite->id);
			$oRoute->setHost('{hosts}');
			$oRoute->setRequirements(['hosts'=>$this->aSiteDomains[$oSite->id]]);

			$aRoutes[] = $oRoute;

			// Favicon
			if($oSite->favicon_active == 1) {
				
				$oRoute = new \Core\Model\DynamicRoute();

				$oRoute->setController('Cms\Controller\FaviconController', 'generate');

				$oRoute->setName('cms_favicon_'.$oSite->id);
				$oRoute->setPath('/favicon.ico');
				$oRoute->setDefault('iSiteId', $oSite->id);
				$oRoute->setHost('{hosts}');
				$oRoute->setRequirements(['hosts'=>$this->aSiteDomains[$oSite->id]]);

				$aRoutes[] = $oRoute;
				
			}
			
		}

	}
	
	private function buildRedirectRoutes(array &$aRoutes) {
		
		$aRedirects = \Cms\Entity\Redirection::getRepository()->findAll();

		if(empty($aRedirects)) {
			return;
		}
		
		foreach($aRedirects as $oRedirect) {
			
			$oRoute = new \Core\Model\DynamicRoute();

			$oRoute->setController('Cms\Controller\PageController', 'redirectUrl');

			$oRoute->setName('cms_redirect_'.$oRedirect->id);
			$oRoute->setPath($oRedirect->url);
			$oRoute->setDefault('sUrl', $oRedirect->target);
			if($oRedirect->return_code === 'http_301') {
				$oRoute->setDefault('bPermanent', true);	
			} else {
				$oRoute->setDefault('bPermanent', false);	
			}
			if($oRedirect->qsa == 1) {
				$oRoute->setDefault('bQSA', true);	
			} else {
				$oRoute->setDefault('bQSA', false);	
			}

			$aRoutes[] = $oRoute;
		}

	}

	public function buildRoutes($bSkipTechnicalPages=false) {

		$this->getSitesData();
		
		$aRoutes = [];
		
		// Normale Seiten
		$this->buildPageRoutes($aRoutes);
		
		// Dynamic Routing
		$this->buildDynamicRoutes($aRoutes);
		
		// Redirects
		$this->buildRedirectRoutes($aRoutes);

		if($bSkipTechnicalPages === false) {

			// Sitemap-File
			$this->buildTechnicalPagesRoutes($aRoutes);
			
		}

		return $aRoutes;
	}

	public function getCacheKey($oPage, $sLanguage, $aParameters) {
		
		$sCacheKey = 'cms_dynamic_routing_page_cache_'.$oPage->id.'_'.$sLanguage.'_'.json_encode($aParameters);
	
		return $sCacheKey;
	}
		
}
