<?php

namespace Admin\Service\Auth\AuthenticationAddon;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class Simple extends \Admin\Service\Auth\AbstractAuthentication {

	/**
	 * @param \MVC_View_Smarty $oView
	 */
	public function prepareView(\MVC_View_Smarty $oView) {

		parent::prepareView($oView);

		$oView->setTemplate('system/bundles/Admin/Resources/views/authentication/simple.tpl');
		
//		$oView->set('oAccess', $this->aViewValues['access']);
//		$oView->set('sUsername', $this->aViewValues['username']);
		
	}
	
	/**
	 * @param Request $oRequest
	 */
	public function handleRequest(Request $oRequest, bool $isViewRequest = true): ?RedirectResponse {

		if($this->oAccess->checkValidAccess()) {

			return $this->handleLogin($oRequest);

		}

		return null;
	}
	
}