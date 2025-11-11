<?php

namespace TsReporting\Generator\Groupings;

use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use TsReporting\Generator\ValueHandler;
use TsReporting\Services\QueryBuilder;

class Aggregated extends AbstractGrouping implements QueryableInterface
{
	private self $parent;

	protected array $aggregated;

	private array $sortOrder = [];

	public function getTitle(): string
	{
		return $this->t('Aggregierte Gruppierung');
	}

	public function build(QueryBuilder $builder, ValueHandler $values): void
	{
		if (!isset($this->parent)) {
			$builder->selectRaw("? as ".$this->buildSelectFieldId(), [$this->buildGroupingId($values->grouping)]);
			$builder->selectRaw("? as ".$this->buildSelectFieldLabel(), [$values->grouping->getTitle()]);
		} else {
			$values->grouping->setBase($this->base);
			$values->grouping->build($builder, $values);
		}
	}

	public function group(ValueHandler $values, \Closure $next): void
	{
		if (isset($this->parent)) {
			$next($values);
			return;
		}

		foreach ($this->getChildGroupings() as $grouping) {
			$newValues = clone $values;
			$newValues->grouping = $grouping;
			$next($newValues);
		}
	}

	/**
	 * @return AbstractGrouping[]
	 */
	public function getChildGroupings(): array
	{
		return array_map(function (array $grouping) {
			/** @var AbstractGrouping $object */
			$object = new $grouping['object']();
			$object->setId($this->getId().'_child');
			$object->setConfig($grouping['config']);
			$this->sortOrder[$this->buildGroupingId($object)] = $grouping['position'];
			return $object;
		}, $this->aggregated);
	}

	public function prepare(Collection $result, ValueHandler $values): Collection
	{
		if (isset($this->parent)) {
			$id = $this->buildGroupingId($values->grouping);
			$result->transform(function (array $row) use ($id) {
				$row[$this->buildSelectFieldId()] = $id.'_'.$row[$this->buildSelectFieldId()];
				return $row;
			});
		}

		return $result;
	}

	public function getSortValue(array $row): mixed
	{
		if (isset($this->parent)) {
			return parent::getSortValue($row);
		}

		// Sortieren nach Reihenfolge im Dialog
		return $this->sortOrder[$row[$this->buildSelectFieldId()]] ?? null;
	}

//	public function getSortFlag(): int
//	{
//		if (isset($this->parent)) {
//			return SORT_NATURAL;
//		}
//
//		return SORT_NUMERIC;
//	}

	public function setParent(self $grouping): void
	{
		$this->parent = $grouping;
		$this->id .= '_child';
	}

	private function buildGroupingId(AbstractGrouping $grouping): string
	{
		// Nicht nach spl_object_hash($grouping), damit Kinder der gleichen Gruppierung gemerged werden
		return Str::snake(str_replace([__NAMESPACE__, '\\'], '', get_class($grouping)));
	}
}