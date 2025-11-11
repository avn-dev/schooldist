<?php

namespace TsRegistrationForm\Validation\Rules;

use Illuminate\Contracts\Validation\Rule;
use TsRegistrationForm\Helper\ServiceDatesHelper;

/**
 * In Kursen eingestellte Unterkunftskombinationen überprüfen
 */
class AccommodationCombinationRule implements Rule {

	/**
	 * @var array
	 */
	private $accommodation;

	/**
	 * @var ServiceDatesHelper
	 */
	private $datesHelper;

	public function __construct(array $accommodation, ServiceDatesHelper $datesHelper) {
		$this->accommodation = $accommodation;
		$this->datesHelper = $datesHelper;
	}

	public function passes($attribute, $value) {
		$combinationKey = sprintf('%s_%s_%s', $this->accommodation['accommodation'], $this->accommodation['roomtype'], $this->accommodation['board']);
		return $this->datesHelper->checkCourseAccommodationCombination($combinationKey);
	}

	public function message() {
		return 'Accommodation combination not available.';
	}
}