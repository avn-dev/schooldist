<?php

namespace TsReporting\Generator\Filter;

use TsReporting\Traits\TimeframeFilterTrait;
use TsReporting\Services\QueryBuilder;

class Period extends AbstractFilter
{
	use TimeframeFilterTrait;

	public function getTitle(): string
	{
		return $this->t('Zeitraum');
	}

	function getType(): string
	{
		return 'timeframe';
	}

	public function build(QueryBuilder $builder)
	{
		// NOOP – Wird direkt in der Basis gesetzt
	}

	public function isRequired(): bool
	{
		return true;
	}
}