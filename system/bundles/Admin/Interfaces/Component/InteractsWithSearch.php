<?php

namespace Admin\Interfaces\Component;

use Admin\Dto\Component\Search;

interface InteractsWithSearch
{
	public function isAccessible(\Access $access): bool;

	public function getLabel(): string;

	public function search(string $query, int $limit): Search\SearchResult;
}