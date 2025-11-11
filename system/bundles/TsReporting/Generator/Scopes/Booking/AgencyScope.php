<?php

namespace TsReporting\Generator\Scopes\Booking;

use TsReporting\Generator\Scopes\AbstractScope;
use TsReporting\Generator\ValueHandler;
use TsReporting\Services\QueryBuilder;

class AgencyScope extends AbstractScope
{
	private string $field;

	public function setField(string $field)
	{
		$this->field = $field;
	}

	public function apply(QueryBuilder $builder, ValueHandler $values): void
	{
		$builder->leftJoin('ts_companies as ka', 'ka.id', 'ts_i.agency_id');

		// TODO Da ein Scope (die Klasse) jeweils nur einmal angewendet wird, funktioniert das nicht, wenn bspw. Name und Land kombiniert werden
//		switch ($this->field) {
//			case 'country':
				$builder->leftJoin('data_countries as ka_countries', 'ka_countries.cn_iso_2', 'ka.ext_6');
//				break;
//			case 'category';
				$builder->leftJoin('kolumbus_agency_categories as kagc', 'kagc.id', 'ka.ext_39');
//				break;
//		}
	}
}