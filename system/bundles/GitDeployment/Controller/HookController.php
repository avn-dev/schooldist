<?php

namespace GitDeployment\Controller;

class HookController extends \MVC_Abstract_Controller {

	protected $_sAccessRight = null;

	/**
	 * Dient nur zum Anstoßen des Services
	 */
	public function pullAction() {

		$hashBefore = $hashAfter = null;
		if($this->_oRequest->has('hash_before')) {
			$hashBefore = $this->_oRequest->get('hash_before');
		}

		$oDeploy = new \GitDeployment\Service\Deploy();	
		$oDeploy->handleExecute($hashBefore);

	}

}