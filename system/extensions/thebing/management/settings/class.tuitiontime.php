<?php

/**
 * @property $tuition_time_id
 * @property $days
 * @property $courses
 */
class Ext_Thebing_Management_Settings_TuitionTime extends Ext_TC_Config_Child_Entity {

	protected $_aData = [
		'id' => 0,
		'tuition_time_id' => 0,
		'days' => [],
		'courses' => []
	];

	public static function getConfigKey(): string {
		return 'ts_statistic_tuition_times';
	}

	public function __set($name, $value) {

		if ($name === 'tuition_time_id') {
			$value = (int)$value;
		} elseif (
			$name === 'days' ||
			$name === 'courses'
		) {
			$value = array_map(function ($id) {
				return (int)$id;
			}, $value);
		}

		parent::__set($name, $value);

	}

	public function getTuitionTime(): \Ext_Thebing_Tuition_Template {
		return \Ext_Thebing_Tuition_Template::getInstance($this->tuition_time_id);
	}

	public static function findOneByTuitionTime($tuitionTimeId): ?self {

		$entries = collect(self::findAll())->filter(function (self $entry) use ($tuitionTimeId) {
			return $entry->tuition_time_id == $tuitionTimeId;
		});

		if ($entries->count() > 1) {
			throw new LogicException('More than one entry found');
		}

		return $entries->first();

	}

	/**
	 * @param $day
	 * @param $courseId
	 * @return \Illuminate\Support\Collection|self[]
	 */
	public static function findByDayAndCourse($day, $courseId) {

		return collect(self::findAll())->filter(function (self $entry) use ($day, $courseId) {
			return in_array($day, $entry->days) && in_array($courseId, $entry->courses);
		});

	}

}