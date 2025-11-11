<?php
 
/**
 * @property $id
 * @property $accommodation_id
 * @property $type_id
 * @property $name
 * @property $female
 * @property $male
 * @property $couples
 * @property $single_beds
 * @property $double_beds
 * @property $active
 * @property $valid_until
 * @property $creator_id
 * @property $floor_id
 * @property $created
 * @property $changed
 * @property $user_id
 * @property $position
 */
class Ext_Thebing_Accommodation_Room extends Ext_Thebing_Basic  {

	CONST CACHE_KEY_ROOM_BED_COUNT = 'ts_accommodation_rooms_bed_count';

	/**
	 * @var string
	 */
	protected $_sTable = 'kolumbus_rooms';

	/**
	 * @var mixed[]
	 */
	protected $_aFormat = [
		'name' => [
			'required' => true,
		],
		'single_beds' => [
			'validate' => 'INT_NOTNEGATIVE',
		],
		'double_beds' => [
			'validate' => 'INT_NOTNEGATIVE',
		],
		'accommodation_id' => [
			'validate' => 'INT_NOTNEGATIVE',
		],
	];

	/**
	 * @var mixed[]
	 */
	protected $_aJoinTables = [
		'accommodations_allocations' => [
			'table' => 'kolumbus_accommodations_allocations',
			'class' => 'Ext_Thebing_Accommodation_Allocation',
			'primary_key_field' => 'room_id',
			'autoload' => false,
			'static_key_fields' => [
				'active' => 1,
				'status' => 0,
			]
		],
		'absence' => [ // Purge
			'table' => 'kolumbus_absence',
			'primary_key_field' => 'item_id',
			'autoload' => false,
			'check_active' => true,
			'static_key_fields' => [
				'item' => 'accommodation'
			],
			'on_delete' => 'no_action'
		],
		'cleaning_status' => [
			'table' => 'ts_accommodation_rooms_latest_cleaning_status',
			'primary_key_field' => 'room_id',
			'foreign_key_field' => ['bed', 'status'],
			'autoload' => false,
			'on_delete' => 'no_action'
		]
	];

	/**
	 * @var mixed[]
	 */
	protected $_aFlexibleFieldsConfig = [
		'roomdata' => [],
	];

	/**
	 * Erzeugt ein Query für eine Liste mit Items dieses Objektes
	 * @return array
	 */
	public function getListQueryData($oGui=null) {

		$aQueryData = array();

		$sFormat = $this->_formatSelect();

		$aQueryData['data'] = array();

		$sAliasString = '';
		$sTableAlias = '';
		if(!empty($this->_sTableAlias)) {
			$sAliasString .= '`'.$this->_sTableAlias.'`.';
			$sTableAlias .= '`'.$this->_sTableAlias.'`';
		}

		$aQueryData['sql'] = "
			SELECT
				*
				{FORMAT}
			FROM
				`{TABLE}` ".$sTableAlias."
		";

		$aQueryData['sql'] .= " WHERE ".$sAliasString."`active` = 1 ";

		if(array_key_exists('id', $this->_aData)) {
			$aQueryData['sql'] .= "ORDER BY ".$sAliasString."`id` ASC ";
		}

		$aQueryData['sql'] = str_replace('{FORMAT}', $sFormat, $aQueryData['sql']);
		$aQueryData['sql'] = str_replace('{TABLE}', $this->_sTable, $aQueryData['sql']);

		return $aQueryData;
	}

	public function getNumberOfBeds(): int {
	    return (int)$this->single_beds + ((int)$this->double_beds * 2);
    }

	/**
	 * Liefert den zugehörigen Unterkunftsanbieter.
	 *
	 * @return bool|Ext_Thebing_Accommodation
	 */
	public function getProvider() {

		if($this->accommodation_id > 0) {
			return Ext_Thebing_Accommodation::getInstance($this->accommodation_id);
		}

		return false;
	}

	/**
	 * Nicht unterstützt, kann (über den Unterkunftsanbieter) mehreren Schulen zugewiesen sein.
	 *
	 * @deprecated
	 * @throws LogicException
	 */
	public function getSchool() {

		$sMsg = 'An accommodation room can be assigned to multiple schools.';
		throw new LogicException($sMsg);

	}

	/**
	 * Nicht unterstützt, kann (über den Unterkunftsanbieter) mehreren Schulen zugewiesen sein.
	 *
	 * @deprecated
	 * @throws LogicException
	 */
	public function getSchoolId() {

		$sMsg = 'An accommodation room can be assigned to multiple schools.';
		throw new LogicException($sMsg);

	}

	/**
	 * Gibt alle Zuordnungen des Raums zurück
	 *
	 * @return array
	 */
	public function getAllocations() {

		$sSql = "
			SELECT
				*
			FROM
				`kolumbus_accommodations_allocations` `kaa` INNER JOIN
				`ts_inquiries_journeys_accommodations` `ts_ija` ON
					`ts_ija`.`id` = `kaa`.`inquiry_accommodation_id` AND
					`ts_ija`.`active` = 1
			WHERE
				`kaa`.`room_id` = :room_id AND
				`kaa`.`active` = 1 AND
				`kaa`.`status` = 0 AND
				`kaa`.`matching_canceled` = 0
		";

		$aAllocations = DB::getQueryRows($sSql, array(
			'room_id' => (int)$this->id
		));

		return $aAllocations;
	}

	/**
	 * Prüft ob der Raum eine Zuordnung besitzt
	 *
	 * @return bool
	 */
	public function hasAllocations() {
		$aAllocations = $this->getAllocations();
		return !empty($aAllocations);
	}

	/**
	 * Gibt alle Zuordnungen des Raums in einem Zeitraum zurück
	 *
	 * @param string $sFrom
	 * @param string $sUntil
	 * @return array
	 */
	public function getAllocationsByPeriod($sFrom, $sUntil) {

		$sSql = "
			SELECT
				*
			FROM
				`kolumbus_accommodations_allocations` `kaa` INNER JOIN
				`ts_inquiries_journeys_accommodations` `ts_ija` ON
					`ts_ija`.`id` = `kaa`.`inquiry_accommodation_id` AND
					`ts_ija`.`active` = 1
			WHERE
				`kaa`.`room_id` = :room_id AND
				`kaa`.`active` = 1 AND
				`kaa`.`status` = 0 AND
				`kaa`.`from` <= :until AND
				`kaa`.`until` >= :from AND
				`kaa`.`matching_canceled` = 0
		";

		$aAllocations = DB::getQueryRows($sSql, array(
			'room_id' => (int)$this->id,
			'from' => $sFrom,
			'until' => $sUntil
		));

		return $aAllocations;
	}

	/**
	 * @return Ext_Thebing_Accommodation_Roomtype
	 * @throws Exception
	 */
	public function getType() {
		return Ext_Thebing_Accommodation_Roomtype::getInstance((int)$this->type_id);
	}

	/**
	 * Den aktuellen Reinigungsstatus für ein Bett eintragen
	 *
	 * @param int $iBed
	 * @param string $status
	 * @return $this
	 */
	public function setCleaningStatus(int $iBed, string $status) {

		// Alten Wert - falls vorhanden - rauswerfen
		$aStatus = array_filter($this->cleaning_status, function($aStatus) use ($iBed) {
			return (int)$aStatus['bed'] !== $iBed;
		});

		$aStatus[] = ['bed' => $iBed, 'status' => $status];

		$this->cleaning_status = $aStatus;

		return $this;
	}

	/**
	 * @param bool $bThrowExceptions
	 * @return array|bool
	 */
	public function validate($bThrowExceptions = false) {
		
		$aErrors = parent::validate($bThrowExceptions);
		
		if($aErrors === true) {
			$aErrors = array();
		}

		// Die Anzahl der Betten darf nicht verkleinert werden, da ansonsten Zuweisungen im Interface verschwinden könnten
		// @TODO Prüfung so umbauen, dass geguckt wird, wie hoch die maximale zugewiese Bettenanzahl dieses Raums jemals war
		$iBedsBefore = $this->_aOriginalData['single_beds'] + ($this->_aOriginalData['double_beds'] * 2);
		$iBedsNow = $this->single_beds + ($this->double_beds * 2);

		if(
			$iBedsNow < $iBedsBefore &&
			$this->hasAllocations()
		) {
			$aErrors['single_beds'][] = 'COUNT_OF_BEDS_LOWER_THAN_BEFORE';
		}

		// Wenn Eintrag gelöscht wird: valid_until auf 1970 setzen, damit wirklich alles geprüft wird
		$sValidUntil = $this->valid_until;
		if($this->active == 0) {
			$sValidUntil = '1970-01-01';
		}

		$aInvalidMatchingEntries = Ext_Thebing_Accommodation_Allocation::getInvalidEntries($sValidUntil, $this->accommodation_id, $this->id);

		if(!empty($aInvalidMatchingEntries)) {
			$aErrors['valid_until'][] = 'ALLOCATIONS_EXISTS';
		}

		if(empty($aErrors)) {
			$aErrors = true;
		}
		
		return $aErrors;
	}

	public function save($bLog = true) {
	
		parent::save($bLog);
		
		WDCache::delete(self::CACHE_KEY_ROOM_BED_COUNT);
		
		return $this;
	}
	
}
