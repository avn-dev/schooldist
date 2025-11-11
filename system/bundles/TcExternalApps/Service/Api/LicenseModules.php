<?php

namespace TcExternalApps\Service\Api;

class LicenseModules extends \Licence\Service\Office\Api\AbstractObject {
		
	public function getUrl() {
		return '/customer/api/apps/list';
	}

	public function prepareRequest(\Licence\Service\Office\Api\Request $oRequest) {
		
	}

}
