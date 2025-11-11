<?php

namespace TsCanvas\Controller;

use TsHubspot\Service\Agency;
use Core\Handler\SessionHandler as Session;
use TsHubspot\Service\Exceptions\ApiException;
use TsHubspot\Service\SetupHubspot;
use SevenShores\Hubspot\Http\Client;
use SevenShores\Hubspot\Resources\OAuth2;
use SevenShores\Hubspot\Factory;

/**
 * Class HubspotController
 *
 * Api Klasse zum Aufrufen der API mit entsprechendem Key
 *
 * @package TsHubspot\Controller
 */
class ApiController extends \MVC_Abstract_Controller {

    /**
     * Es wird kein Zugriffsrecht benötigt.
     * Da der Zugriff von den Webhooks von Hubspot ausgelöst werden.
     *
     * @var string
     */
    protected $_sAccessRight = '';

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

	function __construct($sExtension, $sController, $sAction, $oAccess = null) {

		parent::__construct($sExtension, $sController, $sAction, $oAccess);

		$this->oLogger = \Log::getLogger('api', 'hubspot');

	}

	public function activateHubspot() {

		$sRedirectUri = 'https://'. \Util::getSystemHost().'/admin/hubspot/redirect';
		$oClient = new Client(['key' => self::CLIENT_SECRET]);
		$oAuth = new OAuth2($oClient);

		$sUrl = $oAuth->getAuthUrl(self::CLIENT_ID, $sRedirectUri, ['contacts']);

		$this->oLogger->addInfo('Redirecting to Hubspot...', ['hubspot_auth_url' => $sUrl]);

		$this->redirectUrl($sUrl, false);

	}

	public function handleRedirectFromHubspot() {

		$sRedirectUri = 'https://'. \Util::getSystemHost().'/admin/hubspot/redirect';
		$oClient = new Client(['key' => self::CLIENT_SECRET]);
		$oAuth = new OAuth2($oClient);

		$this->oLogger->addInfo('Starting... getting tokens by code.', ['request' => $this->_oRequest->getAll()]);

		$this->oSession = Session::getInstance();

		if(!empty($this->_oRequest->get('code'))) {

			$oTokens = $oAuth->getTokensByCode(self::CLIENT_ID, self::CLIENT_SECRET, $sRedirectUri, $this->_oRequest->get('code'));

			$this->oLogger->addInfo('Tokens data: ', ['tokens', [$oTokens]]);

			if(isset($oTokens->data)) {

				$dExpirationTime = new \DateTime();
				$dExpirationTime->modify('+6 hours');
				$sExpirationTime = $dExpirationTime->format('Y-m-d H:i:s');

				\System::s('hubspot_access_token', $oTokens->data->access_token);
				\System::s('hubspot_refresh_token', $oTokens->data->refresh_token);
				\System::s('hubspot_token_expiration', $sExpirationTime);

				$bConnectionSuccessful = true;

				try{

					$this->oLogger->addInfo('Initialising synchronisation');
					$oSetupHubspot = new SetupHubspot();
					$oSetupHubspot->init();

				} catch (ApiException $ex) {

					$bConnectionSuccessful = false;
					$this->oLogger->addError('Synchronisation failed!', [$ex->getMessage()]);

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

		$this->redirect('ts_external_apps', ['sApp' => 'hubspot'], false);

	}

	/**
     * Wird bei einem Api-Call ausgeführt.
     *
     * @return void
     */
	public function postLoadAction() {

		try {

			$oAgencyApi = new Agency();

			$this->oLogger->addInfo('Handling incoming api call...', ['request' => $this->getRequest()->getAll()]);
			$oAgencyApi->incomingApiCall($this->getRequest());

		} catch(\Exception $ex) {
			$this->oLogger->addError($ex->getMessage());
		}

	}

}