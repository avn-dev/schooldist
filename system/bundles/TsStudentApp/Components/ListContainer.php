<?php

namespace TsStudentApp\Components;

class ListContainer implements Component
{
	private array $components = [];

	public function getKey(): string
	{
		return 'list';
	}

	public function add(Item $item): static
	{
		$this->components[] = [
			'name' => $item->getKey(),
			'data' => $item->toArray(),
		];
		return $this;
	}

	public function toArray(): array
	{
		return [
			'items' => $this->components
		];
	}
}