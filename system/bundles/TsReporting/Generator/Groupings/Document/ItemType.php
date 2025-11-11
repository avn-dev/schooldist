<?php

namespace TsReporting\Generator\Groupings\Document;

use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Arr;
use TsReporting\Generator\Columns\Booking\Revenue;
use TsReporting\Generator\Groupings\AbstractGrouping;
use TsReporting\Generator\Scopes\Booking\ItemScope;
use TsReporting\Generator\ValueHandler;
use TsReporting\Services\QueryBuilder;

class ItemType extends AbstractGrouping
{
	protected string $type;

	public function getTitle(): string
	{
		return $this->t('Positionstyp');
	}

	public function build(QueryBuilder $builder, ValueHandler $values): void
	{
		$types = (new Revenue())->matchItemType($this->type);
		$labels = Arr::first($this->getConfigOptions(), fn(array $option) => $option['key'] === 'type')['options'];

		$builder
			->requireScope(ItemScope::class)
			->addJoinAddition(function (JoinClause $join) use ($types) {
				if (!empty($types)) {
					$join->whereIn('kidvi.type', $types);
				}
			});

		$builder->selectRaw('? as '.$this->buildSelectFieldId(), [$this->type]);
		$builder->selectRaw('? as '.$this->buildSelectFieldLabel(), [$labels[$this->type]]);
	}

	public function getConfigOptions(): array
	{
		// TODO: Option je nach Typ, aber dafür muss auch build() angepasst werden
		$types = Arr::first((new Revenue())->getConfigOptions(), fn(array $option) => $option['key'] === 'service_type')['options'];

		return [
			[
				'key' => 'type',
				'label' => $this->t('Typ'),
				'type' => 'select',
				'options' => $types
			]
		];
	}
}
