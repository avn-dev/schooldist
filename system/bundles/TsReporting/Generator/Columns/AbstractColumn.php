<?php

namespace TsReporting\Generator\Columns;

use Illuminate\Support\Str;
use TsReporting\Generator\Groupings\AbstractGrouping;
use TsReporting\Generator\ValueHandler;
use TsReporting\Services\QueryBuilder;
use TsReporting\Traits\TranslateTrait;
use TsReporting\Traits\ColumnTrait;

abstract class AbstractColumn
{
	use ColumnTrait, TranslateTrait;

	protected array $groupings;

	protected array $availableGroupings;

	abstract public function getTitle(?array $varying = null): string;

	abstract public function build(QueryBuilder $builder, ValueHandler $values): void;

	public function reduce(array &$carry, array $item): void
	{
		throw new \BadMethodCallException('Reduce/merge not implemented for '.static::class);
	}

	public function setGroupings(array $groupings): void
	{
		$this->groupings = $groupings;
	}

	protected function buildGroupingRowKey(array $row, string $id): string
	{
		$key = array_map(fn(AbstractGrouping $grouping) => $row[$grouping->buildSelectFieldId()], $this->groupings);
		$key[] .= ':'.$id;

		return join(':', $key);
	}

	public function getAvailableGroupings(): array
	{
		return $this->availableGroupings;
	}

	public function compareOptions(array $columns): array
	{
		$options = $this->getConfigOptions();

		$comparison = array_reduce($columns, function (array $carry, self $column) use ($options) {
			if (
				// Nicht die selbe Instanz und nur gleiche Klassen (keine Ableitungen)
				$this === $column ||
				get_class($this) !== get_class($column)
			) {
				return $carry;
			}

			foreach (array_column($options, 'key') as $option) {
				$attribute = Str::camel($option);
				if ($this->{$attribute} !== $column->{$attribute}) {
					$carry[] = $option;
				}
			}
			return $carry;
		}, []);

		// Sortieren und doppelte Werte entfernen
		return array_intersect(array_column($options, 'key'), $comparison);
	}
}