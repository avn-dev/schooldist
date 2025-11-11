<?php

/**
 * @property int $id
 * @property string $created (TIMESTAMP)
 * @property string $changed (TIMESTAMP)
 * @property int $active
 * @property int $creator_id
 * @property int $editor_id
 * @property int $holiday_id
 * @property int $journey_course_id
 * @property int $journey_split_course_id
 * @property int $journey_accommodation_id
 * @property int $journey_split_accommodation_id
 * @property int $original_weeks
 * @property string $original_from (DATE)
 * @property string $original_until (DATE)
 */
class Ext_TS_Inquiry_Holiday_Splitting extends Ext_Thebing_Basic {

	protected $_sTable = 'ts_inquiries_holidays_splitting';


	protected $_aFormat = [
		// TODO Funktioniert ohne IDs in der Ebene irgendwie nicht
//		'holiday_id' => [
//			'validate' => 'INT_POSITIVE',
//			'required' => true
//		],
		'original_weeks' => [
			'validate' => 'INT_POSITIVE'
		],
		'original_from' => [
			'validate' => 'DATE'
		],
		'original_until' => [
			'validate' => 'DATE'
		]
	];

	protected $_aJoinedObjects = [
		// Leider notwendig, da die WDBasic keine Polymorphic Relations kann
		'old_course' => [
			'class' => 'Ext_TS_Inquiry_Journey_Course',
			'key' => 'journey_course_id',
			'type' => 'parent'
		],
		'new_course' => [
			'class' => 'Ext_TS_Inquiry_Journey_Course',
			'key' => 'journey_split_course_id',
			'type' => 'parent'
		],
		'old_accommodation' => [
			'class' => 'Ext_TS_Inquiry_Journey_Accommodation',
			'key' => 'journey_accommodation_id',
			'type' => 'parent'
		],
		'new_accommodation' => [
			'class' => 'Ext_TS_Inquiry_Journey_Accommodation',
			'key' => 'journey_split_accommodation_id',
			'type' => 'parent'
		]
	];

	public function save($bLog = true) {

		if(
			!empty($this->journey_course_id) &&
			!empty($this->journey_accommodation_id)
		) {
			throw new RuntimeException('Both course and accommodation set!');
		}

		return parent::save($bLog);

	}

	/**
	 * $this->_aJoinedObjects wird benötigt für noch nicht gespeicherte Objekte,
	 * wo aber bereits getType() aufgerufen wird (z.B. Fehlerüberprüfung im Splitter).
	 *
	 * @return string
	 */
	public function getType() {

		if(
			!empty($this->journey_course_id) ||
			!empty($this->_aJoinedObjects['old_course']['object'])
		) {
			return 'course';
		} elseif(
			!empty($this->journey_accommodation_id) ||
			!empty($this->_aJoinedObjects['old_accommodation']['object'])
		) {
			return 'accommodation';
		}

		throw new RuntimeException('Unknown split type!');

	}

	/**
	 * @return bool
	 */
	public function hasOriginalData() {

		if(
			!empty($this->original_weeks) &&
			!empty($this->original_from) &&
			!empty($this->original_until)
		) {
			return true;
		}

		return false;

	}

	/**
	 * Prüfen, ob diese Entität auch eine Splittung hat. Sollte die split_id null sein,
	 * handelt es sich um eine verschobene Leistung.
	 *
	 * @return bool
	 */
	public function hasSplitting() {

		if(
			(
				!empty($this->journey_course_id) &&
				!empty($this->journey_split_course_id)
			) || (
				!empty($this->journey_accommodation_id) &&
				!empty($this->journey_split_accommodation_id)
			)
		) {
			return true;
		}

		// Leider liefert getJoinedObject() IMMER ein Objekt zurück
		if(
			(
				!empty($this->_aJoinedObjects['old_course']['object']) &&
				!empty($this->_aJoinedObjects['new_course']['object'])
			) || (
				!empty($this->_aJoinedObjects['old_accommodation']['object']) &&
				!empty($this->_aJoinedObjects['new_accommodation']['object'])
			)
		) {
			return true;
		}

		return false;

	}

}
