<?php

namespace FideloSoftware\Spam\Strategies;

use FideloSoftware\Spam\Contracts\Form;
use Illuminate\Http\Request;

/**
 * Value-Blacklist
 *
 * Disallow specific values in form
 */
class ValueBlacklistStrategy extends AbstractStrategy {

	private $blacklist = [];


	/**
	 * e.g.:
	 * $blacklist => [
	 * 		'name' => ['firstname' => 'John', 'lastname' => 'Doe'],
	 * 		'firstname' => 'John'
	 * ]
	 *
	 * @param array $blacklist
	 */
	public function __construct(array $blacklist) {
		$this->blacklist = $blacklist;
	}

	public function detect(Form $form, Request $request): bool {

		$values = $form->getFieldValues();

		$checkValue = function ($formField, $forbiddenValue) use ($values) {
			$value = $values[$formField] ?? '';
			if (is_string($value) && $value == $forbiddenValue) {
				return true;
			}
			return false;
		};

		$matches = 0;
		foreach($this->blacklist as $formField => $forbiddenValue) {

			$checked = [];
			if (is_array($forbiddenValue)) {
				foreach ($forbiddenValue as $innerForbiddenValue) {
					$checked[] = $checkValue($formField, $innerForbiddenValue);
				}
			} else {
				$checked[] = $checkValue($formField, $forbiddenValue);
			}

			if (in_array(true, $checked)) {
				$matches++;
			}
		}

		if($matches > 0) {
			$this->info(['matches' => $matches]);
			return true;
		}

		return false;
	}

}
