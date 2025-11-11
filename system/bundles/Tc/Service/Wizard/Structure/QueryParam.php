<?php

namespace Tc\Service\Wizard\Structure;

use Illuminate\Support\Arr;
use Illuminate\Support\Collection;

class QueryParam
{
	private bool $loop = false;

	private Collection $values;

	public function __construct(private string $key, mixed $values) {
		if (!$values instanceof Collection) {
			$values = collect(Arr::wrap($values));
		}
		$this->values = $values;
	}

	public function loop(): static
	{
		$this->loop = true;
		return $this;
	}

	public function isLoop(): bool
	{
		return $this->loop;
	}

	public function getKey(): string
	{
		return $this->key;
	}

	public function getValues(): array
	{
		return $this->values->toArray();
	}

	public function has($value): bool
	{
		if ($this->values->first() === '*') {
			return true;
		}
		return $this->values->contains($value);
	}

	public function count(): int
	{
		return $this->values->count();
	}

	public function getFirstValue(): mixed
	{
		return $this->values->first();
	}

	public function getIndex(mixed $value): int
	{
		return $this->values->values()->search($value);
	}

	public function getNextValue(mixed $after): mixed
	{
		if (!$this->has($after)) {
			throw new \RuntimeException('Cannot find next loop parameter ['.$this->values->implode(', ').'] value by unknown value ['.$after.']');
		}

		$index = $this->getIndex($after);

		return $this->values->values()->get($index + 1);
	}

}