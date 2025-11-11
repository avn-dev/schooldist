<?php

namespace TsReporting\Generator\Groupings\Booking;

use TsReporting\Generator\Groupings\AbstractGrouping;
use TsReporting\Generator\ValueHandler;
use TsReporting\Services\QueryBuilder;

class Inbox extends AbstractGrouping
{
	public function getTitle(): string
	{
		return $this->t('Inbox');
	}

	public function build(QueryBuilder $builder, ValueHandler $values): void
	{
		$builder->addSelect("ts_i.inbox as ".$this->buildSelectFieldId());
		$builder->addSelect("k_inb.name as ".$this->buildSelectFieldLabel());
		$builder->leftJoin('kolumbus_inboxlist as k_inb', 'k_inb.short', 'ts_i.inbox');
		$builder->groupBy($this->buildSelectFieldId());
//		$builder->orderBy($this->buildSelectFieldLabel());
	}
}
