<?php

namespace Ts\Traits;

trait PriceCalculationColumns {

	protected ?string $nationality;

	protected ?\Ext_Thebing_Agency_Category $agencyCategory;

	protected ?array $countryGroupIds;
	
	protected function addNationalityWherePart($nationality): string {
		
		if($nationality === null) {
			return " `nationality` IS NULL ";
		}
		
		return " `nationality` = :nationality ";
	}

	protected function addAgencyCategoryWherePart($agencyCategory): string {

		if($agencyCategory === null) {
			return " `agency_category_id` IS NULL ";
		}

		return " `agency_category_id` = :agency_category_id ";
	}

	protected function addCountryGroupWherePart(?array $countryGroupIds = null): string {

		$sql = "";

		if (is_array($countryGroupIds) && count($countryGroupIds) > 0) {
			$sql .= " `country_group_id` IN (".implode(',', $countryGroupIds).") ";
		} else {
			$sql .= " `country_group_id` IS NULL ";
		}

		return $sql;
	}

	protected function addNationalityAndCountryGroupWherePart(?string $nationality = null, ?array $countryGroupIds = null, bool $fallbackNationalityToCountryGroups = false): string {

		$sql = "";

		if ($nationality === null) {
			$sql .= " (`nationality` IS NULL";
		} else {
			$sql .= " (`nationality` = :nationality";
		}

		if ($fallbackNationalityToCountryGroups && $countryGroupIds) {
			$sql .= " OR `country_group_id` IN (".implode(',', $countryGroupIds).")) ";
		} else if ($countryGroupIds) {
			$sql .= " AND `country_group_id` IN (".implode(',', $countryGroupIds).")) ";
		} else {
			$sql .= " AND `country_group_id` IS NULL ) ";
		}

		return $sql;
	}

	/**
	 * If it is with fallback, makes sure max one of the two ($agencyCategory, $nationality/$countryGroupIds) is not null, in that order. Then sets countryGroupIds. Used for Price lookups for users.
	 * If it is without fallback, makes sure max one of the three ($agencyCategory, $nationality, $countryGroupIds) is not null, in that order. Used for exact Price entries in Administration.
	 * @param $nationality
	 * @param $agencyCategory
	 * @param $countryGroupIds
	 * @param bool $fallbackNationalityToCountryGroups
	 * @return void
	 */
	protected function manipulateNationalityAndAgencyCategory(&$nationality, &$agencyCategory, &$countryGroupIds, bool $fallbackNationalityToCountryGroups = false): void {
		// Man kann nur entweder oder benutzen
		if ($agencyCategory) {
			$nationality = null;
			$countryGroupIds = null;
		}
		if ($nationality) {
			$agencyCategory = null;
			if ($fallbackNationalityToCountryGroups) {
				$countryGroupIdList = \Ext_TC_Countrygroup::getCountryGroupIdsByCountryIso($nationality);
				$countryGroupIds = count($countryGroupIdList) > 0 ? $countryGroupIdList : null;
			} else {
				$countryGroupIds = null;
			}
		}
	}

}
