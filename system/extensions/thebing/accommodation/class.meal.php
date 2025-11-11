<?php

/**
 * @property int $id
 * @property int $active
 * @property string $valid_until YYYY-MM-DD
 * @property int $creator_id Ersteller
 * @property int $changed Timestamp
 * @property int $user_id Bearbeiter
 * @property int $created Timestamp
 * @property int $position
 * @property int $meal_plan
 * @property string $name_LANG pro verfügbarer Sprache, siehe Ext_Thebing_Accommodation_Meal::getName()
 * @property string $short_LANG pro verfügbarer Sprache, siehe Ext_Thebing_Accommodation_Meal::getShortName()
 */
class Ext_Thebing_Accommodation_Meal extends Ext_TS_Inquiry_Journey_Accommodation_Info_Abstract {

	use \Tc\Traits\Placeholder;
	
	/**
	 * @var string
	 */
	protected $_sTable = 'kolumbus_accommodations_meals';

	/**
	 * @var string
	 */
	protected $_sTableAlias = 'kam';

	/**
	 * @var mixed[]
	 */
	protected $_aJoinTables = [
		'schools' => [
			'table' => 'ts_accommodation_meals_schools',
			'foreign_key_field' => 'school_id',
			'primary_key_field' => 'accommodation_meal_id',
		],
	];

	/** @var int Stundenplansrecht */
	const MEAL_PLAN_BREAKFAST = 1;

	/** @var int Anwesenheitsrecht */
	const MEAL_PLAN_LUNCH = 2;

	/** @var int Kommunikationsrecht */
	const MEAL_PLAN_DINNER = 4;

	/**
	 * @var mixed[]
	 */
	protected $_aFlexibleFieldsConfig = [
		'meals' => []
	];

	protected $_sPlaceholderClass = \TsAccommodation\Service\Placeholder\BoardPlaceholder::class;
	
	/**
	 * @TODO Es gibt hier drei Methoden, die nahezu dasselbe machen (auch bei Category und Roomtype)
	 * @TODO valid_until wird hier ignoriert
	 *
	 * Gibt eine Liste mit Einträgen zurück, die der angegebenen Schule zugewiesen sind.
	 *
	 * Die Keys sind die IDs der Einträge, das Array ist nach der Listensortierung ("position"-Feld) sortiert.
	 *
	 * @param Ext_Thebing_School $oSchool
	 * @return Ext_Thebing_Accommodation_Meal[]
	 */
	public static function getMealTypesBySchool(Ext_Thebing_School $oSchool) {

		$sSql = "
			SELECT
				`kam`.*
			FROM
				`kolumbus_accommodations_meals` `kam` INNER JOIN
				`ts_accommodation_meals_schools` `ts_ams` ON
					`ts_ams`.`accommodation_meal_id` = `kam`.`id` AND
					`ts_ams`.`school_id` = :school_id
			WHERE
				`kam`.`active` = 1
			GROUP BY
				`kam`.`id`
			ORDER BY
				`kam`.`position` ASC,
				`kam`.`id` ASC
		";

		$aResult = (array)DB::getQueryRowsAssoc($sSql, ['school_id' => (int)$oSchool->id]);

		array_walk($aResult, function(&$aRow, $iId) {
			$aRow['id'] = $iId;
			$aRow = static::getObjectFromArray($aRow);
		});

		return $aResult;

	}

	/**
	 * @TODO Es gibt hier drei Methoden, die nahezu dasselbe machen (auch bei Category und Roomtype)
	 *
	 * Gibt eine Liste mit Einträgen zurück die für mindestens eine ($bMatchAllSchools = false)
	 * oder alle ($bMatchAllSchools = true) der angegebenen Schulen gültig sind.
	 *
	 * Das Array ist nach der Listensortierung ("position"-Feld) sortiert.
	 *
	 * Wenn keine Schulen angegeben sind ($aSchoolIds leer), wird eine leere Liste zurück gegeben.
	 *
	 * @param int[] $aSchoolIds
	 * @param bool $bForSelect
	 * @param string $sLanguage
	 * @return mixed[]
	 */
	public static function getListForSchools(array $aSchoolIds, $bForSelect = true, $sLanguage = '', $bMatchAllSchools = false) {

		$aMeals = self::getList(false);
		$aResult = [];

		if(empty($aSchoolIds)) {
			return $aResult;
		}

		foreach($aMeals as $aMeal) {

			$aMealAvailableSchoolIds = explode(',', $aMeal['schools']);

			if($bMatchAllSchools) {
				foreach($aSchoolIds as $iSchoolId) {
					if(!in_array($iSchoolId, $aMealAvailableSchoolIds)) {
						continue 2;
					}
				}
				$aResult[] = $aMeal;
			} else {
				foreach($aSchoolIds as $iSchoolId) {
					if(in_array($iSchoolId, $aMealAvailableSchoolIds)) {
						$aResult[] = $aMeal;
						break;
					}
				}
			}

		}

		if(!$bForSelect) {
			return $aResult;
		}

		if(empty($sLanguage)) {
			$sLanguage = Ext_Thebing_Util::getInterfaceLanguage();
		}

		$aBack = [];
		foreach ($aResult as $aData) {
			$aBack[$aData['id']] = $aData['name_'.$sLanguage];
		}
		return $aBack;

	}

	protected function _formatSelect($bLoadSingle = false) {

		$sFormat = parent::_formatSelect($bLoadSingle);

		if($bLoadSingle === false) {
			$sFormat .= ",
				GROUP_CONCAT(DISTINCT `schools`.`school_id`) AS `schools`
			";
		}

		return $sFormat;
	}

	/**
	 * {@inheritdoc}
	 */
	protected function _getInfoKey() {
		return 'meal_id';
	}

	public function  __get($sName) {

		if(strpos($sName, 'meal_plan_') === 0) {

			$sKey = str_replace('meal_plan', '', $sName);

			$iBit = constant('self::MEAL_PLAN'.strtoupper($sKey));

			$sValue = $iBit & $this->meal_plan;

		} else {
			$sValue = parent::__get($sName);
		}

		return $sValue;

	}

	public function  __set($sName, $sValue) {

		if(strpos($sName, 'meal_plan_') === 0) {

			$sKey = str_replace('meal_plan', '', $sName);

			$iBit = constant('self::MEAL_PLAN'.strtoupper($sKey));

			if(empty($sValue)) {
				$this->meal_plan &= ~$iBit;
			} else {
				$this->meal_plan |= $iBit;

			}

		} else {
			parent::__set($sName, $sValue);
		}

	}

	/**
	 * {@inheritdoc}
	 *
	 * @param string $sLanguage
	 * @param bool $bShort
	 */
	public function getName($sLanguage = '', $bShort = false) {

		if(empty($sLanguage)) {
			$sLanguage = Ext_Thebing_Util::getInterfaceLanguage();
		}

		$sColumn = 'name_'.$sLanguage;

		if($bShort) {
			$sColumn = 'short_'.$sLanguage;
		}

		if(!isset($this->_aData[$sColumn])) {
			if($bShort) {
				$sColumn = 'short_en';
			} else {
				$sColumn = 'name_en';
			}
		}

		return $this->$sColumn;

	}

	/**
	 * @param string $sLanguage
	 * @return string
	 */
	public function getShortName($sLanguage = '') {
		return $this->getName($sLanguage, true);
	}

	/**
	 * Nicht unterstützt, kann mehreren Schulen zugewiesen sein.
	 *
	 * @deprecated
	 * @throws LogicException
	 */
	public function getSchool() {

		$sMsg = 'An accommodation meal can be assigned to multiple schools.';
		throw new LogicException($sMsg);

	}

	/**
	 * Nicht unterstützt, kann mehreren Schulen zugewiesen sein.
	 *
	 * @deprecated
	 * @throws LogicException
	 */
	public function getSchoolId() {

		$sMsg = 'An accommodation meal can be assigned to multiple schools.';
		throw new LogicException($sMsg);

	}

	/**
	 * @param Ext_Thebing_School $oSchool
	 * @return bool
	 */
	public function belongsToSchool(Ext_Thebing_School $oSchool) {

		$aSchoolIds = $this->getJoinTableData('schools');
		return in_array($oSchool->id, $aSchoolIds);

	}

}