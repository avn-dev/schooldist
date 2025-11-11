<?php

namespace TsStatistic\Model\Filter;

use \TcStatistic\Model\Filter\AbstractFilter;

class Schools extends AbstractFilter {

	public function getKey() {
		return 'schools';
	}

	public function getTitle() {
		return self::t('Schulen');
	}

	public function getInputType() {
		return 'multiselect';
	}

	public function getSelectOptions() {
		return \Ext_Thebing_Client::getFirstClient()->getSchoolListByAccess(true);
	}

	public function getDefaultValue() {

		$aOptions = $this->getSelectOptions();

		if(\Ext_Thebing_System::isAllSchools()) {
			return array_keys($aOptions);
		}

		$oSchool = \Ext_Thebing_School::getSchoolFromSession();
		return [$oSchool->id];

	}

	public function isShown() {

		// Wenn es nur eine Schule gibt, dann wird dieser Filter nicht benötigt
		if(count($this->getSelectOptions()) === 1) {
			return false;
		}

		return true;

	}

}
