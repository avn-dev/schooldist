<?php

namespace TsReporting\Generator\Groupings;

use TsReporting\Generator\ValueHandler;
use TsReporting\Services\QueryBuilder;

class School extends AbstractGrouping
{
	public function getTitle(): string
	{
		return $this->t('Schule');
	}

	public function build(QueryBuilder $builder, ValueHandler $values): void
	{
		$builder->addSelect("cdb2.id as ".$this->buildSelectFieldId());
		$builder->addSelect("cdb2.ext_1 as ".$this->buildSelectFieldLabel());
		$builder->groupBy($this->buildSelectFieldId());
//		$builder->orderBy("cdb2.position");
	}
}