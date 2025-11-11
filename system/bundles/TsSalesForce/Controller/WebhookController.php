<?php

namespace TsSalesForce\Controller;

class WebhookController extends \MVC_Abstract_Controller {
	
	/**
	 * Controller hat kein CMS-Recht
	 * @var null
	 */
	protected $_sAccessRight = null;

	public function call($sObject) {
		
		$oLog = \Log::getLogger('ts_salesforce');
		
		$oLog->addInfo('New request', [$_SERVER, $this->_oRequest->getAll(), $this->_oRequest->getJSONDecodedPostData(), $this->_oRequest->getPostData]);
		
		$this->set('request', $this->_oRequest->getAll());
		
	}
	
}
