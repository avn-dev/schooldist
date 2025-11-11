<?php

namespace Cms\Controller;

class FaviconController extends \MVC_Abstract_Controller {
	
	protected $_sInterface = 'frontend';
	protected $_sAccessRight = null;
	
	public function generate($iSiteId) {

		$oSite = \Cms\Entity\Site::getInstance($iSiteId);

		header("Content-type: image/x-icon");

		$sFaviconPath = \Util::getDocumentRoot().'storage/favicon.ico';
		$sSiteFaviconPath = \Util::getDocumentRoot().'storage/favicon_'.(int)$oSite->id.'.ico';

		\System::wd()->executeHook('favicon_path', $sSiteFaviconPath);

		if(is_file($sSiteFaviconPath)) {
			$sFaviconPath = $sSiteFaviconPath;
		}

		if(is_file($sFaviconPath)) {
			$fp = @fopen($sFaviconPath, 'rb');
			@fpassthru($fp);
			@fclose($fp);
		} else {
			echo "404 Not Found";
			header("HTTP/1.0 404 Not Found");
		}

		die();

	}
	
}