<?php

namespace Admin\Dto\Component\Search;

use Admin\Interfaces\RouterAction;

class SearchResult
{
	private int $hits = 0;

	private array $rows = [];

	public function isEmpty(): bool
	{
		return $this->hits === 0;
	}

	public function addRow(string $key, RouterAction $routerAction, array $matchingParts): static
	{
		$this->hits++;
		$this->rows[$key] = [$routerAction, $matchingParts];
		return $this;
	}

	/**
	 * @return int
	 */
	public function getHits(): int
	{
		return $this->hits;
	}

	public function getRows(): array
	{
		return $this->rows;
	}
}