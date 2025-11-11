<?php

namespace Cms\Controller;

class RobotsController extends \MVC_Abstract_Controller {
	
	protected $_sInterface = 'frontend';
	protected $_sAccessRight = null;
	
	public function generate($iSiteId) {

		$oSite = \Cms\Entity\Site::getInstance($iSiteId);
		
		$aPath = [];
		$aPath['site_robots'] = \Util::getDocumentRoot().'storage/robots_'.(int)$iSiteId.'.txt';

		\System::wd()->executeHook('robots_txt', $aPath);

		if(is_file($aPath['site_robots'])) {
			$aPath['robots'] = $aPath['site_robots'];
		}

		if(is_file($aPath['robots'])) {
			$fp = @fopen($aPath['robots'], 'rb');
			@fpassthru($fp);
			@fclose($fp);
			die();
		}

		header("Content-Type: text/plain");
		echo "User-agent: *
Disallow: /system/
Disallow: /admin/
Sitemap: ".$oSite->getFullDomain().'/sitemap.xml';
		die();		

	}
	
}