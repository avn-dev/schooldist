<?php

namespace TsActivities\Entity\Activity;

/**
 * @property string $id
 * @property string $activity_id
 * @property string $school_id
 * @property array $courses
 */
class ActivitySchool extends \WDBasic {

	protected $_sTable = 'ts_activities_schools';

	protected $_sTableAlias = 'ts_acts';

	protected $_aJoinTables = [
		'courses' => [
			'table' => 'ts_activities_schools_to_courses',
			'foreign_key_field' => 'course_id',
			'primary_key_field' => 'activity_school_id',
		]
	];

}
