<?php

namespace TsAccommodation\Hook;

use \TsAccommodation\Handler\ExternalApp\CityTax as CityTaxApp;

class DocumentPositionDetailSave {
	
	public function run(&$aNewPosition, &$aPosition) {
		
		if(\TcExternalApps\Service\AppService::hasApp(\TsAccommodation\Handler\ExternalApp\CityTax::APP_NAME)) {

			if(isset($aNewPosition['city_tax'])) {
				$aPosition['additional_info']['city_tax'][0]['city_tax'] = (float)\Ext_Thebing_Format::convertFloat($aNewPosition['city_tax'])/100;
			}
			
			if(isset($aNewPosition['calculate_max_days'])) {
				$aPosition['additional_info']['city_tax'][0]['calculate_max_days'] = (int)$aNewPosition['calculate_max_days'];
			}
			
		}
		
		
	}
	
}
