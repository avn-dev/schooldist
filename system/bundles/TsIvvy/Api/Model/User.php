<?php

namespace TsIvvy\Api\Model;

use TsIvvy\Api;

/**
 * Koordinator
 */
class User extends Model {

	public function getFullName(): string {

		$name = [
			$this->getLastname(),
			$this->getFirstname()
		];

		return implode(', ', $name);
	}

	public function getFirstname(): string {
		return $this->data->get('firstName', "");
	}

	public function getLastname(): string {
		return $this->data->get('lastName', "");
	}
}
