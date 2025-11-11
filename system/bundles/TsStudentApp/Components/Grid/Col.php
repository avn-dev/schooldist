<?php

namespace TsStudentApp\Components\Grid;

use TsStudentApp\Components\Component;

class Col implements Component
{
	private array $components = [];

	private ?string $size = null;

	public function getKey(): string
	{
		return 'ion-col';
	}

	public function size(string $size): static
	{
		$this->size = $size;
		return $this;
	}

	public function add(Component $component): static
	{
		$this->components[] = [
			'name' => $component->getKey(),
			'data' => $component->toArray(),
		];
		return $this;
	}

	public function toArray(): array
	{
		return [
			'key' => $this->getKey(),
			'size' => $this->size,
			'components' => $this->components
		];
	}
}