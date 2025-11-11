<?php

namespace TsAccommodation\Entity;

/**
 * @TODO Es darf keine zwei Klassen für eine Entität geben!
 * Das lässt sich nur bisher nicht besser lösen.
 *
 * @internal
 */
class AccommodationRequirement extends \Ext_Thebing_Accommodation  {

	/**
	 * @param array $aSqlParts
	 * @param string $sView
	 * @return void
	 */
	public function manipulateSqlParts(&$aSqlParts, $sView=null) {

		#parent::manipulateSqlParts($aSqlParts, $sView);
		
		$aSqlParts['select'] = "
			`ts_apr`.`id`,
			`ts_apr`.`name`,
			`customer_db_4`.`id` `accommodation_id`
			
		";

		$aSqlParts['from'] .= " JOIN
			`ts_accommodation_categories_to_requirements` `ts_actr` ON
				`ts_actr`.`accommodation_category_id` = `accommodation_categories`.`accommodation_category_id` LEFT JOIN
			`ts_accommodation_providers_requirements` `ts_apr` ON
				`ts_apr`.`id` = `ts_actr`.`requirement_id` AND
				`ts_apr`.`active` = 1 
		";
		
		$aSqlParts['groupby'] = "`ts_apr`.`id`";
		
	}

}
