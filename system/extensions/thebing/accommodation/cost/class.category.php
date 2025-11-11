<?php

/**
 * @property int $id
 * @property int $changed Timestamp
 * @property int $created Timestamp
 * @property string $valid_until (DATE)
 * @property string $name
 * @property int $active
 * @property int $creator_id Ersteller
 * @property int $user_id Bearbeiter
 * @property string $cost_type
 * @property int $rounding_precision
 * @property int $rounding_increment
 * @method static Ext_Thebing_Accommodation_Cost_CategoryRepository getRepository()
 */
class Ext_Thebing_Accommodation_Cost_Category extends Ext_Thebing_Basic {

	/**
	 * @var string
	 */
	protected $_sTable = 'kolumbus_accommodations_costs_categories';

	/**
	 * @var string
	 */
	protected $_sTableAlias = 'kacc';

	/**
	 * @var array
	 */
	protected $_aFormat = [
		'rounding_precision' => [
			'required'=>true
		],
		'rounding_increment' => [
			'required'=>true,
			'validate'=> 'INT_POSITIVE'
		]
	];

	/**
	 * @var mixed[]
	 */
	protected $_aJoinTables = [
		'cost_weeks' => [
			'table' => 'kolumbus_accommodations_costs_categories_weeks',
			'foreign_key_field' => 'week_id',
			'primary_key_field' => 'category_id',
		],
		'accommodation_categories' => [
			'table' => 'kolumbus_accommodations_costs_categories_categories',
			'foreign_key_field' => 'accommodation_category_id',
			'primary_key_field' => 'category_id',
		],
		'accommodation_selaries' => [
			'table' => 'kolumbus_accommodations_salaries',
			'primary_key_field' => 'costcategory_id',
			//'on_delete' => 'delete',
			'check_active' => true,
			'delete_check' => true
		],
		'schools' => [
			'table' => 'ts_accommodation_costs_categories_schools',
			'foreign_key_field' => 'school_id',
			'primary_key_field' => 'accommodation_cost_category_id',
		],
	];

	/**
	 * Gibt die Einträge mit den angegebenen IDs zurück.
	 *
	 * Die Keys sind die IDs der Einträge, wenn $bSort true ist, ist das Array ist nach der
	 * Listensortierung ("name"-Feld) sortiert.
	 *
	 * @param int[] $aIds
	 * @param bool $bSort
	 * @return Ext_Thebing_Accommodation_Cost_Category[]
	 */
	public static function getListByIds(array $aIds, $bSort = true) {

		$aReturn = [];

		foreach($aIds as $iCostCategoryId) {
			$oCostCategory = Ext_Thebing_Accommodation_Cost_Category::getInstance($iCostCategoryId);
			$aReturn[$oCostCategory->id] = $oCostCategory;
		}

		if($bSort) {
			uasort(
				$aReturn,
				function(Ext_Thebing_Accommodation_Cost_Category $oCostCategory1, Ext_Thebing_Accommodation_Cost_Category $oCostCategory2) {
					return strcmp($oCostCategory1->name, $oCostCategory2->name);
				}
			);
		}

		return $aReturn;

	}

	/**
	 * Gibt eine Liste mit Einträgen zurück, die der angegebenen Schule zugewiesen sind.
	 *
	 * Die Keys sind die IDs der Einträge, das Array ist nach der Listensortierung ("name"-Feld) sortiert.
	 *
	 * @param Ext_Thebing_School $oSchool
	 * @return Ext_Thebing_Accommodation_Cost_Category[]
	 */
	public static function getListBySchool(Ext_Thebing_School $oSchool) {

		$sSql = "
			SELECT
				`kacc`.`id`
			FROM
				`kolumbus_accommodations_costs_categories` `kacc`
			INNER JOIN
				`ts_accommodation_costs_categories_schools` `ts_accs`
			ON
				`ts_accs`.`accommodation_cost_category_id` = `kacc`.`id` AND
				`ts_accs`.`school_id` = :school_id
			WHERE
				`kacc`.`active` = 1
			GROUP BY
				`kacc`.`id`
			ORDER BY
				`kacc`.`name` ASC,
				`kacc`.`id` ASC
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
	 * Gibt die Kostenkategorien für die angegebene oder aktuell ausgewählte Schule als Select-Optionen zurück.
	 *
	 * Das Array ist nach der Listensortierung ("name"-Feld) sortiert.
	 *
	 * @param bool $bEmptyItem
	 * @param Ext_Thebing_School $oSchool
	 * @return mixed[]
	 */
	public static function getSelectOptions($bEmptyItem = true, Ext_Thebing_School $oSchool = null) {

		if(!($oSchool instanceof Ext_Thebing_School)) {
			$oSchool = Ext_Thebing_School::getSchoolFromSession();
		}

		// in All-Schools muss eine Schule angegeben werden, hier wird nicht einfach geraten ...
		if(!$oSchool->exist()) {
			$oCategory = self::getInstance();
			$aCostCategories = $oCategory->getArrayList(true);
		} else {

			$aCostCategories = Ext_Thebing_Accommodation_Cost_Category::getListBySchool($oSchool);
			$aCostCategories = array_map(
				function(Ext_Thebing_Accommodation_Cost_Category $oCostCategory) {
					return $oCostCategory->name;
				},
				$aCostCategories
			);

		}

		if($bEmptyItem) {
			$aCostCategories = Ext_Thebing_Util::addEmptyItem($aCostCategories);
		}

		return $aCostCategories;

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

			if(in_array($sJoinAlias, ['schools'])) {
				$aQueryData['sql'] .= "
					LEFT OUTER JOIN
						#join_table_".$iJoinCount." #join_alias_".$iJoinCount." ON
						#join_alias_".$iJoinCount.".#join_pk_".$iJoinCount." = ".$sAliasString."`id`
				";
				$aQueryData['data']['join_table_'.$iJoinCount] = $aJoinData['table'];
				$aQueryData['data']['join_pk_'.$iJoinCount] = $aJoinData['primary_key_field'];
				$aQueryData['data']['join_alias_'.$iJoinCount] = 'filter_'.$sJoinAlias;
				$iJoinCount++;
			}

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

		if(array_key_exists('name', $this->_aData)) {
			$aQueryData['sql'] .= "
				ORDER BY
					".$sAliasString."`name` ASC,
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
	 * Sortierung nicht nach Position, weil das hier nur für Preisberechnung verwendet wird.
	 * 
	 * @return mixed[]
	 */
	public function getCostWeeks() {

		$sSql = "
			SELECT
				`kacw`.*
			FROM
				`kolumbus_accommodations_costs_categories_weeks` `kaccw` JOIN
				`kolumbus_accommodations_costs_weeks` `kacw` ON
					`kaccw`.`week_id` = `kacw`.`id`
			WHERE
				`kaccw`.`category_id` = :category_id
			ORDER BY
				`kacw`.`start_week` ASC
		";
		$aSql = [
			'category_id' => (int)$this->id
		];
		$aWeeks = $this->_oDb->queryRows($sSql, $aSql);

		return $aWeeks;
	}

	/**
	 * {@inheritdoc}
	 */
	public function validate($bThrowExceptions = false) {

		$mReturn = parent::validate($bThrowExceptions);

		if($mReturn === true) {

			$aIntersectionData = $this->getIntersectionData();
			if(array_key_exists('cost_type', $aIntersectionData)) {
				$aPaymentsForCostCategory = $this->getPayments();
				if(!empty($aPaymentsForCostCategory)) {
					$mReturn = [
						'cost_type' => 'PAYMENTS_EXIST',
					];
				}
			}

			if(
				$this->valid_until !== '0000-00-00' &&
				!$this->checkValidUntil()
			) {
				if(!is_array($mReturn)) {
					$mReturn = [];
				}
				$mReturn['valid_until'] = 'ALLOCATIONS_EXIST';
			}

		}

		return $mReturn;

	}

	/**
	 * @return mixed[]
	 */
	public function getPayments() {

		$oDate = new WDDate();
		$oDate->add(10, WDDate::YEAR);
		$dMax = $oDate->get(WDDate::DB_DATE);

		$sSql = "
			SELECT
				*
			FROM
				`kolumbus_accommodations_salaries` `kas`
			INNER JOIN
				`kolumbus_accommodations_payments` `kap`
			ON
				`kap`.`accommodation_id` = `kas`.`accommodation_id` AND
				`kap`.`timepoint` >= `kas`.`valid_from` AND 
				`kap`.`timepoint` <= IF(`kas`.`valid_until` = '0000-00-00',:max_date,`kas`.`valid_until`) AND
				`kap`.`active` = 1
			INNER JOIN
				`customer_db_4` `cdb4`
			ON
				`cdb4`.`id` = `kap`.`accommodation_id` AND
				`cdb4`.`active` = 1
			WHERE
				`kas`.`active` = 1 AND
				`kas`.`costcategory_id` = :costcategory_id
		";
		$aSql = [
			'costcategory_id' => (int)$this->id,
			'max_date' => $dMax,
		];
		$aResult = DB::getPreparedQueryData($sSql, $aSql);

		return $aResult;

	}

	/**
	 * Prüfen, ob es für valid_until noch gültige Zuweisungen bei Unterkunftsanbietern gibt
	 *
	 * @return bool
	 */
	protected function checkValidUntil() {

		$sSql = "
			SELECT
				COUNT(`id`)
			FROM
				`kolumbus_accommodations_salaries`
			WHERE
				`costcategory_id` = :id AND
				`active` = 1 AND (
					`valid_until` = '0000-00-00' OR
					`valid_until` > :valid_until
				)
		";

		$iCount = (int)DB::getQueryOne($sSql, $this->getData());

		return $iCount < 1;

	}

	/**
	 * Rundet nach den Einstellungen der Kostenkategorie.
	 *
	 * @param float $fAmount
	 * @return float
	 */
	public function round($fAmount) {
		return round($fAmount / $this->rounding_increment, $this->rounding_precision) * $this->rounding_increment;
	}

	/**
	 * Nicht unterstützt, kann mehreren Schulen zugewiesen sein.
	 *
	 * @deprecated
	 * @throws LogicException
	 */
	public function getSchool() {

		$sMsg = 'An accommodation cost category can be assigned to multiple schools.';
		throw new LogicException($sMsg);

	}

	/**
	 * Nicht unterstützt, kann mehreren Schulen zugewiesen sein.
	 *
	 * @deprecated
	 * @throws LogicException
	 */
	public function getSchoolId() {

		$sMsg = 'An accommodation cost category can be assigned to multiple schools.';
		throw new LogicException($sMsg);

	}

}
