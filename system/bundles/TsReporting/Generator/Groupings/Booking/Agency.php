<?php

namespace TsReporting\Generator\Groupings\Booking;

use TsReporting\Generator\Groupings\AbstractGrouping;
use TsReporting\Generator\Scopes\Booking\AgencyScope;
use TsReporting\Generator\ValueHandler;
use TsReporting\Services\QueryBuilder;

class Agency extends AbstractGrouping
{
	protected string $field;

	public function getTitle(): string
	{
		if (
			isset($this->field) &&
			$this->field === 'country'
		) {
			return $this->t('Agenturland');
		}

		return $this->t('Agentur');
	}

	public function build(QueryBuilder $builder, ValueHandler $values): void
	{
		$builder->requireScope(AgencyScope::class)->setField($this->field);
		$this->pushItem(0, '', null);

		[$id, $label] = match ($this->field) {
			// Grouping-ID darf nicht null sein
			'name' => ["COALESCE(ka.id, 0)", "COALESCE(ka.ext_1, '')"],
			// Keine Ahnung, wer es für eine gute Idee hielt, '0' bei einem fehlenden Land zu speichern
			'country' => ["IF(ka.ext_6 != '0', ka.ext_6, '')", "COALESCE(ka_countries.cn_short_".$values->getLocale().", '')"],
			'category' => ["COALESCE(ka.ext_39, 0)", "COALESCE(kagc.name, '')"],
		};

		$builder->selectRaw("$id as ".$this->buildSelectFieldId());
		$builder->selectRaw("$label as ".$this->buildSelectFieldLabel());
		$builder->groupBy($this->buildSelectFieldId());
	}

	public function getConfigOptions(): array
	{
		return [
			[
				'key' => 'field',
				'label' => $this->t('Feld'),
				'type' => 'select',
				'options' => [
					'name' => $this->t('Name'),
					'country' => $this->t('Land'),
					'category' => $this->t('Kategorie')
				]
			]
		];
	}
}