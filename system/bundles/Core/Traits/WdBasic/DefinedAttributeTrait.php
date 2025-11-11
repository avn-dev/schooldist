<?php

namespace Core\Traits\WdBasic;

/**
 * Trait, der für die WDBasic generell fest definierte und typisierte Attribute bereitstellt. Dieser Trait wird von
 * der WDBasic immer eingebunden und stellt V2 der Attribut-Implementierung dar, inkl. Legacy-Behandlung.
 */
trait DefinedAttributeTrait {

	use AttributeTrait;

	private $legacyMapping = [
		'WDBasic_Attribute_Type_Array' => 'array',
		'WDBasic_Attribute_Type_Decimal' => 'float',
		'WDBasic_Attribute_Type_Float' => 'float',
		'WDBasic_Attribute_Type_Int' => 'int',
		'WDBasic_Attribute_Type_Text' => 'text',
		'WDBasic_Attribute_Type_TinyInt' => 'int',
		'WDBasic_Attribute_Type_Varchar' => 'string'
	];

	private function getDefinedAttributeDefinition(string $key) {

		if (empty($this->_aAttributes[$key])) {
			throw new \DomainException('Defined attribute does not exist: '.$key);
		}

		// Kompatibilität
		if (
			!isset($this->_aAttributes[$key]['type']) &&
			isset($this->_aAttributes[$key]['class'])
		) {
			$this->_aAttributes[$key]['type'] = $this->legacyMapping[$this->_aAttributes[$key]['class']];
		}

		if (
			empty($this->_aAttributes[$key]['type']) ||
			!in_array($this->_aAttributes[$key]['type'], $this->legacyMapping)
		) {
			throw new \DomainException('Invalid attribute type for '.$key);
		}

		return $this->_aAttributes[$key];

	}

	private function getDefinedAttribute(string $key) {

		$attribute = $this->getAttribute($key);

		if (!$attribute) {
			return null;
		}

		$definition = $this->getDefinedAttributeDefinition($key);
		$value = $attribute->value;

		switch ($definition['type']) {
			case 'int':
				return (int)$value;
			case 'float':
				return (float)$value;
			case 'array':
				return json_decode($value, true);
			default:
				return (string)$value;
		}

	}

	private function setDefinedAttribute(string $key, $value) {

		if ($value === null) {
			$this->setAttribute($key, $value);
			return;
		}

		$definition = $this->getDefinedAttributeDefinition($key);

		switch ($definition['type']) {
			case 'int':
				$value = (int)$value;
				break;
			case 'float':
				$value = (float)$value;
				break;
			case 'array':
				// Damit keine leeren Arrays gespeichert werden
				if(empty($value)) {
					$value = null;
				}
				$value = json_encode($value);
				break;
			default:
				$value = (string)$value;
				break;
		}

		$this->setAttribute($key, $value);

	}

	public function addDynamicAttribute(string $key, string $type='string') {
		$this->_aAttributes[$key] = [
			'type' => $type
		];
	}

}
