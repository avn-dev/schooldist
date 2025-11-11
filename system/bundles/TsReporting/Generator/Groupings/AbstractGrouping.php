<?php

namespace TsReporting\Generator\Groupings;

use TsReporting\Generator\ValueHandler;
use TsReporting\Services\QueryBuilder;
use TsReporting\Traits\TranslateTrait;
use TsReporting\Traits\ColumnTrait;

abstract class AbstractGrouping
{
	use ColumnTrait, TranslateTrait;

	public string $pivot = ''; // TODO

	public bool $subtotals = false; // TODO

	private array $items = [];

	abstract public function getTitle(): string;

	abstract public function build(QueryBuilder $builder, ValueHandler $values): void;

	public function buildSelectFieldId(): string
	{
		return sprintf('grouping_%s_id', $this->getId());
	}

	public function buildSelectFieldLabel(): string
	{
		return sprintf('grouping_%s_label', $this->getId());
	}

	public function getSortValue(array $row): mixed
	{
		return $row[$this->buildSelectFieldLabel()];
//		$value = $this->items[$row[$this->buildSelectFieldId()]];
//		if ($value === null) {
//			throw new \RuntimeException('No sort value for '.get_class($this));
//		}
//		return $value;
	}

	final public function pushItem($key, $label, $sort): void
	{
		if (!isset($this->items[$key])) {
			$this->items[$key] = [$key, $label, $sort];
		}
	}

	final public function getItems(): array
	{
		return $this->items;
	}

//	public function getSortFlag(): int
//	{
//		return SORT_NATURAL;
//	}
}