<?php

class Ext_Thebing_Marketing_Costcategories {

	protected static $_aCache = array();

	public static function getTeacherCategories($bForSelect = true, \Ext_Thebing_School $oSchool=null) {

		$iSessionSchoolId = null;
		if($oSchool !== null) {
			$iSessionSchoolId = $oSchool->id;
		}
		
		if(!isset(self::$_aCache['getTeacherCategories'][(int)$iSessionSchoolId])) {

			$aSql = [];
			$sWhere = "";

			if($iSessionSchoolId !== null) {
				$aSql['school_id'] = (int)$iSessionSchoolId;
				$sWhere .= " AND
					school_id = :school_id ";
			}

			$sSql = "
				SELECT
					*
				FROM
					`kolumbus_costs_kategorie_teacher`
				WHERE
					active = 1
					".$sWhere."
				ORDER BY
					`name` 
			";

			$aResult = DB::getPreparedQueryData($sSql, $aSql);

			self::$_aCache['getTeacherCategories'][(int)$iSessionSchoolId] = $aResult;

		}

		$aResult = self::$_aCache['getTeacherCategories'][(int)$iSessionSchoolId];

		if($bForSelect == false){
			return $aResult;
		}
		$aBack = array();
		foreach((array)$aResult as $aData){
			$aBack[$aData['id']] = $aData['name'];
		}

		return $aBack;

	}

	public static function getAccommodationCategories($bForSelect = true, array $aAccommodationCategoryIds = [], array $aSchoolIds = []) {

		$aSql = [
			'school_ids' => $aSchoolIds,
		];

		if(empty($aSql['school_ids'])) {
			$oSchool = Ext_Thebing_School::getSchoolFromSession();
			$aSql['school_ids'][] = $oSchool->id;
		}

		$sSql = "
			SELECT
				`kacc`.*
			FROM
				`kolumbus_accommodations_costs_categories` `kacc` INNER JOIN
				`ts_accommodation_costs_categories_schools` `ts_accs` ON
					`ts_accs`.`accommodation_cost_category_id` = `kacc`.`id` AND
					`ts_accs`.`school_id` IN(:school_ids) LEFT JOIN
				`kolumbus_accommodations_costs_categories_categories` `kaccc` ON
					`kacc`.`id` = `kaccc`.`category_id`
			WHERE
				`kacc`.`active` = 1 AND (
					`kacc`.`valid_until` = '0000-00-00' OR
					`kacc`.`valid_until` >= CURDATE()
				)
		";

		if(!empty($aAccommodationCategoryIds)) {
			$sSql .= " AND (
				`kaccc`.`accommodation_category_id` IN(:accommodation_category_ids) OR
				`kaccc`.`accommodation_category_id` IS NULL
			)";
			$aSql['accommodation_category_ids'] = $aAccommodationCategoryIds;
		}

		$sSql .= "
			GROUP BY
				`kacc`.`id`
		";

		$aResult = DB::getPreparedQueryData($sSql,$aSql);
		if($bForSelect == false){
			return $aResult;
		}

		$aBack = array();
		foreach((array)$aResult as $aData){
			$aBack[$aData['id']] = $aData['name'];
		}		

		return $aBack;

	}

}
