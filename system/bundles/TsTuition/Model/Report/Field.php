<?php

namespace TsTuition\Model\Report;

class Field {
	
	public $label;
	protected $prepareField;
	protected $selectField;
	protected $settings;
	public $flex;
	protected $fieldId;
	protected $queryParameters = [];

	static public function getInstance(string $label) {
		
		$instance = new static(\L10N::t($label, \Ext_Thebing_Tuition_Report_Gui2::$_sDescription));
		
		return $instance;
	}
	
	public static function getFlexInstance(string $label) {
		
		$instance = new self($label);
		
		return $instance;
	}
	
	private function __construct($label) {
		$this->label = $label;
	}
	
	public function setFieldId(int $fieldId):self {
		$this->fieldId = $fieldId;
		return $this;
	}
	
	public function setSettings(array $settings):self {
		$this->settings = $settings;
		return $this;
	}

	public function hasSettings() {
		if(!empty($this->settings)) {
			return true;
		}
		return false;
	}
	
	public function getSettings() {
		return $this->settings;
	}

	public function setPrepareField(callable $prepareField):self {
		$this->prepareField = $prepareField;
		return $this;
	}
	
	public function hasPrepareField() {
		if(!empty($this->prepareField)) {
			return true;
		}
		return false;
	}
	
	public function getPrepareField(array $value, array $column) {		
		return ($this->prepareField)($value, $column);
	}
	
	public function setFlex(bool $flex):self {
		$this->flex = $flex;
		return $this;
	}
	
	public function setSelectField(string|array $selectField):self {
		$this->selectField = $selectField;
		return $this;
	}
	
	public function hasSelectField() {
		if(!empty($this->selectField)) {
			return true;
		}
		return false;
	}
	
	public function getSelectField(array $column, string $setting=null) {

		if(is_array($this->selectField)) {

			if(
				$setting !== null &&
				!empty($this->selectField[$setting])
			) {
				return $this->selectField[$setting];
			}

			return reset($this->selectField);
		}

        return $this->selectField;
	}

	public function setQueryParameters(array $queryParams):self {
		$this->queryParameters = $queryParams;
		return $this;
	}

	public function getQueryParameters() {
		return $this->queryParameters;
	}

}
