<?php

/**
 * @property $id
 * @property $entity
 * @porperty $entity_id
 * @property $key
 * @property $value
 */
class WDBasic_Attribute extends WDBasic {

	const TABLE_KEY = 'attributes';

	protected $_sTable = 'wdbasic_attributes';

	protected $_aFormat = [
		'entity' => [
			'required' => true,
		],
		'entity_id' => [
			// required funktioniert objekt-relational nicht
			// 'required' => true,
			'validate' => 'INT_POSITIVE',
		],
		'key' => [
			'required' => true,
		]
	];

	public function setValue(mixed $value): static
	{
		if (is_array($value)) {
			$value = json_encode($value);
		}
		$this->value = $value;
		return $this;
	}

	public function getValue(): mixed
	{
		if (
			str_starts_with($this->value, '{') ||
			str_starts_with($this->value, '[')
		) {
			if (is_array($value = json_decode($this->value, true))) {
				return $value;
			}
		}
		return $this->value;
	}

}
