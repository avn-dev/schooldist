<?php

namespace TsAccommodation\Hook;

use \TsAccommodation\Handler\ExternalApp\CityTax as CityTaxApp;

class DocumentPositionDetailForm {
	
	public function run(string &$positionHtml, array $item, \Ext_Thebing_Document_Positions_Row $row, \Ext_Gui2_Dialog $dialog, string $fieldNamePrefix) {
		
		if(\TcExternalApps\Service\AppService::hasApp(\TsAccommodation\Handler\ExternalApp\CityTax::APP_NAME)) {
		
			if(isset($item['additional_info']['city_tax'])) {
				
				$school = \Ext_Thebing_School::getInstance($row->iSchoolId);
				
				$taxFactor = $school->getMeta(CityTaxApp::KEY_CITY_TAX_CURRENT)/100;
				$calculateDays = '';
				
				$firstCityTax = reset($item['additional_info']['city_tax']);
				$taxFactor = ($firstCityTax['city_tax'] ?? $taxFactor)*100;
				$calculateDays = $firstCityTax['calculate_max_days'] ?? $calculateDays;
				
				$positionHtml .= $dialog->createRow(\L10N::t('City-Tax in Prozent', \Ext_Thebing_Document::$sL10NDescription), 'input', [
					'value' => $taxFactor,
					'name' => $fieldNamePrefix.'[city_tax]',
					'inputdiv_class' => 'input-group-sm'
				])->generateHTML();
				
				$positionHtml .= $dialog->createRow(\L10N::t('Zu berechnende Tage', \Ext_Thebing_Document::$sL10NDescription), 'input', [
					'value' => $calculateDays,
					'name' => $fieldNamePrefix.'[calculate_max_days]',
					'inputdiv_class' => 'input-group-sm'
				])->generateHTML();
				
			}
			
		}
		
		
	}
	
}
