<?php

namespace TsRegistrationForm\Validation\Rules;

use Carbon\Carbon;
use Illuminate\Contracts\Validation\Rule;
use Illuminate\Support\Collection;

/**
 * Prüfen, ob die Unterkunft an den übergebenen Start- und Enddatum verfügbar ist
 */
class AccommodationAvailabilityRule implements Rule {

	/**
	 * @var string start|end
	 */
	private $type;

	/**
	 * @var Collection
	 */
	private $dates;

	public function __construct(string $type, Collection $dates) {
		$this->type = $type;
		$this->dates = $dates;
	}

	public function passes($attribute, $value) {
		$date = Carbon::parse($value, 'UTC');
		return $this->dates->some(function (array $dateObj) use ($date) {
			if ($dateObj['type'] !== $this->type) {
				return false;
			}
			return $date->between(Carbon::parse($dateObj['start'], 'UTC'), Carbon::parse($dateObj['end'], 'UTC'));
		});
	}

	public function message() {
		return 'Accommodation start/end is not available.';
	}
}