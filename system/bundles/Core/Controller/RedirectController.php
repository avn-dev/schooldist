<?php

namespace Core\Controller;

class RedirectController extends \MVC_Abstract_Controller {
	
	protected $_sAccessRight = null;

	public function route(string $sRoute = "Admin.admin", bool $bPermanent = true) {

		$this->redirect($sRoute, [], $bPermanent);
		
	}

	public function url(string $sUrl, bool $bPermanent = false) {
		
		$this->redirectUrl($sUrl, $bPermanent);
		
	}
	
}