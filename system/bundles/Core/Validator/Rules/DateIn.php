<?php

namespace Core\Validator\Rules;

use Illuminate\Contracts\Validation\Rule;

class DateIn implements Rule {

	protected $values;

	public function __construct($values) {
		$this->values = $values;
	}

	public function passes($attribute, $value) {

		$date = (new \DateTime($value))->format('Y-m-d');

		return in_array($date, $this->values);

	}

	public function message() {
		return '';
	}
}