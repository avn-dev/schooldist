<?php

namespace Admin\Factory\Auth;

use Core\Handler\SessionHandler as Session;
use Core\Helper\Bundle as BundleHelper;

class AuthenticationAddon {
	
	/**
	 * @var \Access_Backend 
	 */
	private $oAccess;

	public function __construct(\Access_Backend $oAccess) {
		
		$this->oAccess = $oAccess;
		
	}
	
	/**
	 * 
	 * @param Session $oSession
	 * @return \Admin\Service\Auth\AbstractAuthentication
	 * @throws \RuntimeException
	 */
	public function getAddon(Session $oSession) {

		$aBundleConfig = (new BundleHelper())->getBundleConfigData('Admin');

		/*
		 * @todo PROBLEM BEI DER USER IM LOGIN REQUEST HIER NOCH NICHT EINGELOGGT IST
		 */
		if($this->oAccess->checkValidAccess()) {
			
			$sAuthentication = $this->oAccess->authentication;

			if(empty($aBundleConfig['authenticators'][$sAuthentication])) {
				throw new \RuntimeException('Invalid authentication "'.$sAuthentication.'"!');
			}

			$sAuthenticationClass = $aBundleConfig['authenticators'][$sAuthentication];

			$oAuthentication = new $sAuthenticationClass($this->oAccess, $oSession);

			return $oAuthentication;
			
		}
		
		return null;
	}
	
}