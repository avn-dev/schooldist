<?php

namespace TsRegistrationForm\Validation\Rules;

use Illuminate\Contracts\Validation\Rule;

/**
 * Prüfen, ob Kurs mit dem Alter gebucht werden kann
 */
class CourseAgeRule implements Rule {

	private int $age;

	private int $minAge;

	private int $maxAge;

	public function __construct(int $age, int $minAge, int $maxAge) {
		$this->age = $age;
		$this->minAge = $minAge;
		$this->maxAge = $maxAge;
	}

	public function passes($attribute, $value) {

		if (empty($this->age)) {
			return true;
		}

		if (
			!empty($this->minAge) &&
			$this->age < $this->minAge
		) {
			return false;
		}

		if (
			!empty($this->maxAge) &&
			$this->age > $this->maxAge
		) {
			return false;
		}

		return true;

	}

	/**
	 * Siehe Blockübersetzung: service_removed_age
	 *
	 * @internal
	 */
	public function message() {
		return 'Course not available for student age.';
	}

}
