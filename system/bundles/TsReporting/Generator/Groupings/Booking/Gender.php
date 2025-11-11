<?php

namespace TsReporting\Generator\Groupings\Booking;

use TsReporting\Generator\Groupings\AbstractGrouping;
use TsReporting\Generator\ValueHandler;
use TsReporting\Services\QueryBuilder;

class Gender extends AbstractGrouping
{
	protected string $label;

	public function getTitle(): string
	{
		return $this->t('Geschlecht');
	}

	public function build(QueryBuilder $builder, ValueHandler $values): void
	{
		$this->pushItem(0, $this->t('kein Geschlecht'), 0);
		$this->pushItem(1, $this->t('männlich'), 1);
		$this->pushItem(2, $this->t('weiblich'), 2);
		$this->pushItem(3, $this->t('divers'), 3);

		$builder->addSelect('tc_c.gender as '.$this->buildSelectFieldId());
		$builder->groupBy($this->buildSelectFieldId());
	}
}