<?php

namespace Core\Traits\Notification;

use Illuminate\Support\Arr;

trait WithModelsRelations
{
	/**
	 * @var \WDBasic[]
	 */
	private array $relations = [];

	public function relation(\WDBasic|array $relation): static
	{
		$this->relations = array_merge($this->relations, Arr::wrap($relation));
		return $this;
	}

	/**
	 * @return \WDBasic[]
	 */
	public function getRelations(): array
	{
		return $this->relations;
	}
}