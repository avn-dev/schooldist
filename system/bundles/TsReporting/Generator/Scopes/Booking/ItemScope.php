<?php

namespace TsReporting\Generator\Scopes\Booking;

use Illuminate\Database\Query\JoinClause;
use TsReporting\Generator\Bases\BookingServicePeriod;
use TsReporting\Generator\Scopes\AbstractScope;
use TsReporting\Generator\ValueHandler;
use TsReporting\Services\QueryBuilder;

class ItemScope extends AbstractScope
{
	const NEGATE_FACTOR = 'negate_factor';

	private array $joinAdditions = [];

	public function addJoinAddition(\Closure $callback)
	{
		$this->joinAdditions[] = $callback;
	}

	public function apply(QueryBuilder $builder, ValueHandler $values): void
	{
		$builder
			->selectRaw('IF(kidvi.amount < 0, -1, 1) AS '.self::NEGATE_FACTOR)
			->join('kolumbus_inquiries_documents as kid', function (JoinClause $join) {
				$allInvoiceTypes = \Ext_Thebing_Inquiry_Document_Search::getTypeData('invoice_with_creditnote');
				$invoiceTypes = \Ext_Thebing_Inquiry_Document_Search::getTypeDataAsString('invoice_with_creditnotes_and_without_proforma');
				$proformaTypes = \Ext_Thebing_Inquiry_Document_Search::getTypeDataAsString('proforma_with_creditnote');

				$join->on('kid.entity_id', 'ts_i.id')
					->where('kid.entity', \Ext_TS_Inquiry::class)
					->where('kid.active', 1)
					->whereIn('kid.type', $allInvoiceTypes)
					->where('kid.is_credit', 0) // Credits immer ignorieren, da sich diese gegenseitig aufheben
					->whereRaw("IF(ts_i.has_invoice = 1, kid.type IN ($invoiceTypes), kid.type IN ($proformaTypes))"); // Proformas nur einbeziehen wenn es noch keine Rechnung gibt
			})
			->leftJoin('ts_documents_to_documents as ts_dtd_creditnotes', function (JoinClause $join) {
				$join->on('ts_dtd_creditnotes.parent_document_id', 'kid.id')
					->where('ts_dtd_creditnotes.type', 'creditnote');
			})
			// Credit-Creditnotes haben weder is_credit = 1 noch werden die mit der Ursprungs-CN verknüpft
			// Scheinbar wird eine Creditnote aber immer mit credit und creditnote mit der Ursprungsrechnung verknüpft
			->leftJoin('ts_documents_to_documents as ts_dtd_creditnotes2', function (JoinClause $join) {
				$join->on('ts_dtd_creditnotes2.child_document_id', 'kid.id')
					->where('ts_dtd_creditnotes2.type', 'creditnote')
					->where('kid.type', 'creditnote');
			})
			->leftJoin('ts_documents_to_documents as ts_dtd_credit', function (JoinClause $join) {
				$join->on('ts_dtd_credit.parent_document_id', $join->raw("IFNULL(ts_dtd_creditnotes2.parent_document_id, kid.id)"))
					->where('ts_dtd_credit.type', 'credit');
			})
			->join('kolumbus_inquiries_documents_versions as kidv', function (JoinClause $join) {
				$join->on('kidv.id', 'kid.latest_version')
					->where('kidv.active', 1);
			})
			->join('kolumbus_inquiries_documents_versions_items as kidvi', function (JoinClause $join) use ($values) {
				$join->on('kidvi.version_id', 'kidv.id')
					->where('kidvi.active', 1)
					->where('kidvi.onPdf', 1)
					// Rechnungen, die eine Agentur-Creditnote haben, immer ignorieren – hier wird die CN benutzt
					->whereNull('ts_dtd_creditnotes.child_document_id')
					// Rechnungen, die eine Credit haben, immer rauswerfen
					->whereNull('ts_dtd_credit.child_document_id');

				if (
					$this->base instanceof BookingServicePeriod /*&&
					!$join->hasMacro(BookingServicePeriod::ITEM_PERIOD_FILTERED)*/
				) {
					$join->whereRaw("kidvi.index_from <= ?", [$values->getPeriod()->getEndDate()]);
					$join->whereRaw("kidvi.index_until >= ?", [$values->getPeriod()->getStartDate()]);
				}

				foreach ($this->joinAdditions as $callback) {
					$callback($join);
				}
			})
			->leftJoin('kolumbus_costs as kc', function (JoinClause $join) {
				$join->on('kc.id', 'kidvi.type_id')
					->whereIn('kidvi.type', ['additional_general', 'additional_course', 'additional_accommodation']);
			})
			->groupBy('kidvi.id');
	}
}