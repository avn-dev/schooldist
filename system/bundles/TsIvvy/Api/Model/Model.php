<?php

namespace TsIvvy\Api\Model;

use Illuminate\Support\Collection;
use TsIvvy\Api;

class Model {

	protected bool $complete;

	protected Api $api;

	protected Collection $data;

	public function __construct(Api $api, $data, bool $complete = false) {

		if (is_array($data)) {
			$data = new Collection($data);
		} else if ($data === null) {
			$data = new Collection([]);
		}

		$this->api = $api;
		$this->data = $data;
		$this->complete = $complete;
	}

	public function getId() {
		return $this->data->get('id');
	}

	public function getName(): string {

		if ($this->data->has('name')) {
			return $this->data->get('name');
		} else if (
			$this->data->has('firstName') &&
			$this->data->has('lastName')
		) {
			return implode(', ', [$this->data->get('lastName'), $this->data->get('firstName')]);
		}

		return 'Unknown booker';
	}

	public function isComplete(): bool {
		return $this->complete;
	}

	public function get(string $key) {
		return $this->data->get($key);
	}

}
