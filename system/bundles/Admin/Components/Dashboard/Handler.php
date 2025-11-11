<?php

namespace Admin\Components\Dashboard;

use Illuminate\Contracts\Support\Arrayable;

class Handler implements Arrayable
{
	private $deletable = true;

	private int $minRows = 1;
	private int $minCols = 1;

	public function __construct(
		private int $rows = 1,
		private int $cols = 1,
		private bool $default = false,
	){}

	public function min(int $rows = 1, int $cols = 1): static
	{
		$this->minRows = $rows;
		$this->minCols = $cols;
		return $this;
	}

	public function deletable(bool $deletable): static
	{
		$this->deletable = $deletable;
		return $this;
	}

	public function getRows(): int
	{
		return $this->rows;
	}

	public function getCols(): int
	{
		return $this->cols;
	}

	public function getMinRows(): int
	{
		return $this->minRows;
	}

	public function getMinCols(): int
	{
		return $this->minCols;
	}

	public function isDefault(): bool
	{
		return $this->default;
	}

	public function toArray(): array
	{
		return [
			'rows' => $this->rows,
			'cols' => $this->cols,
			'deletable' => $this->deletable,
			'default' => $this->default,
		];
	}
}