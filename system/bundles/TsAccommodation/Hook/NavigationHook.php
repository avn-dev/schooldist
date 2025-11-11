<?php

namespace TsAccommodation\Hook;

use TsAccommodation\Generator\Statistic\CityTaxReport;

class NavigationHook extends \Core\Service\Hook\AbstractHook {

	public function run(array &$input) {

		if($input['name'] === 'ac_management') {

			if(\TcExternalApps\Service\AppService::hasApp(\TsAccommodation\Handler\ExternalApp\CityTax::APP_NAME)) {
				// Keine Ahnung, was die Navigation mit den Backslashes macht
				$class = '/'.str_replace('\\', '/', CityTaxReport::class);
				$input['childs'][] = [
					(new CityTaxReport())->getTitle(),
					'/wdmvc/ts-statistic/statistic/page?statistic='.$class,
					0,
					'',
					null,
					'ts.management.reporting.static.citytax'
				];
			}

		}

	}

}
