<?php

namespace TsHubspot\Service;

/**
 * Class Api
 * @package TsHubspot\Service
 */
class Api {

	/**
	 * Api-Package für Hubspot
	 *
	 * @var Factory
	 */
	public $oHubspot = null;

	/**
	 * @var \Monolog\Logger|null
	 */
	protected $oLogger = null;

	public function __construct() {
		$this->oLogger = \Log::getLogger('api', 'hubspot');
		$this->oLogger->addInfo('Creating Hubspot Api object with the refreshed token or getting an existing one');
		$this->oHubspot = ApiCreate::createAPIObject();
	}

}