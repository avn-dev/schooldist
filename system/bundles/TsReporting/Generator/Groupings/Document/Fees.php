<?php

namespace TsReporting\Generator\Groupings\Document;

use Illuminate\Database\Query\JoinClause;
use TsReporting\Generator\Groupings\AbstractGrouping;
use TsReporting\Generator\Scopes\Booking\ItemScope;
use TsReporting\Generator\ValueHandler;
use TsReporting\Services\QueryBuilder;

class Fees extends AbstractGrouping
{
	protected string $type;

	public function getTitle(): string
	{
		return $this->t('Zusätzliche Gebühren (basierend auf Rechnung)');
	}

	public function build(QueryBuilder $builder, ValueHandler $values): void
	{
		$builder
			->requireScope(ItemScope::class)
			->addJoinAddition(function (JoinClause $join) {
				$join->where('kidvi.type', 'additional_'.$this->type);
			});

		$builder->addSelect('kc.id as '.$this->buildSelectFieldId());
		$builder->addSelect('kc.name_'.$values->getLocale().' as '.$this->buildSelectFieldLabel());
		$builder->groupBy($this->buildSelectFieldId());
	}

	public function getConfigOptions(): array
	{
		return [
			[
				'key' => 'type',
				'label' => $this->t('Typ'),
				'type' => 'select',
				'options' => [
					'general' => $this->t('Generelle Zusatzgebühren'),
					'course' => $this->t('Kursgebühr'),
					'accommodation' => $this->t('Unterkunftsgebühr'),
				]
			]
		];
	}
}
