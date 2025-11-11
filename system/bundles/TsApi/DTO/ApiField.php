<?php

namespace TsApi\DTO;

class ApiField {

	public $sAlias;

	public $sColumn;

	public $sField;

	// Readonly ApiFields are ignored by their setValue(), use for fields that are updated manually
	public bool $readonly = false;

	public $aValidation = [];

	/**
	 * @param string $sAlias
	 * @param string $sField
	 * @param array $aValidation
	 * @param string|null $sColumn
	 */
	public function __construct(string $sAlias, string $sField, array $aValidation = null, string $sColumn = null, bool $readonly = false) {
		$this->sAlias = $sAlias;
		$this->sField = $sField;
		$this->sColumn = $sColumn;
		$this->readonly = $readonly;
		if($aValidation !== null) {
			$this->aValidation = $aValidation;
		}
		if($sColumn === null) {
			$this->sColumn = $this->sField;
		}
	}

	public function addValidation($validation) {
		$this->aValidation[] = $validation;
	}
	
	
	public function setValue($entity, $value, array $objectsByAlias = []): void {
		if ($this->readonly) {
			return;
		}
		// First look in Handler for reference, if not there, try to get from inquiry->getObjectByAlias()
		// Some aliases are not retrievable with inquiry->getObjectByAlias(), those are stored in \TsApi\Handler\AbstractHandler $objectsByAlias
		if ($objectsByAlias[$this->sAlias]) {
			$object = $objectsByAlias[$this->sAlias];
		} else {
			$object = $entity->getObjectByAlias($this->sAlias, $this->sColumn);
		}
		// Object doesnt exist, nothing to update
		if (empty($object)) {
			return;
		}
		$object->{$this->sColumn} = $value;
	}
}
