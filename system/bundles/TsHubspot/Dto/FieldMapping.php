<?php

namespace TsHubspot\Dto;

use Closure;

class FieldMapping {

	public $field;
	public $label;
	public $service;
	public $getter;

	static public function getInstance(string $field) {
		$instance = new static($field);
		return $instance;
	}

	private function __construct($field) {
		$this->field = $field;
	}

	public function getField() {
		return $this->field;
	}

	public function setLabel(string $label) :self {
		$this->label = $label;
		return $this;
	}

	public function getLabel() :string {
		return $this->label;
	}

	public function setService(string $service) :self {
		$this->service = $service;
		return $this;
	}

	public function getService() :string {
		return $this->service;
	}

	public function setGetter(closure $getter) :self {
		$this->getter = $getter;
		return $this;
	}

	public function getGetter() :Closure {
		return $this->getter;
	}

}

