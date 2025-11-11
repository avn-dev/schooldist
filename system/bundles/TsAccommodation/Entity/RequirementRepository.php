<?php
namespace TsAccommodation\Entity;

/**
 * Class RequirementRepository
 */
class RequirementRepository extends \WDBasic_Repository {

	/**
	 * Voraussetzungen des übergebenen Unterkunftsanbieters holen
	 *
	 * @param \Ext_Thebing_Accommodation $oAccommodation
	 * @return array
	 */
	public function findByAccommodation(\Ext_Thebing_Accommodation $oAccommodation) {

		$sSql = "
			SELECT
				`ts_apr`.*
			FROM
				`ts_accommodation_categories_to_accommodation_providers` `ts_actap` JOIN
				`ts_accommodation_categories_to_requirements` `ts_actr` ON 
					`ts_actap`.`accommodation_category_id` = `ts_actr`.`accommodation_category_id` JOIN
				`ts_accommodation_providers_requirements` `ts_apr` ON
					`ts_apr`.`id` = `ts_actr`.`requirement_id` AND
					`ts_apr`.`active` = 1
			WHERE
				`ts_actap`.`accommodation_provider_id` = :accommodation_provider_id
		";

		$aSql = [
			'accommodation_provider_id' => $oAccommodation->id
		];

		$aResults = \DB::getQueryRows($sSql, $aSql);

		$aEntities = array();

		if(is_array($aResults)) {

			$aEntities = $this->_getEntities($aResults);

		}

		return $aEntities;
	}

}