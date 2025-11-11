<?php

class Ext_TS_Transfer_Location extends Ext_TC_Basic {

	use Ext_TS_Transfer_Location_Trait;

	protected $_sTable = 'ts_transfer_locations';

	protected $_sTableAlias = 'ts_tl';

	//protected $_sEditorIdColumn = 'editor_id';

	protected $_aJoinTables = [
		'i18n' => [
			'table' => 'ts_transfer_locations_i18n',
	 		'foreign_key_field' => ['language_iso', 'name'],
	 		'primary_key_field'	=> 'location_id',
			'autoload' => true
		],
		'schools' => [
			'table' => 'ts_transfer_locations_schools',
			'foreign_key_field' => 'school_id',
			'primary_key_field' => 'location_id'
		],
	];

	protected $_aFlexibleFieldsConfig = [
		'airports' => []
	];

	/**
	 * Array of the location types 'location', 'school', 'accommodation'
	 * @return string[]
	 */
	public static function getLocationTypes(): array {
		return ['location', 'school', 'accommodation'];
	}

	/**
	 * @TODO Usage überprüfen
	 * @param string $sLanguage
	 * @return string
	 */
	public function getName($sLanguage = null) {

		if(is_bool($sLanguage)) {
			throw new InvalidArgumentException('Wrong param type!');
		}

		return $this->getI18NName('i18n', 'name', $sLanguage);

	}

	/**
	 * @inheritdoc
	 */
	public function manipulateSqlParts(&$aSqlParts, $sView = null) {

		parent::manipulateSqlParts($aSqlParts, $sView);

		$aSqlParts['select'] .= ",
			GROUP_CONCAT(DISTINCT `schools`.`school_id`) AS `schools`
		";

	}

	/**
	 * liefert alle Transferabschnitte, welche als Start oder Ziel diesen Airport
	 * ausgewählt haben
	 * @param mixed $mUseDate
	 * @return array
	 */
	public function getInquiryJourneyTransfers($mUseDate = false, $bAsObject = false) {

		$aSql = array(
			'airport_id' => $this->id,
			'type' => 'location'
		);

		$sWhere = '';
		// Alle Einträge herausfiltern, die über dem valid_until des Airportes
		// liegen
		if($mUseDate !== false) {
			if(
				WDDate::isDate($mUseDate, WDDate::DB_DATE) &&
				$mUseDate != '0000-00-00'
			) {
				$sWhere = " AND `transfer_date` > :valid_until";
				$aSql['valid_until'] = $mUseDate;
			}
		}

		$sSql = "
			SELECT
				`id`
			FROM
				`ts_inquiries_journeys_transfers` 
			WHERE
				`active` = 1 AND
				(
					`start` = :airport_id OR
					`end` = :airport_id
				) AND
				(
					`start_type` = :type OR
					`end_type` = :type
				)
		" . $sWhere;

		$aJourneyTransfers = (array) DB::getQueryCol($sSql, $aSql);

		if($bAsObject) {
			$aReturn = array();
			foreach($aJourneyTransfers as $iId) {
				$iAirport = (int) $iId;
				$oAirport = static::getInstance($iAirport);
				$aReturn[] = $oAirport;
			}
			$aJourneyTransfers = $aReturn;
		}

		return $aJourneyTransfers;
	}

	/**
	 * @param bool $bForSelect
	 * @param int $iSchoolId
	 * @param string|\Tc\Service\LanguageAbstract $sLanguage
	 * @return string[]|static[]
	 */
	public static function getLocations($bForSelect = false, $iSchoolId = null, $sLanguage = null) {

		if($sLanguage === null) {
			$sLanguage = \System::getInterfaceLanguage();
		}

		if($sLanguage instanceof \Tc\Service\LanguageAbstract) {
			$sLanguage = $sLanguage->getLanguage();
		}

		if($iSchoolId !== null) {
			$oSchool = Ext_Thebing_School::getInstance($iSchoolId);
		} else {
			$oSchool = Ext_Thebing_School::getSchoolFromSessionOrFirstSchool();
		}
		
		$sLanguage = $oSchool->getInterfaceLanguage($sLanguage);
		
		$sSelect = "";
		$sJoin = "";

		if($iSchoolId !== null) {
			$sJoin = " INNER JOIN
				`ts_transfer_locations_schools` `ts_tls` ON
					`ts_tls`.`location_id` = `ts_tl`.`id` AND
					`ts_tls`.`school_id` = :school_id
			";
		}

		if($bForSelect) {
			$sSelect .= ", `ts_tl_i18n`.`name` ";
			$sJoin .= " LEFT JOIN
				`ts_transfer_locations_i18n` `ts_tl_i18n` ON
					`ts_tl_i18n`.`location_id` = `ts_tl`.`id` AND
					`ts_tl_i18n`.`language_iso` = :language
			";
		}

		$sSql = "
			SELECT
				`ts_tl`.*
				{$sSelect}
			FROM
				`ts_transfer_locations` `ts_tl`
				{$sJoin}
			WHERE
				`active` = 1 AND (
					`valid_until` = '0000-00-00' ||
					`valid_until` >= NOW()
				)
			ORDER BY
				`position`
		";

		$aResult = (array)DB::getQueryRows($sSql, [
			'school_id' => $iSchoolId,
			'language' => $sLanguage
		]);

		if($bForSelect) {
			$aReturn = [];
			foreach($aResult as $aRow) {
				$aReturn[$aRow['id']] = $aRow['name'];
			}
		} else {
			$aReturn = array_map(function($aRow) {
				return static::getObjectFromArray($aRow);
			}, $aResult);
		}

		return $aReturn;

	}

	public static function getLabel($sType, $iId, $sLanguage) {

		return match ($sType) {
			'location' => static::getInstance($iId)->getName($sLanguage),
			'school' => Ext_Thebing_L10N::t('Schule', $sLanguage, 'Thebing » Transfer'),
			'accommodation' => Ext_Thebing_L10N::t('Unterkunft', $sLanguage, 'Thebing » Transfer')
		};

	}

}
