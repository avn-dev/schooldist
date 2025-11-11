<?php

class Ext_Thebing_Accommodation_Cost_CategoryRepository extends \WDBasic_Repository {

	/**
	 * @param \Ext_Thebing_Accommodation $oAccommodationProvider
	 * @return \Ext_Thebing_Accommodation_Cost_Category
	 */
	public function findByProvider(\Ext_Thebing_Accommodation $oAccommodationProvider, \DateTime $oDate=null) {

		$sTable = $this->_oEntity->getTableName();
		$sTableAlias = $this->_oEntity->getTableAlias();

		$aSql = array(
			'table' => $sTable,
			'table_alias' => $sTableAlias,
			'accommodation_id' => (int)$oAccommodationProvider->id
		);

		$sCompareDate = 'DATE(NOW())';
		if($oDate !== null) {
			$sCompareDate = ':date';
			$aSql['date'] = $oDate->format('Y-m-d');
		}
		
		$sSql = "
			SELECT
				#table_alias.*
			FROM
				`kolumbus_accommodations_salaries` `kas` JOIN
				#table #table_alias ON
					`kas`.`costcategory_id` = #table_alias.`id`
			WHERE
				`kas`.`accommodation_id` = :accommodation_id AND
				`kas`.`active` = 1 AND
				(
					".$sCompareDate." BETWEEN `kas`.`valid_from` AND `kas`.`valid_until` OR
					(
						".$sCompareDate." >= `kas`.`valid_from` AND
						`kas`.`valid_until` = '0000-00-00'
					)
				)
			LIMIT 1
		";

		$aResult = \DB::getQueryRow($sSql, $aSql);
		
		$oEntity = null;
		if(is_array($aResult)) {
			$oEntity = $this->_getEntity($aResult);
		}

		return $oEntity;		
	}

	/**
	 * Erste Kostenkategorie suchen, welcher der übergebenen Unterkunftskategorie zugewiesen ist
	 *
	 * @param Ext_Thebing_Accommodation_Category $oCategory
	 * @return Ext_Thebing_Accommodation_Cost_Category
	 */
	public function findFirstByAccommodationCategory(Ext_Thebing_Accommodation_Category $oCategory) {

		$sSql = "
			SELECT
				`kacc`.*
			FROM
				`kolumbus_accommodations_costs_categories` `kacc` INNER JOIN
				`kolumbus_accommodations_costs_categories_categories` `kaccc` ON
					`kaccc`.`category_id` = `kacc`.`id` AND
					`kaccc`.`accommodation_category_id` = :accommodation_category_id
			WHERE
				`kacc`.`active` = 1
			ORDER BY
				`id`
			LIMIT
				1
		";

		$aResult = DB::getQueryRow($sSql, ['accommodation_category_id' => $oCategory->id]);

		return $this->_getEntity($aResult);

	}
	
}