<?php

namespace Core\Traits\WdBasic;

use WDBasic_Attribute;

/**
 * Trait für die Basis-Funktion der WDBasic-Attribute
 */
trait AttributeTrait {

	private function getAttributes(): array {

		$this->_aJoinedObjects[WDBasic_Attribute::TABLE_KEY] = [
			'class' => WDBasic_Attribute::class,
			'key' => 'entity_id',
			'static_key_fields' => ['entity' => $this->getTableName()],
			'type' => 'child',
			'on_delete' => 'no_action', // Attributes nicht löschen
			'cloneable' => true
		];

		return $this->getJoinedObjectChilds(WDBasic_Attribute::TABLE_KEY, true);

	}

	private function getAttribute(string $key): ?WDBasic_Attribute {

		return \Illuminate\Support\Arr::first($this->getAttributes(), function (WDBasic_Attribute $attribute) use ($key) {
			return $attribute->key === $key;
		});

	}

	private function setAttribute(string $key, $value): self {

		$attribute = $this->getAttribute($key);

		// Leere Werte nicht speichern
		if (
			$value === null ||
			$value === ''
		) {
			if ($attribute !== null) {
				$this->deleteJoinedObjectChild(WDBasic_Attribute::TABLE_KEY, $attribute);
			}
			return $this;
		}

		if ($attribute === null) {
			/** @var WDBasic_Attribute $attribute */
			$attribute = $this->getJoinedObjectChild(WDBasic_Attribute::TABLE_KEY);
			$attribute->key = $key;
		}

		$attribute->setValue($value);

		return $this;

	}

	private function removeAttribute(string $key) {

		$attribute = $this->getAttribute($key);

		if ($attribute !== null) {
			$this->deleteJoinedObjectChild(WDBasic_Attribute::TABLE_KEY, $attribute);
		}

	}

	/**
	 * @TODO @MP Wenn disableValidate() nicht funktioniert, kann man immer noch mit WDBasic_Attribute direkt arbeiten.
	 *   Per Query wird nichts geloggt!
	 *
	 * Äquivalent zu updateField().
	 * Methode speichert ohne ->save() aufzurufen (executePreparedQuery()) und erstellt das Attribute, falls nicht vorhanden (REPLACE)
	 */
	public function updateAttribute($key, $value) :null {

		// Query und kein ->save(), weil bei manchen Objekten Fehler beim Dialogspeichern auftreten würden und somit auch
		// Fehler beim ->save() auftreten würden. Eigentlich würde man dann ->updateField() machen, aber bei Attributen
		// dann eben ->updateAttribute().
		$sSql = "
					REPLACE 
						wdbasic_attributes 
					SET
						`entity` = :entity,
						`entity_id` = :entity_id,	
						`key` = :key,	
						`value` = :value
				";

		$aSql = [
			'entity' => $this->_sTable,
			'entity_id' => $this->id,
			'key' => $key,
			'value' => $value
		];

		\DB::executePreparedQuery($sSql, $aSql);

		return null;
	}
}
