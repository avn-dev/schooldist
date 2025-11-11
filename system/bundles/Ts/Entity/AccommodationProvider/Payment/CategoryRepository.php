<?php

namespace Ts\Entity\AccommodationProvider\Payment;

class CategoryRepository extends \WDBasic_Repository {
	
	/**
	 * @param \Ext_Thebing_Accommodation $oAccommodationProvider
	 * @return \Ts\Entity\AccommodationProvider\Payment\Category
	 */
	public function findByProvider(\Ext_Thebing_Accommodation $oAccommodationProvider, \DateTime $oDate=null) {

		$sTable = $this->_oEntity->getTableName();
		$sTableAlias = $this->_oEntity->getTableAlias();

		$aSql = array(
			'table' => $sTable,
			'table_alias' => $sTableAlias,
			'provider_id' => (int)$oAccommodationProvider->id
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
				`ts_accommodation_providers_payment_categories_validity` `ts_appcv` JOIN
				#table #table_alias ON
					`ts_appcv`.`category_id` = #table_alias.`id`
			WHERE
				`ts_appcv`.`provider_id` = :provider_id AND
				`ts_appcv`.`active` = 1 AND
				(
					".$sCompareDate." BETWEEN `ts_appcv`.`valid_from` AND `ts_appcv`.`valid_until` OR
					(
						".$sCompareDate." >= `ts_appcv`.`valid_from` AND
						`ts_appcv`.`valid_until` = '0000-00-00'
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
	
}