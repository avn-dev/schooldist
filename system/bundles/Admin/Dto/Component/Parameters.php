<?php

namespace Admin\Dto\Component;

readonly class Parameters
{
	public function __construct(
		private array $items
	) {}

	public function get(string $name, $default = null): mixed
	{
		return $this->items[$name] ?? $default;
	}

	public function toArray()
	{
		return $this->items;
	}

	public static function fromArray(array $array): static
	{
		return new static($array);
	}
}