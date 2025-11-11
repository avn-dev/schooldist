<?php

namespace TsReporting\Generator\Bases;

use Illuminate\Database\Query\JoinClause;
use TsReporting\Generator\Filter\Booking\CreationDate;
use TsReporting\Generator\ValueHandler;
use TsReporting\Services\QueryBuilder;

class Booking extends AbstractBase {

	public function getTitle(): string
	{
		return $this->t('Buchung: Erstellungsdatum');
	}

	public function createQueryBuilder(ValueHandler $values): QueryBuilder
	{
		$builder = (new QueryBuilder())
			->from('ts_inquiries', 'ts_i')
			->join('ts_inquiries_journeys as ts_ij', function (JoinClause $join) {
				$join->on('ts_ij.inquiry_id', 'ts_i.id')
					->where('ts_ij.active', 1)
					->where('ts_ij.type', '&', \Ext_TS_Inquiry_Journey::TYPE_BOOKING);
			})
			->join('customer_db_2 as cdb2', 'ts_ij.school_id', 'cdb2.id')
			->join('ts_inquiries_to_contacts as ts_itc', function (JoinClause $join) {
				$join->on('ts_itc.inquiry_id', 'ts_i.id')
					->where('ts_itc.type', 'traveller');
			})
			->join('tc_contacts as tc_c', 'tc_c.id', 'ts_itc.contact_id')
			->leftJoin('tc_contacts_numbers as tc_cn', 'tc_cn.contact_id', 'tc_c.id')
			->where('ts_i.active',  1)
			->where('ts_i.type', '&', \Ext_TS_Inquiry::TYPE_BOOKING);

		$this->addWhere($builder, $values);

		return $builder;
	}

	protected function addWhere(QueryBuilder $builder, ValueHandler $values)
	{
		CreationDate::apply($builder, $values->getPeriod()->getStartDate(), $values->getPeriod()->getEndDate());
	}
}