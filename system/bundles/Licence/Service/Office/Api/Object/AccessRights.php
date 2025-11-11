<?php

namespace Licence\Service\Office\Api\Object;

class AccessRights extends \Licence\Service\Office\Api\AbstractObject {

	public function getUrl() {
		return '/customer/api/licence/modules/access/structure';
	}

	/**
	 * Alle nötigen Request-Parameter setzen
	 *
	 * @param \Licence\Service\Office\Api\Request $oRequest
	 */
	public function prepareRequest(\Licence\Service\Office\Api\Request $oRequest) {
		// no parameters
	}

}

