<?php

namespace Tc\Traits\Entity;

trait HasSystemTypes
{
	abstract protected function getEntityTypeForSystemTypes(): string;

	public function hasSystemType(string $sSystemType): bool
	{
		return in_array($sSystemType, $this->getSystemTypes());
	}

	public function addSystemType(string $systemType): static
	{
		if ($this->hasSystemType($systemType)) {
			return $this;
		}

		// Alle zuweisen
		$mappings = \Tc\Entity\SystemTypeMapping::getRepository()
			->findBySystemType($this->getEntityTypeForSystemTypes(), $systemType)
			->pluck('id');

		$systemTypes = $this->system_types;
		$systemTypes = array_merge($systemTypes, $mappings->toArray());
		$this->system_types = array_unique($systemTypes);
		return $this;
	}

	public function getSystemTypes(): array
	{
		$mappings = $this->getJoinTableObjects('system_types');
		$systemTypes = [];

		foreach ($mappings as $mapping) {
			$systemTypes = array_merge($systemTypes, $mapping->system_types);
		}

		return array_unique($systemTypes);
	}
}