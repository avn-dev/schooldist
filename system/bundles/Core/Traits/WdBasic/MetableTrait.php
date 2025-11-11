<?php

namespace Core\Traits\WdBasic;

/**
 * Trait für Entitäten, um mit den hier definierten Methoden beliebige Attribute an die Entität anhängen zu können.
 */
trait MetableTrait {

	use AttributeTrait;

	public function getMeta(string $key, $default = null): mixed {

		if (isset($this->_aAttributes[$key])) {
			throw new \InvalidArgumentException($key.' is an invalid meta key as this is already used for a defined attribute.');
		}

		$attribute = $this->getAttribute($key);

		if ($attribute === null) {
			return $default;
		}

		return $attribute->getValue();

	}

	public function setMeta(string $key, $value) {

		if (isset($this->_aAttributes[$key])) {
			throw new \InvalidArgumentException($key.' is an invalid meta key as this is already used for a defined attribute.');
		}

		return $this->setAttribute($key, $value);

	}

	public function unsetMeta(string $key) {

		$this->removeAttribute($key);

	}

	public function getAllMetaData(): array {

		$attributes = $this->getAttributes();

		$metaData = [];
		foreach ($attributes as $attribute) {
			$metaData[$attribute->key] = $attribute->getValue();
		}

		return $metaData;
	}

}
