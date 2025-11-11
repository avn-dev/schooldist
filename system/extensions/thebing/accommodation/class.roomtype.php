<?php

/**
 * @property int $id
 * @property int $active
 * @property string $valid_until YYYY-MM-DD
 * @property int $creator_id Ersteller
 * @property int $changed Timestamp
 * @property int $user_id Bearbeiter
 * @property int $created Timestamp
 * @property int $type
 * @property int $position
 * @property string $name_LANG pro verfügbarer Sprache, siehe Ext_Thebing_Accommodation_Roomtype::getName()
 * @property string $short_LANG pro verfügbarer Sprache, siehe Ext_Thebing_Accommodation_Roomtype::getShortName()
 */
class Ext_Thebing_Accommodation_Roomtype extends Ext_TS_Inquiry_Journey_Accommodation_Info_Abstract {

	use \Tc\Traits\Placeholder;
	
	/**
	 * @var string
	 */
	protected $_sTable = 'kolumbus_accommodations_roomtypes';

	/**
	 * @var string
	 */
	protected $_sTableAlias = 'kar';

		protected $_sEditorIdColumn = 'editor_id';
	
	/**
	 * @var mixed[]
	 */
	protected $_aJoinTables = [
		'accommodations_allocations' => [
			'table' => 'ts_inquiries_journeys_accommodations',
			'class' => 'Ext_TS_Inquiry_Journey_Accommodation',
			'primary_key_field' => 'roomtype_id',
			'autoload' => false,
			'check_active' => true,
			'delete_check' => true,
		],
		'room' => [
			'table' => 'kolumbus_rooms',
			'class' => 'Ext_Thebing_Accommodation_Room',
			'primary_key_field' => 'type_id',
			'autoload' => false,
			'check_active' => true,
			'delete_check' => true,
		],
		'schools' => [
			'table' => 'ts_accommodation_roomtypes_schools',
			'foreign_key_field' => 'school_id',
			'primary_key_field' => 'accommodation_roomtype_id',
		],
	];

	/**
	 * @var mixed[]
	 */
	protected $_aFlexibleFieldsConfig = [
		'roomtypes' => []
	];

	protected $_sPlaceholderClass = \TsAccommodation\Service\Placeholder\RoomtypePlaceholder::class;
	
	/**
	 * @TODO Es gibt hier drei Methoden, die nahezu dasselbe machen (auch bei Category und Meal)
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
	 * @param bool $bMatchAllSchools
	 * @return mixed[]
	 */
	public static function getListForSchools(array $aSchoolIds, $bForSelect = true, $sLanguage = null, $bMatchAllSchools = false) {

		if(empty($sLanguage)) {
			$sLanguage = Ext_Thebing_Util::getInterfaceLanguage();
		}

		$aRoomtypes = self::getList(false, $sLanguage);
		$aResult = [];

		if(empty($aSchoolIds)) {
			return $aResult;
		}

		foreach($aRoomtypes as $aRoomtype) {

			$aRoomtypeAvailableSchoolIds = explode(',', $aRoomtype['schools']);

			if($bMatchAllSchools) {
				foreach($aSchoolIds as $iSchoolId) {
					if(!in_array($iSchoolId, $aRoomtypeAvailableSchoolIds)) {
						continue 2;
					}
				}
				$aResult[] = $aRoomtype;
			} else {
				foreach($aSchoolIds as $iSchoolId) {
					if(in_array($iSchoolId, $aRoomtypeAvailableSchoolIds)) {
						$aResult[] = $aRoomtype;
						break;
					}
				}
			}

		}

		if(!$bForSelect) {
			return $aResult;
		}

		$aBack = [];
		foreach ($aResult as $aData) {
			$aBack[$aData['id']] = $aData['name_'.$sLanguage];
		}
		return $aBack;

	}

	/**
	 * @TODO Es gibt hier drei Methoden, die nahezu dasselbe machen (auch bei Category und Meal)
	 * @TODO valid_until wird hier ignoriert
	 *
	 * Gibt eine Liste mit Einträgen zurück, die der angegebenen Schule zugewiesen sind.
	 *
	 * Die Keys sind die IDs der Einträge, das Array ist nach der Listensortierung ("position"-Feld) sortiert.
	 *
	 * @param Ext_Thebing_School $oSchool
	 * @return Ext_Thebing_Accommodation_Roomtype[]
	 */
	public static function getRoomTypesBySchool(Ext_Thebing_School $oSchool) {

		$sSql = "
			SELECT
				`kar`.*
			FROM
				`kolumbus_accommodations_roomtypes` `kar` INNER JOIN
				`ts_accommodation_roomtypes_schools` `ts_ars` ON
					`ts_ars`.`accommodation_roomtype_id` = `kar`.`id` AND
					`ts_ars`.`school_id` = :school_id
			WHERE
				`kar`.`active` = 1
			GROUP BY
				`kar`.`id`
			ORDER BY
				`kar`.`position`,
				`kar`.`id`
		";

		$aResult = (array)DB::getQueryRowsAssoc($sSql, ['school_id' => (int)$oSchool->id]);

		array_walk($aResult, function(&$aRow, $iId) {
			$aRow['id'] = $iId;
			$aRow = static::getObjectFromArray($aRow);
		});

		return $aResult;

	}

	/**
	 * @inheritdoc
	 */
	public function manipulateSqlParts(&$aSqlParts, $sView = null) {

		$aSqlParts['select'] .= ",
			GROUP_CONCAT(DISTINCT `schools`.`school_id`) AS `schools`
		";

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
	 * {@inheritdoc}
	 */
	protected function _getInfoKey() {
		return 'roomtype_id';
	}

	/**
	 * Nicht unterstützt, kann mehreren Schulen zugewiesen sein.
	 *
	 * @deprecated
	 * @throws LogicException
	 */
	public function getSchool() {

		$sMsg = 'An accommodation rommtype can be assigned to multiple schools.';
		throw new LogicException($sMsg);

	}

	/**
	 * Nicht unterstützt, kann mehreren Schulen zugewiesen sein.
	 *
	 * @deprecated
	 * @throws LogicException
	 */
	public function getSchoolId() {

		$sMsg = 'An accommodation rommtype can be assigned to multiple schools.';
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

	public static function getTypeOptions(\Tc\Service\LanguageAbstract $language): array {
		return [
			$language->translate('Einzelzimmer'),
			$language->translate('Doppelzimmer'),
			$language->translate('Geteiltes Zimmer')
		];
	}

}