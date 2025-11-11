<?php

namespace TsStudentApp\Components;

use Illuminate\Support\Collection;

/**
 * TODO
 */
class Grid implements Component
{
	private array $components = [];

	public function getKey(): string
	{
		return 'ion-grid';
	}

	public function __construct(private readonly \Illuminate\Container\Container $container) {}

	public function row(Collection $cols): static
	{
		$row = $this->container->make(Grid\Row::class);

		$cols->each(fn (Grid\Col $col) => $row->col($col));

		$this->components[] = $row;

		return $this;
	}

	public function toArray(): array
	{
		return [
			'rows' => array_map(fn (Grid\Row $row) => $row->toArray(), $this->components)
		];
	}
}