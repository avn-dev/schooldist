<?php

/**
 * @property int $id
 * @property int $changed Timestamp
 * @property int $created Timestamp
 * @property int $week
 * @property string $title
 * @property int $extra
 * @property int $active
 * @property int $creator_id Ersteller
 * @property int $start_week
 * @property int $week_count
 * @property int $position
 * @property int $user_id Bearbeiter
 */
class Ext_Thebing_School_Week extends Ext_Thebing_Basic {

	/**
	 * @var string
	 */
	protected $_sTable = 'kolumbus_weeks';

	/**
	 * @var string
	 */
	protected $_sTableAlias = 'kw';

	/**
	 * @var mixed[]
	 */
	protected $_aJoinTables = [
		'schools' => [
			'table' => 'ts_weeks_schools',
			'foreign_key_field' => 'school_id',
			'primary_key_field' => 'week_id',
		],
	];

	/**
	 * Gibt eine Liste mit Einträgen zurück.
	 *
	 * Das Array ist nach der Listensortierung ("position"-Feld) sortiert.
	 *
	 * @param bool $bForSelect
	 * @return mixed[]
	 */
	public static function getList($bForSelect = true) {

		$oWeeks = new self(0);
		$aQueryData = $oWeeks->getListQueryData();

		$aResult = DB::getPreparedQueryData($aQueryData['sql'], $aQueryData['data']);

		if(!$bForSelect) {
			return $aResult;
		}

		$aBack = [];
		foreach ($aResult as $aData) {
			$aBack[$aData['id']] = $aData['title'];
		}

		return $aBack;
	}

	/**
	 * Gibt eine Liste mit Wochen zurück die für alle angegebenen Schulen gültig sind.
	 *
	 * Das Array ist nach der Listensortierung ("position"-Feld) sortiert.
	 *
	 * Wenn keine Schulen angegeben sind ($aSchoolIds leer), wird eine leere Liste zurück gegeben.
	 *
	 * @param int[] $aSchoolIds
	 * @param bool $bForSelect
	 * @return mixed[]
	 */
	public static function getListForSchools(array $aSchoolIds, $bForSelect = true) {

		$aWeeks = self::getList(false);
		$aResult = [];

		if(empty($aSchoolIds)) {
			return $aResult;
		}

		foreach($aWeeks as $aWeek) {

			$aWeekAvailableSchoolIds = explode(',', $aWeek['schools']);

			foreach($aSchoolIds as $iSchoolId) {
				if(!in_array($iSchoolId, $aWeekAvailableSchoolIds)) {
					continue 2;
				}
			}

			$aResult[] = $aWeek;

		}

		if(!$bForSelect) {
			return $aResult;
		}

		$aBack = [];
		foreach ($aResult as $aData) {
			$aBack[$aData['id']] = $aData['title'];
		}
		
		return $aBack;
	}

	/**
	 * {@inheritdoc}
	 */
	public function getListQueryData($oGui=null) {

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

		$aQueryData['sql'] = "
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
	 * @throws \LogicException
	 */
	public function getSchool() {

		$sMsg = 'A week can be assigned to multiple schools.';
		throw new \LogicException($sMsg);

	}

	/**
	 * Nicht unterstützt, kann mehreren Schulen zugewiesen sein.
	 *
	 * @deprecated
	 * @throws \LogicException
	 */
	public function getSchoolId() {

		$sMsg = 'A week can be assigned to multiple schools.';
		throw new \LogicException($sMsg);

	}

}
