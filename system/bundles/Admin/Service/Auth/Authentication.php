<?php

namespace Admin\Service\Auth;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class Authentication extends AbstractAuthentication {

	/**
	 * @var AbstractAuthentication 
	 */
	private $oAddon;

	/**
	 * @param \MVC_View_Smarty $oView
	 */
	public function prepareView(\MVC_View_Smarty $oView) {

		parent::prepareView($oView);
		
		if($this->oAddon !== null) {
			$this->oAddon->prepareView($oView);
		} else {
			$oView->setTemplate('system/bundles/Admin/Resources/views/authentication/simple.tpl');
		}

		$aLanguages = \System::getBackendLanguages(true);

		$oView->set('aLanguages', $aLanguages);
		$oView->set('oAccess', $this->aViewValues['access']);
		$oView->set('sUsername', $this->aViewValues['username']??null);
		$oView->set('bForce', $this->aViewValues['force']);

	}	

	public function getSession() {
		return $this->oSession;
	}

	public function hasAddon(): bool {

		return $this->oAddon !== null;

	}

	/**
	 * @param AbstractAuthentication $oAuthenticationAddon
	 */
	public function setAddon(AbstractAuthentication $oAuthenticationAddon) {
		
		$this->oAddon = $oAuthenticationAddon;
		
	}

	public function getAddon(): ?AbstractAuthentication {
		return $this->oAddon;
	}

	/**
	 * @param Request $oRequest
	 */
	public function handleAddonRequest(Request $oRequest, bool $isViewRequest = true) {
		
		if($this->oAddon instanceof AbstractAuthentication) {
			return $this->oAddon->handleRequest($oRequest, $isViewRequest);
		}
		
	}
	
	/**
	 * @param Request $oRequest
	 */
	public function handleRequest(Request $oRequest, bool $isViewRequest = true): ?RedirectResponse {

		$bForceLogin = (bool)$oRequest->get('force', 0);

		$this->aViewValues['force'] = $bForceLogin;

		// Login durchführen?
		if(
			$oRequest->exists('login') &&
			$oRequest->get('login') === 'ok' &&
			$this->oAccess->checkExecuteLogin()
		) {
			if (!empty($passkey = $oRequest->input('passkey'))) {
				$aLoginData = [
					'username' => $oRequest->input('username'),
					'passkey' => $passkey,
					'host' => $oRequest->host(),
					'force' => $bForceLogin
				];
			} else {
				$aLoginData = [
					'username' => $oRequest->input('username'),
					'password' => $oRequest->input('password'),
					'force' => $bForceLogin
				];
			}

			$this->aViewValues['username'] = $aLoginData['username'];

			$this->oAccess->executeLogin($aLoginData, $this->oSession);

		} else {

			// Access-Object aus Session laden falls vorhanden
			if(
				$this->oAccess->checkValidAccess() === false &&
				$this->oSession->has('admin_access')
			) {
				
				$this->oAccess = &$this->oSession->get('admin_access');
				
			}

		}

		$this->aViewValues['access'] = $this->oAccess;

		return null;
	}

	public function generatePasskeyChallenge(string $host, string $username = null): array
	{
		return $this->oAccess->generatePasskeyChallenge($this->oSession, $host, $username);
	}
}