<?php

namespace TsRdstation\Controller;

use \League\OAuth2\Client\Token\AccessToken;

class SetupController extends \MVC_Abstract_Controller {

    protected $_sAccessRight = 'core_external_apps';

	public function forward() {
		
		$oBundleHelper = new \Core\Helper\Bundle();
		$aBundleConfig = $oBundleHelper->getBundleConfigData('TsRdstation');

		// Erst auf den Proxy, damit der einen Cookie mit der Installation setzen kann. Der Proxy leitet dann zu OAuth von RD Station weiter
		$sUrl = \Util::getProxyHost().'rdstation/forward?client_id='.$aBundleConfig['rdstation']['client_id'].'&installation='.\Util::getHost();
		
		$this->redirectUrl($sUrl, false);
		
	}

	public function callback() {

		$log = \Log::getLogger('api', 'rdstation');
		
		$oProvider = \TsRdstation\Service\RDStation::getProvider();
		
		$oAccessToken = $oProvider->getAccessToken('authorization_code', [
            'code' => $this->_oRequest->get('code')
        ]);
		
		\System::s('rdstation_access_token', serialize($oAccessToken));
		
		$oSession = \Core\Handler\SessionHandler::getInstance();
		
		if(
			$oAccessToken instanceof AccessToken &&
			!$oAccessToken->hasExpired()
		) {
			
			$log->info('Connection established');
			
			$oSession->getFlashBag()->add('success', \L10N::t('RD Station wurde erfolgreich verbunden!'));
		} else {
			
			$log->error('Connection failed');
			
			$oSession->getFlashBag()->add('error', \L10N::t('RD Station konnte nicht verbunden werden!'));
		}
		
		return redirect()->away('/external-apps/app/edit/rdstation');
	}
	
}
