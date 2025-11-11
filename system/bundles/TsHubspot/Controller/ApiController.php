<?php

namespace TsHubspot\Controller;

/**
 * Class ApiController
 *
 * Api Klasse zum Behandeln der Aufrufe von Hubspot-Api-webhooks
 *
 * @package TsHubspot\Controller
 */
class ApiController extends \MVC_Abstract_Controller {

	protected $_sAccessRight = '';

	/**
	 * @var \Monolog\Logger|null
	 */
	protected $oLogger = null;

	/**
	 * @inheritDoc
	 */
	function __construct($sExtension, $sController, $sAction, $oAccess = null) {

		parent::__construct($sExtension, $sController, $sAction, $oAccess);

		$this->oLogger = \Log::getLogger('api', 'hubspot');

	}

	/**
	 * Wird bei einem Api-Call ausgeführt.
	 *
	 * @return void
	 */
	public function postLoadAction() {

		try {

			$this->oLogger->addInfo('Handling incoming api call...', ['request' => $this->getRequest()->getAll()]);
//			$oApi->incomingApiCall($this->getRequest());

		} catch (\Throwable $exception) {
			if ($exception instanceof \HubSpot\Client\Crm\Objects\ApiException) {
				$errorMessage = $exception->getResponseBody();
			} else {
				$errorMessage = $exception->getMessage();
			}
			$this->oLogger->error('Handling incoming api call failed', [$errorMessage]);

		}

	}

}





























