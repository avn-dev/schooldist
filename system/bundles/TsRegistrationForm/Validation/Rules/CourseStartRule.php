<?php

namespace TsRegistrationForm\Validation\Rules;

use Core\Validator\Rules\DateIn;
use TsRegistrationForm\Dto\FrontendCourse;

/**
 * Prüfen, ob Kurs mit dem Alter gebucht werden kann
 */
class CourseStartRule extends DateIn
{
	public function __construct($values, private readonly FrontendCourse $course)
	{
		parent::__construct($values);
	}

	public function passes($attribute, $value)
	{
		// Wenn Kurs von Ferien gesplittet wurde, kann das Datum ggf. gar nicht zur Verfügung stehen
		$holidaySplit = data_get($this->course->additional, 'request_course.field_state.holiday_split', false);
		if ($holidaySplit) {
			return true;
		}

		return parent::passes($attribute, $value);
	}
}
