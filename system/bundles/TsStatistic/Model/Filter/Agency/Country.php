<?php

namespace TsStatistic\Model\Filter\Agency;

use \TcStatistic\Model\Filter\AbstractFilter;

/**
 * @TODO Wenn benötigt, als generellen Filter Country bereitstellen (dieser hier ist aber kein Multiselect)
 */
class Country extends AbstractFilter {

	public function getKey() {
		return 'agency_country';
	}

	public function getTitle() {
		return self::t('Agenturland');
	}

	public function getInputType() {
		return 'select';
	}

	public function getSelectOptions() {
		$aCountries = \Ext_Thebing_Data::getCountryList();
		$aCountries = \Ext_Thebing_Util::addEmptyItem($aCountries);
		return $aCountries;
	}

	public function getDefaultValue() {
		return '0';
	}

}
