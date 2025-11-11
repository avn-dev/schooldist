<?php

namespace TsHubspot\Controller;

use Core\Handler\SessionHandler as Session;
use TsHubspot\Handler\ExternalApp;
use TsHubspot\Service\SetupHubspot;
use HubSpot\Factory;
use HubSpot\Utils\OAuth2;

/**
 * Class AppController
 *
 * Klasse zur Aktivierung und manuellen Synchronisierung mit Hubspot
 *
 * @package TsHubspot\Controller
 */
class AppController extends \MVC_Abstract_Controller {

    protected $_sAccessRight = 'core_external_apps';

	protected $_sViewClass = '\MVC_View_Smarty';

	/**
	 * @var Session
	 */
	protected $oSession;

	/**
	 * @var \Monolog\Logger|null
	 */
	protected $oLogger = null;

	/**
	 * @var string
	 */
	const CLIENT_ID = 'f1ddc729-1f63-417b-b8ac-00525a965540';

	/**
	 * @var string
	 */
	const CLIENT_SECRET = '7aae545f-d4c8-4bea-88ba-e6c3ff569ecd';

	/**
	 * @inheritDoc
	 */
	function __construct($sExtension, $sController, $sAction, $oAccess = null) {

		parent::__construct($sExtension, $sController, $sAction, $oAccess);

		$this->oLogger = \Log::getLogger('api', 'hubspot');

	}

	public function deactivateHubspot() {

		$hubspotConfigEntries = \DB::table('system_config')->where('c_key', 'LIKE', 'hubspot_%')->get();

		foreach ($hubspotConfigEntries as $hubspotConfigEntry) {
			\System::deleteConfig($hubspotConfigEntry['c_key']);
		}

		\WDCache::delete(ExternalApp::HUBSPOT_OBJECTS_CACHE_KEY);
		
		$this->redirect('TcExternalApps.edit', ['sAppKey' => 'hubspot'], false);
		
	}

	public function activateHubspot() {

		// Gibt es erstmal nicht ("Alles einmal synchronisieren")
//		if ($this->_oRequest->get('sync') == 'true') {
//			\System::s('hubspot_sync_all', 1);
//		} else {
//			\System::s('hubspot_sync_all', 0);
//		}

		$sRedirectUri = \Util::getProxyHost().'hubspot/auth-redirect';

		// "Rechte"
		$sUrl = OAuth2::getAuthUrl(
			self::CLIENT_ID,
			$sRedirectUri,
			[
				'crm.objects.contacts.read',
				'crm.objects.contacts.write',
				'crm.objects.companies.write',
				'crm.objects.companies.read',
				'crm.objects.deals.read',
				'crm.objects.deals.write',
				'crm.objects.custom.read',
				'crm.objects.custom.write',
			]
		);

		$sUrl .= '&state=https://'. \Util::getSystemHost().'/admin/hubspot/redirect';

		$this->oLogger->addInfo('Redirecting to Hubspot...', ['hubspot_auth_url' => $sUrl]);

		$this->redirectUrl($sUrl, false);

	}

	public function handleRedirectFromHubspot() {

		$sRedirectUri = \Util::getProxyHost().'hubspot/auth-redirect';

		$this->oLogger->addInfo('Starting... getting tokens by code.', ['request' => $this->_oRequest->getAll()]);

		$this->oSession = Session::getInstance();

		if(!empty($this->_oRequest->get('code'))) {

			$tokens = Factory::create()->oauth()->tokensApi()->create(
				'authorization_code',
				$this->_oRequest->get('code'),
				$sRedirectUri,
				self::CLIENT_ID,
				self::CLIENT_SECRET
			);

			$this->oLogger->addInfo('Tokens data: ', ['tokens', [$tokens]]);

			if(!empty($tokens->getAccessToken())) {

				$bConnectionSuccessful = true;

				try{

					$dExpirationTime = new \DateTime();
					$dExpirationTime->modify('+'.$tokens->getExpiresIn().' seconds');
					$sExpirationTime = $dExpirationTime->format('Y-m-d H:i:s');

					\System::s('hubspot_access_token', $tokens->getAccessToken());
					\System::s('hubspot_refresh_token', $tokens->getRefreshToken());
					\System::s('hubspot_token_expiration', $sExpirationTime);

					$this->oLogger->addInfo('Initialising synchronisation');
					$oSetupHubspot = new SetupHubspot();
					$oSetupHubspot->init();

				} catch (\Throwable $exception) {

					\System::deleteConfig('hubspot_token_expiration');
					\System::deleteConfig('hubspot_refresh_token');
					\System::deleteConfig('hubspot_access_token');

					if ($exception instanceof \HubSpot\Client\Crm\Objects\ApiException) {
						$errorMessage = $exception->getResponseBody();
					} else {
						$errorMessage = $exception->getMessage();
					}
					$this->oLogger->error('Synchronisation failed!', [$errorMessage]);

					$bConnectionSuccessful = false;
				} 

				if($bConnectionSuccessful) {

					$this->oLogger->addInfo('Synchronisation successful!');
					$this->oSession->getFlashBag()->add('success', \L10N::t('Hubspot wurde erfolgreich verlinkt!'));

				} else {
					$this->oSession->getFlashBag()->add('error', \L10N::t('Hubspot konnte nicht verlinkt werden!'));

				}

			} else {

				$this->oLogger->addError('No tokens sent by Hubspot!');
				$this->oSession->getFlashBag()->add('error', \L10N::t('Hubspot konnte nicht verlinkt werden, bitte versuchen sie es in 2 Minuten erneut!'));

			}

		} else {

			$this->oLogger->addError('No code sent by Hubspot!');
			$this->oSession->getFlashBag()->add('error', \L10N::t('Authentifizierungscode konnte nicht gefunden werden, bitte versuchen Sie es in 2 Minuten erneut!'));

		}

		$this->redirect('TcExternalApps.edit', ['sAppKey' => 'hubspot'], false);

	}

}