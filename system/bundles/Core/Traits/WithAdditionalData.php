<?php

namespace Core\Traits;

trait WithAdditionalData
{
	protected array $additionalData = [];

	public function additional(array $data): static
	{
		$this->additionalData = array_merge($this->additionalData, $data);
		return $this;
	}

	public function additionalIf(bool $condition, array $data): static
	{
		if ($condition) {
			return $this->additional($data);
		}
		return $this;
	}

	public function getAdditionalData(): array
	{
		return $this->additionalData;
	}
}