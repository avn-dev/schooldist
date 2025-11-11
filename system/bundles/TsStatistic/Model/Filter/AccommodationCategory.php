<?php

namespace TsStatistic\Model\Filter;

use TcStatistic\Model\Filter\AbstractFilter;
use TsStatistic\Model\Filter\Tool\FilterInterface;

class AccommodationCategory extends AbstractFilter implements FilterInterface {

	public function getKey() {
		return 'accommodation_categories';
	}

	public function getTitle() {
		return self::t('Unterkunftskategorie');
	}

	public function getInputType() {
		return 'multiselect';
	}

	public function getSelectOptions() {
		$schoolIds = array_keys(\Ext_Thebing_Client::getFirstClient()->getSchoolListByAccess(true));
		return \Ext_Thebing_Accommodation_Category::getListForSchools($schoolIds);
	}

	public function getDefaultValue() {
		return array_keys($this->getSelectOptions());
	}

	public function getJoinParts(): array {
		return ['accommodation'];
	}

	public function getJoinPartsAdditions(): array {
		return ['JOIN_ACCOMMODATIONS' => " AND `kac`.`id` IN (:accommodation_categories) "];
	}

	public function getSqlWherePart(): string {
		return "";
	}
}
