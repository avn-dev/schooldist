<?php

namespace TsStatistic\Model\Filter;

use \TcStatistic\Model\Filter\AbstractFilter;

class Currency extends AbstractFilter {

	public function getKey() {
		return 'currency';
	}

	public function getTitle() {
		return self::t('Währung');
	}

	public function getInputType() {
		return 'select';
	}

	public function getSelectOptions() {
		$oClient = \Ext_Thebing_Client::getFirstClient();
		return $oClient->getSchoolsCurrencies();
	}

	public function getDefaultValue() {
		$oSchool = \Ext_Thebing_Client::getFirstSchool();
		return $oSchool->getCurrency();
	}

	public function isShown() {

		// Wenn es nur eine Währung gibt, dann wird dieser Filter nicht benötigt
		if(count($this->getSelectOptions()) === 1) {
			return false;
		}

		return true;

	}

}
