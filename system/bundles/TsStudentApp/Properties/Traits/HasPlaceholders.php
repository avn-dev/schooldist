<?php

namespace TsStudentApp\Properties\Traits;

use TsStudentApp\Facades\PropertyKey;

trait HasPlaceholders
{
	protected array $placeholders = [];

	abstract protected function rawProperty(): string;

	public function property(): string {
		return PropertyKey::generate($this->rawProperty(), $this->placeholders);
	}

	public function placeholders(array $placeholders): static
	{
		$this->placeholders = $placeholders;
		return $this;
	}
}