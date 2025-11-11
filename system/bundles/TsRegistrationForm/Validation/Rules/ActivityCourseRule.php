<?php

namespace TsRegistrationForm\Validation\Rules;

use Illuminate\Contracts\Validation\Rule;

/**
 * In Aktivitäten eingestellte Kurse überprüfen
 */
class ActivityCourseRule implements Rule {

	/**
	 * @var array
	 */
	private $activityCourses;

	/**
	 * @var array
	 */
	private $allSelectedCourses;

	public function __construct(array $activityCourses, array $allSelectedCourses) {
		$this->activityCourses = $activityCourses;
		$this->allSelectedCourses = $allSelectedCourses;
	}

	public function passes($attribute, $value) {
		// Keine Kurse eingestellt = keine Abhängigkeit auf Kurs
		if (empty($this->activityCourses)) {
			return true;
		}
		return collect($this->activityCourses)->some(function ($courseId) {
			return in_array($courseId, $this->allSelectedCourses);
		});

	}

	public function message() {
		return 'Activity not available, course needed.';
	}

	public static function pluckCourseIds(\Ext_TS_Inquiry $inquiry): array {
		return collect($inquiry->getJourney()->getCoursesAsObjects())->pluck('course_id')->toArray();
	}

}