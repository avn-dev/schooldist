<?php

namespace Communication\Traits\Model\Log;

use Communication\Interfaces\Model\HasCommunication;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;

trait WithModelRelations
{
	public function addRelation(\WDBasic $model): static
	{
		return $this->addRelations([$model]);
	}

	public function addRelations(array $relations): static
	{
		$allRelations = $this->relations;

		foreach ($relations as $relation) {
			if ($relation instanceof \WDBasic) {
				$allRelations[] = ['relation' => $relation::class, 'relation_id' => (int)$relation->id];
			} else if (is_array($relation)) {
				$allRelations[] = $relation;
			}
		}

		// Damit der Unique auch wirklich funktioniert
		$allRelations = array_map(function ($relation) {
			$new = Arr::except($relation, 'message_id');
			$new['relation_id'] = (int)$relation['relation_id'];
			return $new;
		}, $allRelations);

		// unique
		$this->relations = array_values(array_map('unserialize', array_unique(array_map('serialize', $allRelations))));

		return $this;
	}

	public function searchRelations(string|array $class): Collection
	{
		$classes = array_unique(Arr::wrap($class));

		return collect($this->relations)
			->filter(function ($relation) use ($classes) {
				$found = Arr::first($classes, fn ($check) => is_a($relation['relation'], $check, true));
				return $found !== null;
			})
			->map(fn ($relation) => \Factory::getInstance($relation['relation'], $relation['relation_id']));
	}
}