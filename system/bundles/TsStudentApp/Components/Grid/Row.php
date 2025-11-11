<?php

namespace TsStudentApp\Components\Grid;

use TsStudentApp\Components\Component;

class Row implements Component
{
	private array $components = [];

	public function getKey(): string
	{
		return 'ion-row';
	}

	public function col(Col $col): static
	{
		$this->components[] = $col->toArray();
		return $this;
	}

	public function toArray(): array
	{
		return [
			'cols' => $this->components
		];
	}
}