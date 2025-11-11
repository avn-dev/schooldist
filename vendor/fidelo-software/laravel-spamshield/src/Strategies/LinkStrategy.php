<?php

namespace FideloSoftware\Spam\Strategies;

use FideloSoftware\Spam\Contracts\Form;
use Illuminate\Http\Request;

/**
 * Links
 *
 * Allow only a specific number of links in input values.
 */
class LinkStrategy extends AbstractStrategy {

	private $numberOfLinksAllowed;

	private $fieldNames;

	public function __construct(int $numberOfLinksAllowed = 0, array $fieldNames = []) {

		$this->numberOfLinksAllowed = $numberOfLinksAllowed;
		$this->fieldNames = $fieldNames;
	}

	public function detect(Form $form, Request $request): bool {

		$values = $form->getFieldValues();

		if (!in_array('*', $this->fieldNames)) {
			$values = array_intersect_key($values, array_flip($this->fieldNames));
		}

        $log = [];
		foreach ($values as $field => $value) {
			// Allow links in URL name attributes
			if (!is_string($value) || strpos($field, 'url') !== false) {
				continue;
			}

			// Find links in value
			preg_match_all('@http://|https://|ftp://@', $value, $result);

			if (isset($result[0])) {
                $log[$field] = count($result[0]);
			}
		}

        $linkCount = array_sum($log);

		if($linkCount > $this->numberOfLinksAllowed) {
			// Maximum number of allowed links exceeded
			$this->info($log);
			return true;
		}

		return false;
	}

}
