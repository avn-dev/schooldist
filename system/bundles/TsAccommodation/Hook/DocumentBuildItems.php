<?php

namespace TsAccommodation\Hook;

class DocumentBuildItems {
	
	public function run(&$mInput) {

		if(\TcExternalApps\Service\AppService::hasApp(\TsAccommodation\Handler\ExternalApp\CityTax::APP_NAME)) {
			$version = $mInput['version'];
			$document = $version->getDocument();

			$cityTax = new \TsAccommodation\Service\CityTax;
			$cityTax->handle($mInput['items'], $document);
		}
		
	}
	
}
