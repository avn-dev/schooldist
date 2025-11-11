<?php

/**
 * @property int $id
 * @property int $changed Timestamp
 * @property int $created Timestamp
 * @property int $user_id Bearbeiter
 * @property int $units 	
 * @property string $title
 * @property int $extra
 * @property int $active
 * @property int $creator_id Ersteller
 * @property int $start_unit
 * @property int $unit_count
 * @property int $position
 */
class Ext_Thebing_School_TeachingUnit extends Ext_Thebing_Basic {

	/**
	 * @var string
	 */
	protected $_sTable = 'kolumbus_courseunits';

	/**
	 * @var string
	 */
	protected $_sTableAlias = 'kcou';

	/**
	 * @var mixed[]
	 */
	protected $_aJoinTables = [
		'schools' => [
			'table' => 'ts_courseunits_schools',
			'foreign_key_field' => 'school_id',
			'primary_key_field' => 'courseunit_id',
		],
	];

	/**
	 * Gibt die Einträge mit den angegebenen IDs zurück.
	 *
	 * Die Keys sind die IDs der Einträge, wenn $bSort true ist, ist das Array ist nach der
	 * Listensortierung ("position"-Feld) sortiert.
	 *
	 * @param int[] $aIds
	 * @param bool $bSort
	 * @return Ext_Thebing_Accommodation_Category[]
	 */
	public static function getListByIds(array $aIds, $bSort = true) {

		$aReturn = [];

		foreach($aIds as $iTeachingUnitId) {
			$oTeachingUnit = Ext_Thebing_School_TeachingUnit::getInstance($iTeachingUnitId);
			$aReturn[$oTeachingUnit->id] = $oTeachingUnit;
		}

		if($bSort) {
			uasort(
				$aReturn,
				function(Ext_Thebing_School_TeachingUnit $oTeachingUnit1, Ext_Thebing_School_TeachingUnit $oTeachingUnit2) {
					return strcmp($oTeachingUnit1->position, $oTeachingUnit2->position);
				}
			);
		}

		return $aReturn;

	}

	/**
	 * Gibt eine Liste mit Einträgen zurück, die der angegebenen Schule zugewiesen sind.
	 *
	 * Die Keys sind die IDs der Einträge, das Array ist nach der Listensortierung ("position"-Feld) sortiert.
	 *
	 * @param Ext_Thebing_School $oSchool
	 * @return Ext_Thebing_School_TeachingUnit[]
	 */
	public static function getListBySchool(Ext_Thebing_School $oSchool) {

		$sSql = "
			SELECT
				`kac`.`id`
			FROM
				`kolumbus_courseunits` `kac`
			INNER JOIN
				`ts_courseunits_schools` `ts_cs`
			ON
				`ts_cs`.`courseunit_id` = `kac`.`id` AND
				`ts_cs`.`school_id` = :school_id
			WHERE
				`kac`.`active` = 1
			GROUP BY
				`kac`.`id`
			ORDER BY
				`kac`.`position` ASC,
				`kac`.`id` ASC
		";
		$aSql = [
			'school_id' => (int)$oSchool->id,
		];
		$aResult = (array)DB::getPreparedQueryData($sSql, $aSql);

		$aIds = array_map(
			function(array $aRow) {
				return $aRow['id'];
			},
			$aResult
		);

		return self::getListByIds($aIds, false);

	}

	/**
	 * {@inheritdoc}
	 */
	public function getListQueryData($oGui = null) {

		$sFormat = $this->_formatSelect();

		$aQueryData = [
			'data' => [],
			'sql' => '',
		];

		$sAliasString = '';
		$sTableAlias = '';
		if(!empty($this->_sTableAlias)) {
			$sTableAlias = '`'.$this->_sTableAlias.'`';
			$sAliasString = $sTableAlias.'.';
		}

		$aQueryData['sql'] .= "
			SELECT
				".$sAliasString."* {FORMAT},
				GROUP_CONCAT(DISTINCT `schools`.`school_id`) AS `schools`
			FROM
				`{TABLE}` ".$sTableAlias."
		";

		$iJoinCount = 1;
		foreach($this->_aJoinTables as $sJoinAlias => $aJoinData) {

			$aQueryData['sql'] .= "
				LEFT OUTER JOIN
					#join_table_".$iJoinCount." #join_alias_".$iJoinCount." ON
					#join_alias_".$iJoinCount.".#join_pk_".$iJoinCount." = ".$sAliasString."`id`
			";
			$aQueryData['data']['join_table_'.$iJoinCount] = $aJoinData['table'];
			$aQueryData['data']['join_pk_'.$iJoinCount] = $aJoinData['primary_key_field'];
			$aQueryData['data']['join_alias_'.$iJoinCount] = $sJoinAlias;
			$iJoinCount++;

		}

		if(array_key_exists('active', $this->_aData)) {
			$aQueryData['sql'] .= "
				WHERE
					".$sAliasString."`active` = 1
			";
		}

		if(count($this->_aJoinTables) > 0){
			$aQueryData['sql'] .= "
				GROUP BY
					".$sAliasString."`id`
			";
		}

		if(array_key_exists('position', $this->_aData)) {
			$aQueryData['sql'] .= "
				ORDER BY
					".$sAliasString."`position` ASC,
					".$sAliasString."`id` ASC
			";
		} else {
			$aQueryData['sql'] .= "
				ORDER BY
					".$sAliasString."`id` ASC
			";
		}

		$aQueryData['sql'] = str_replace('{FORMAT}', $sFormat, $aQueryData['sql']);
		$aQueryData['sql'] = str_replace('{TABLE}', $this->_sTable, $aQueryData['sql']);

		return $aQueryData;

	}

	/**
	 * Nicht unterstützt, kann mehreren Schulen zugewiesen sein.
	 *
	 * @deprecated
	 * @throws LogicException
	 */
	public function getSchool() {

		$sMsg = 'A course unit can be assigned to multiple schools.';
		throw new LogicException($sMsg);

	}

	/**
	 * Nicht unterstützt, kann mehreren Schulen zugewiesen sein.
	 *
	 * @deprecated
	 * @throws LogicException
	 */
	public function getSchoolId() {

		$sMsg = 'A course unit can be assigned to multiple schools.';
		throw new LogicException($sMsg);

	}

}
