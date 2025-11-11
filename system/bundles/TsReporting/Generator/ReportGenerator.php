<?php

namespace TsReporting\Generator;

use Illuminate\Pipeline\Pipeline;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use TsReporting\Entity\Report;
use TsReporting\Generator\Columns\AbstractColumn;
use TsReporting\Generator\Filter\AbstractFilter;
use TsReporting\Generator\Filter\Period;
use TsReporting\Generator\Groupings\AbstractGrouping;
use TsReporting\Generator\Groupings\Aggregated;
use TsReporting\Generator\Groupings\QueryableInterface;
use TsReporting\Traits\TranslateTrait;

class ReportGenerator
{
	use TranslateTrait;

	const HOOK_COLUMN_BUILT = 'ts_reporting_generator_column_built';

	const HOOK_COLUMN_PREPARED = 'ts_reporting_generator_column_prepared';

	private Report $report;

	private \TsReporting\Generator\Bases\AbstractBase $base;

	/**
	 * @var AbstractGrouping[]
	 */
	private array $groupings;

	/**
	 * @var array AbstractColumn{[
	 */
	private array $columns;

	/**
	 * @var array
	 */
	private array $data = [];

	/**
	 * @var AbstractFilter[]
	 */
	private array $filters;

	private ValueHandler $valueHandler;

	private array $log = [];

	public function __construct(Report $report, ValueHandler $valueHandler, array $filters)
	{
		$this->report = $report;
		$this->base = $report->createGeneratorBase();
		$this->groupings = $this->report->createGeneratorObjects('groupings');
		$this->columns = $this->report->createGeneratorObjects('columns');
		$this->valueHandler = $valueHandler;
		$this->filters = $filters;
	}

	public function generate()
	{
		$config = [];
		$definitions = [];
		$rows = [];

		$period = $this->valueHandler->getFilter(Period::class)->getValue();
		if (!$period) {
			$config['message'] = $this->t('Bitte einen Zeitraum auswählen.');
			return compact('config', 'definitions', 'rows');
		}

		// Bei Aggregierter Gruppierung Kind-Gruppierung ergänzen (Parent: Labels, Childs: Items)
		$this->groupings = array_reduce($this->groupings, function (array $carry, AbstractGrouping $grouping) {
			$carry[] = $grouping;
			if ($grouping instanceof Aggregated) {
				$child = clone $grouping;
				$child->setParent($grouping);
				$carry[] = $child;
			}
			return $carry;
		}, []);

		$this->runQueryables();

		$config = [
			'visualization' => $this->report->visualization,
			'pivot' => [
				'show_grand_totals' => (bool)$this->report->visualization_grand_totals,
				'show_row_totals' => (bool)$this->report->visualization_row_totals,
				'grand_totals' => $this->t('Gesamtsummen'),
				'subtotals_for_label' => $this->t('Summe für {label}'),
				'row_totals' => $this->t('Zeilensummen')
			]
		];

		$definitions = array_reduce($this->groupings, function (array $carry, AbstractGrouping $grouping) {
			$carry[] = [
				'key' => $grouping->getId(),
				'type' => 'grouping',
				'label' => $grouping->getTitle(),
				'items' => array_values($grouping->getItems()),
//				'sort' => match ($grouping->getSortFlag()) {
//					SORT_NUMERIC => 'numeric',
//					SORT_NATURAL => 'natural'
//				},
				'format' => $grouping->getFormat($this->valueHandler),
				'pivot' => $grouping->pivot,
				'subtotals' => $grouping->pivot === 'row' && $grouping->subtotals
			];
			return $carry;
		}, []);

		$definitions = array_reduce($this->columns, function (array $carry, AbstractColumn $column) {
			$carry[] = [
				'key' => $column->getId(),
				'type' => 'column',
				'label' => $column->getTitle($column->compareOptions($this->columns)),
				'format' => $column->getFormat($this->valueHandler)
			];
			return $carry;
		}, $definitions);

		$rows = array_values($this->prepareData());

		if (empty($rows)) {
			$definitions = [];
			$config['message'] = $this->t('Für den ausgewählten Zeitraum stehen keine Daten zur Verfügung.');
		}

		return compact('config', 'definitions', 'rows');
	}

	private function runQueryables(): void
	{
		\Ext_TC_Util::setMySqlGroupConcatMaxLength();

		/** @var QueryableInterface[] $queryable */
		$queryable = array_filter($this->groupings, fn($grouping) => $grouping instanceof QueryableInterface);

		if (empty($queryable)) {
			$this->runColumns($this->valueHandler);
			return;
		}

		// Verknüpftes Kartesisches Produkt über alle Werte von group() jeder Queryable-Gruppierung
		$carry = [];
		(new Pipeline())
			->send($this->valueHandler)
			->through($queryable)
			->via('group')
			->then(function (ValueHandler $values) use (&$carry) {
				$carry[] = $values;
			});

		foreach ($carry as $values) {
			$this->runColumns($values);
		}
	}

	private function runColumns(ValueHandler $values): void
	{
		foreach ($this->columns as $column) {
			$prepared = $this->runColumn($column, $this->groupings, $values);
			foreach ($prepared as $row) {
				$this->prepareColumnRow($column, $row);
			}
		}
	}

	public function runColumn(AbstractColumn $column, array $groupings, ValueHandler $values): Collection
	{
		$builder = $this->base->createQueryBuilder($values);

		$builder->selectRaw("? as `column` /* ".get_class($column)." */", [$column->getId()]);

		foreach ($groupings as $grouping) {
			$grouping->setBase($this->base);
			$grouping->build($builder, $values);
		}

		$column->setBase($this->base);
		$column->setGroupings($groupings);
		$column->build($builder, $values);

		foreach ($this->filters as $filter) {
			if ($filter->hasValue()) {
				$filter->build($builder);
			}
		}

		$builder->applyScopes($this->base, $values);

		\System::wd()->executeHook(self::HOOK_COLUMN_BUILT,  $this, $column, $builder, $values);

		$data = $builder->get();

		$this->log[] = [
			'query' => Arr::last(\DB::getQueryHistory()),
			'sql' => $builder->toSql(),
			'bindings' => $builder->getBindings(),
			'column' => get_class($column),
			//'values' => $values,
			//'groupings' => $this->groupings
		];

		foreach ($groupings as $grouping) {
			$data = $grouping->prepare($data, $values);
		}

		$prepared = $column->prepare($data, $values);

		\System::wd()->executeHook(self::HOOK_COLUMN_PREPARED,  $this, $column, $prepared, $values);

		return $prepared;
	}

	private function prepareColumnRow(AbstractColumn $column, array $row): void
	{
		$key = [];
		foreach ($this->groupings as $grouping) {
			$id = $row[$grouping->buildSelectFieldId()] ?? null;
			if ($id === null) {
				// Leerer String ist in Ordnung, aber nicht null (Grouping falsch programmiert)
				throw new \RuntimeException(sprintf('Grouping field %s (%s) is empty for column %s (%s)', $grouping->getId(), get_class($grouping), $column->getId(), get_class($column)));
			}

			// Label muss für Pivot-Korrelation immer da sein
			$label = $row[$grouping->buildSelectFieldLabel()] ?? null;
			$sort = $grouping->getSortValue($row);
			$grouping->pushItem($row[$grouping->buildSelectFieldId()], $label, $label === $sort ? null : $sort);

			$key[] = $id;
		}

		$key = join(':', $key);

		if (!empty($this->data[$key][$column->getId()])) {
			// Merge (summieren)
			$column->reduce($this->data[$key][$column->getId()], $row);
		} else {
			$this->data[$key][$column->getId()] = $row;
		}
	}

	private function prepareData(): array
	{
		$sorting = [];

		$data = array_map(function (array $data) use (&$sorting) {
			$final = [];
			$first = Arr::first($data);
			foreach ($this->groupings as $index => $grouping) {
				$value = $first[$grouping->buildSelectFieldId()];
				$sorting[$index * 2][] = $grouping->getItems()[$value][2] ?? $grouping->getItems()[$value][1];
				$sorting[$index * 2 + 1] = SORT_NATURAL;
				$final[] = $value;
			}
			foreach ($this->columns as $column) {
				$final[] = [$data[$column->getId()]['result'] ?? null, $data[$column->getId()]['label'] ?? null];
			}
			return $final;
		}, $this->data);

		$sorting[] = &$data;

		array_multisort(...$sorting);

		return $data;
	}

	public function getLog(): array
	{
		return $this->log;
	}

	/**
	 * @return Report
	 */
	public function getReport(): Report
	{
		return $this->report;
	}

	/**
	 * @return array
	 */
	public function getGroupings(): array
	{
		return $this->groupings;
	}
}