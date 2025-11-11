<?php

class Ext_Thebing_Marketing_StudentStatus extends Ext_Thebing_Basic {

	protected $_sTable = 'kolumbus_student_status';

	protected $_sTableAlias = 'kss';

	protected $_aJoinTables = [
		'schools' => [
			'table' => 'kolumbus_student_status_schools',
			'foreign_key_field' => 'school_id',
			'primary_key_field' => 'status_id',
		]
	];

	public function manipulateSqlParts(&$aSqlParts, $sView = null) {

		parent::manipulateSqlParts($aSqlParts, $sView);

		$aSqlParts['select'] .= ",
			GROUP_CONCAT(DISTINCT `schools`.`school_id`) AS `schools`
		";

	}

	/**
	 * @param bool $bForSelect
	 * @param int $iSchoolId
	 * @return static[]|string[]
	 */
	public static function getList($bForSelect = false, $iSchoolId = null) {

		$aSql = [];

		$sJoin = "";
		if($iSchoolId !== null) {
			$sJoin .= " INNER JOIN
				`kolumbus_student_status_schools` `ksss` ON
					`ksss`.`status_id` = `kss`.`id` AND
					`ksss`.`school_id` = :school_id
			";

			$aSql['school_id'] = $iSchoolId;
		}

		$sSql = "
			SELECT
				`kss`.*
			FROM
				`kolumbus_student_status` `kss`
				{$sJoin}
			WHERE
				`kss`.`active` = 1 AND (
					`kss`.`valid_until` = '0000-00-00' OR
					`kss`.`valid_until` >= NOW()
				)
			GROUP BY
				`kss`.`id`
			ORDER BY
				`kss`.`position`
		";

		$aResult = (array)DB::getQueryRows($sSql, $aSql);

		$aReturn = [];
		foreach($aResult as $aRow) {
			if(!$bForSelect) {
				$aReturn[] = static::getObjectFromArray($aRow);
			} else {
				$aReturn[$aRow['id']] = $aRow['text'];
			}

		}

		return $aReturn;

	}

}
